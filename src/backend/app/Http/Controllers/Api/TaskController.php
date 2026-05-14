<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Task\StoreTaskRequest;
use App\Http\Requests\Task\UpdateTaskRequest;
use App\Models\Task;
use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $query = $this->scopedTasksQuery($actor)
            ->with(['creator:id,name,email', 'assignee:id,name,email'])
            ->orderBy('status')
            ->orderBy('position')
            ->orderByDesc('id');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }
        if ($request->filled('assignee_user_id')) {
            $query->where('assignee_user_id', (int) $request->query('assignee_user_id'));
        }

        return response()->json($query->paginate((int) $request->query('per_page', 50)));
    }

    public function store(StoreTaskRequest $request): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $assigneeId = $request->validated('assignee_user_id');
        $this->ensureCanAssign($actor, $assigneeId);

        $task = Task::create([
            'title' => $request->validated('title'),
            'description' => $request->validated('description'),
            'status' => $request->validated('status', Task::STATUS_TODO),
            'due_at' => $request->validated('due_at'),
            'creator_user_id' => $actor->id,
            'assignee_user_id' => $assigneeId,
            'position' => (int) ($request->validated('position') ?? 0),
        ]);

        AuditLogger::log($request, 'task.created', $task, [
            'title' => $task->title,
            'status' => $task->status,
        ]);

        return response()->json([
            'message' => 'タスクを作成しました。',
            'data' => $task->load(['creator:id,name,email', 'assignee:id,name,email']),
        ], 201);
    }

    public function show(Request $request, Task $task): JsonResponse
    {
        $this->ensureCanView($request->user(), $task);

        return response()->json($task->load(['creator:id,name,email', 'assignee:id,name,email']));
    }

    public function update(UpdateTaskRequest $request, Task $task): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $this->ensureCanEdit($actor, $task);

        if ($request->has('assignee_user_id')) {
            $this->ensureCanAssign($actor, $request->validated('assignee_user_id'));
        }

        $validated = $request->validated();
        $task->fill(collect($validated)->only([
            'title', 'description', 'status', 'due_at', 'assignee_user_id', 'position',
        ])->all());
        $task->save();

        AuditLogger::log($request, 'task.updated', $task, [
            'status' => $task->status,
        ]);

        return response()->json([
            'message' => 'タスクを更新しました。',
            'data' => $task->load(['creator:id,name,email', 'assignee:id,name,email']),
        ]);
    }

    public function destroy(Request $request, Task $task): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $this->ensureCanDelete($actor, $task);

        $id = $task->id;
        $task->delete();

        AuditLogger::log($request, 'task.deleted', null, ['deleted_task_id' => $id]);

        return response()->json(['message' => 'タスクを削除しました。']);
    }

    /**
     * @param  Builder<Task>  $query
     */
    private function scopedTasksQuery(User $actor): Builder
    {
        $query = Task::query();

        if ($actor->isSuperAdmin()) {
            return $query;
        }

        if ($actor->isAdmin()) {
            $groupIds = $actor->groups()->pluck('groups.id')->all();

            return $query->where(function ($q) use ($actor, $groupIds): void {
                $q->where('creator_user_id', $actor->id)
                    ->orWhere('assignee_user_id', $actor->id);
                if ($groupIds !== []) {
                    $q->orWhereHas('creator', fn ($uq) => $uq->whereHas('groups', fn ($gq) => $gq->whereIn('groups.id', $groupIds)))
                        ->orWhereHas('assignee', fn ($uq) => $uq->whereHas('groups', fn ($gq) => $gq->whereIn('groups.id', $groupIds)));
                }
            });
        }

        return $query->where(function ($q) use ($actor): void {
            $q->where('creator_user_id', $actor->id)
                ->orWhere('assignee_user_id', $actor->id);
        });
    }

    private function ensureCanView(User $actor, Task $task): void
    {
        if (! $this->scopedTasksQuery($actor)->whereKey($task->id)->exists()) {
            abort(404);
        }
    }

    private function ensureCanEdit(User $actor, Task $task): void
    {
        $this->ensureCanView($actor, $task);
        if ($actor->isSuperAdmin() || $actor->isAdmin()) {
            return;
        }
        if ($task->creator_user_id === $actor->id || $task->assignee_user_id === $actor->id) {
            return;
        }
        abort(403);
    }

    private function ensureCanDelete(User $actor, Task $task): void
    {
        if ($actor->isSuperAdmin()) {
            return;
        }
        if ($task->creator_user_id === $actor->id) {
            return;
        }
        if ($actor->isAdmin() && $this->scopedTasksQuery($actor)->whereKey($task->id)->exists()) {
            return;
        }
        abort(403);
    }

    private function ensureCanAssign(User $actor, ?int $assigneeId): void
    {
        if ($assigneeId === null) {
            return;
        }

        $target = User::query()->findOrFail($assigneeId);
        if ($actor->isSuperAdmin()) {
            return;
        }
        if ($actor->id === $assigneeId) {
            return;
        }
        if ($actor->isAdmin()) {
            $this->ensureActorCanAccessTargetUser($actor, $target);

            return;
        }

        $actorGroupIds = $actor->groups()->pluck('groups.id');
        if (! $target->groups()->whereIn('groups.id', $actorGroupIds)->exists()) {
            abort(403, '担当者に指定できるのは同一グループのユーザーのみです。');
        }
    }

    private function ensureActorCanAccessTargetUser(User $actor, User $target): void
    {
        if ($actor->isSuperAdmin()) {
            return;
        }

        $actorGroupIds = $actor->groups()->pluck('groups.id')->all();
        if (! $target->groups()->whereIn('groups.id', $actorGroupIds)->exists()) {
            abort(403, '対象ユーザーへのアクセスが許可されていません。');
        }
    }
}
