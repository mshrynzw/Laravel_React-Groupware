<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function index()
    {
        /** @var User $actor */
        $actor = request()->user();
        $query = User::query()->with(['groups', 'primaryGroup'])->orderBy('id');

        if (! $actor->isSuperAdmin()) {
            $allowedGroupIds = $actor->groups()->pluck('groups.id');
            $query->whereHas('groups', fn ($q) => $q->whereIn('groups.id', $allowedGroupIds));
        }

        if ($keyword = request('q')) {
            $query->where(function ($q) use ($keyword): void {
                $q->where('name', 'like', "%{$keyword}%")
                    ->orWhere('email', 'like', "%{$keyword}%");
            });
        }

        return response()->json($query->paginate((int) request('per_page', 15)));
    }

    public function store(StoreUserRequest $request)
    {
        /** @var User $actor */
        $actor = $request->user();
        $this->ensureCanManageUser($actor);

        return DB::transaction(function () use ($request, $actor) {
            $validated = $request->validated();
            $groupIds = collect($validated['group_ids'] ?? [])->map(fn ($id) => (int) $id);
            $role = $validated['role'] ?? User::ROLE_MEMBER;

            if ($role === User::ROLE_SUPERADMIN && ! $actor->isSuperAdmin()) {
                abort(403);
            }

            $plainPassword = Str::password(12);
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'role' => $role,
                'password' => $plainPassword,
                'primary_group_id' => $validated['primary_group_id'] ?? null,
            ]);

            if ($groupIds->isNotEmpty()) {
                $this->ensureGroupScope($actor, $groupIds->all());
                $user->groups()->sync($groupIds->all());
            }

            if ($user->primary_group_id !== null && ! $user->groups()->where('groups.id', $user->primary_group_id)->exists()) {
                abort(422, 'primary_group_id は所属済みグループから指定してください。');
            }
            AuditLogger::log($request, 'user.created', $user, [
                'role' => $user->role,
                'group_ids' => $groupIds->all(),
                'primary_group_id' => $user->primary_group_id,
            ]);

            return response()->json([
                'message' => 'ユーザーを作成しました。',
                'temporary_password' => $plainPassword,
                'user' => $user->load(['groups', 'primaryGroup']),
            ], 201);
        });
    }

    public function show(User $user)
    {
        $this->ensureCanAccessTarget(request()->user(), $user);

        return response()->json($user->load(['groups', 'primaryGroup']));
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        /** @var User $actor */
        $actor = $request->user();
        $this->ensureCanManageUser($actor);
        $this->ensureCanAccessTarget($actor, $user);

        return DB::transaction(function () use ($request, $user, $actor) {
            $validated = $request->validated();
            if (array_key_exists('role', $validated) && $validated['role'] === User::ROLE_SUPERADMIN && ! $actor->isSuperAdmin()) {
                abort(403);
            }

            $user->fill(collect($validated)->only(['name', 'email', 'role', 'primary_group_id'])->toArray());
            $user->save();

            if (array_key_exists('group_ids', $validated)) {
                $groupIds = collect($validated['group_ids'] ?? [])->map(fn ($id) => (int) $id);
                $this->ensureGroupScope($actor, $groupIds->all());
                $user->groups()->sync($groupIds->all());
            }

            if ($user->primary_group_id !== null && ! $user->groups()->where('groups.id', $user->primary_group_id)->exists()) {
                abort(422, 'primary_group_id は所属済みグループから指定してください。');
            }
            AuditLogger::log($request, 'user.updated', $user, [
                'role' => $user->role,
                'primary_group_id' => $user->primary_group_id,
            ]);

            return response()->json([
                'message' => 'ユーザーを更新しました。',
                'user' => $user->load(['groups', 'primaryGroup']),
            ]);
        });
    }

    public function destroy(User $user)
    {
        /** @var User $actor */
        $actor = request()->user();
        $this->ensureCanManageUser($actor);
        $this->ensureCanAccessTarget($actor, $user);

        if ($actor->is($user)) {
            abort(422, '自分自身は削除できません。');
        }

        $user->delete();
        AuditLogger::log(request(), 'user.deleted', $user, ['deleted_user_id' => $user->id]);

        return response()->json(['message' => 'ユーザーを削除しました。']);
    }

    private function ensureCanManageUser(User $actor): void
    {
        if (! in_array($actor->role, [User::ROLE_SUPERADMIN, User::ROLE_ADMIN], true)) {
            abort(403);
        }
    }

    private function ensureCanAccessTarget(User $actor, User $target): void
    {
        if ($actor->isSuperAdmin()) {
            return;
        }

        $actorGroupIds = $actor->groups()->pluck('groups.id')->all();
        $targetInScope = $target->groups()->whereIn('groups.id', $actorGroupIds)->exists();
        if (! $targetInScope) {
            abort(403);
        }
    }

    private function ensureGroupScope(User $actor, array $groupIds): void
    {
        if ($actor->isSuperAdmin() || $groupIds === []) {
            return;
        }

        $allowed = $actor->groups()->whereIn('groups.id', $groupIds)->count();
        if ($allowed !== count(array_unique($groupIds))) {
            abort(403);
        }
    }
}
