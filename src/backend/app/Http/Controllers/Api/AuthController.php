<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => User::ROLE_MEMBER,
        ]);

        Auth::login($user);
        if ($request->hasSession()) {
            $request->session()->regenerate();
        }
        AuditLogger::log($request, 'auth.register', $user, ['email' => $user->email]);

        return response()->json([
            'message' => 'アカウントを作成しました。',
            'user' => $user,
        ], 201);
    }

    public function login(LoginRequest $request)
    {
        if (! Auth::attempt($request->validated())) {
            AuditLogger::log($request, 'auth.login_failed', null, ['email' => $request->validated('email')]);
            throw ValidationException::withMessages([
                'email' => ['メールアドレスまたはパスワードが正しくありません。'],
            ]);
        }

        $request->session()->regenerate();
        AuditLogger::log($request, 'auth.login', $request->user());

        return response()->json([
            'message' => 'ログインしました。',
            'user' => $request->user(),
        ]);
    }

    public function logout(Request $request)
    {
        AuditLogger::log($request, 'auth.logout', $request->user());
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'message' => 'ログアウトしました。',
        ]);
    }

    public function me(Request $request)
    {
        return response()->json($request->user()->load(['groups', 'primaryGroup']));
    }

    public function sendVerificationNotification(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return response()->json(['message' => '既にメール認証済みです。']);
        }

        $request->user()->sendEmailVerificationNotification();
        AuditLogger::log($request, 'auth.email_verification_notification_sent', $request->user());

        return response()->json(['message' => '認証メールを送信しました。']);
    }

    public function verifyEmail(Request $request, string $id, string $hash)
    {
        $user = User::query()->findOrFail($id);

        if (! hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            abort(403);
        }

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            AuditLogger::log($request, 'auth.email_verified', $user);
        }

        return response()->json(['message' => 'メール認証が完了しました。']);
    }

    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => ['required', 'email']]);

        $status = Password::sendResetLink($request->only('email'));
        AuditLogger::log($request, 'auth.password_reset_link_requested', null, ['email' => $request->string('email')->toString(), 'status' => $status]);

        return $status === Password::RESET_LINK_SENT
            ? response()->json(['message' => __($status)])
            : response()->json(['message' => __($status)], 422);
    }

    public function resetPassword(Request $request)
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $status = Password::reset(
            $validated,
            function ($user, $password): void {
                $user->forceFill(['password' => $password])->save();
            }
        );
        AuditLogger::log($request, 'auth.password_reset', null, ['email' => $validated['email'], 'status' => $status]);

        return $status === Password::PASSWORD_RESET
            ? response()->json(['message' => __($status)])
            : response()->json(['message' => __($status)], 422);
    }
}
