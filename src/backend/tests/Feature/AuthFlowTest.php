<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'New User',
            'email' => 'new-user@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('user.email', 'new-user@example.com');
        $this->assertDatabaseHas('users', [
            'email' => 'new-user@example.com',
            'role' => User::ROLE_MEMBER,
        ]);
    }

    public function test_register_validation_error_when_password_confirmation_mismatched(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Bad User',
            'email' => 'bad-user@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different',
        ]);

        $response->assertStatus(422);
    }

    public function test_guest_can_verify_email_with_signed_url(): void
    {
        $user = User::factory()->unverified()->create();

        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->id,
                'hash' => sha1($user->getEmailForVerification()),
            ]
        );

        $response = $this->getJson($url);

        $response->assertOk();
        $response->assertJsonPath('message', 'メール認証が完了しました。');

        $user->refresh();
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_verify_email_returns_forbidden_when_hash_mismatch(): void
    {
        $user = User::factory()->unverified()->create();

        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->id,
                'hash' => 'deadbeefdeadbeefdeadbeefdeadbeefdeadbeef',
            ]
        );

        $this->getJson($url)->assertForbidden();
    }

    public function test_forgot_password_returns_ok_without_password_reset_named_route(): void
    {
        User::factory()->create(['email' => 'reset-me@example.com']);

        $response = $this->postJson('/api/forgot-password', [
            'email' => 'reset-me@example.com',
        ]);

        $response->assertOk();
    }
}
