<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Group\StoreGroupRequest;
use App\Http\Requests\Group\UpdateGroupRequest;
use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\User;
use App\Support\AuditLogger;

class GroupController extends Controller
{
    public function index()
    {
        /** @var User $actor */
        $actor = request()->user();
        $query = Group::query()->orderBy('id');

        if (! $actor->isSuperAdmin()) {
            $query->whereHas('users', fn ($q) => $q->where('users.id', $actor->id));
        }

        if ($keyword = request('q')) {
            $query->where('name', 'like', "%{$keyword}%");
        }

        return response()->json($query->paginate((int) request('per_page', 15)));
    }

    public function store(StoreGroupRequest $request)
    {
        $this->ensureCanManageGroup($request->user());

        $group = Group::create([
            'name' => $request->validated('name'),
            'description' => $request->validated('description'),
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        if ($request->user()->isAdmin()) {
            $group->users()->syncWithoutDetaching([$request->user()->id]);
        }
        AuditLogger::log($request, 'group.created', $group, [
            'name' => $group->name,
            'description' => $group->description,
        ]);

        return response()->json([
            'message' => 'グループを作成しました。',
            'group' => $group,
        ], 201);
    }

    public function show(Group $group)
    {
        $this->ensureInScope(request()->user(), $group);

        return response()->json($group->load('users:id,name,email,role'));
    }

    public function update(UpdateGroupRequest $request, Group $group)
    {
        $this->ensureCanManageGroup($request->user());
        $this->ensureInScope($request->user(), $group);

        $group->fill($request->validated());
        $group->updated_by = $request->user()->id;
        $group->save();
        AuditLogger::log($request, 'group.updated', $group, [
            'name' => $group->name,
            'description' => $group->description,
        ]);

        return response()->json([
            'message' => 'グループを更新しました。',
            'group' => $group,
        ]);
    }

    public function destroy(Group $group)
    {
        $this->ensureCanManageGroup(request()->user());
        $this->ensureInScope(request()->user(), $group);

        AuditLogger::log(request(), 'group.deleted', $group, ['name' => $group->name]);
        $group->delete();

        return response()->json(['message' => 'グループを削除しました。']);
    }

    private function ensureCanManageGroup(User $actor): void
    {
        if (! in_array($actor->role, [User::ROLE_SUPERADMIN, User::ROLE_ADMIN], true)) {
            abort(403);
        }
    }

    private function ensureInScope(User $actor, Group $group): void
    {
        if ($actor->isSuperAdmin()) {
            return;
        }

        if (! $group->users()->where('users.id', $actor->id)->exists()) {
            abort(403);
        }
    }
}
