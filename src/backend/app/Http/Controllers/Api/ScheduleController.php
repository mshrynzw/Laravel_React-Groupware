<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Schedule\StoreScheduleRequest;
use App\Http\Requests\Schedule\UpdateScheduleRequest;
use App\Models\Schedule;
use App\Models\User;
use App\Support\AuditLogger;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
            'user_id' => ['sometimes', 'integer', 'exists:users,id'],
        ]);

        /** @var User $actor */
        $actor = $request->user();
        $targetUserId = $this->resolveTargetUserId($actor, (int) ($validated['user_id'] ?? $actor->id));

        $from = CarbonImmutable::parse($validated['from'])->utc();
        $to = CarbonImmutable::parse($validated['to'])->endOfDay()->utc();

        $rows = Schedule::query()
            ->where('user_id', $targetUserId)
            ->where('start_at', '<', $to)
            ->where('end_at', '>', $from)
            ->orderBy('start_at')
            ->get();

        return response()->json(['data' => $rows]);
    }

    public function store(StoreScheduleRequest $request): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $ownerId = (int) ($request->validated('user_id') ?? $actor->id);
        $this->ensureCanManageScheduleForUser($actor, $ownerId);

        $validated = $request->validated();

        $schedule = Schedule::create([
            'user_id' => $ownerId,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'start_at' => $validated['start_at'],
            'end_at' => $validated['end_at'],
            'all_day' => (bool) ($validated['all_day'] ?? false),
        ]);

        AuditLogger::log($request, 'schedule.created', $schedule, [
            'title' => $schedule->title,
        ]);

        return response()->json([
            'message' => '予定を作成しました。',
            'data' => $schedule,
        ], 201);
    }

    public function show(Request $request, Schedule $schedule): JsonResponse
    {
        $this->ensureCanViewSchedule($request->user(), $schedule);

        return response()->json($schedule);
    }

    public function update(UpdateScheduleRequest $request, Schedule $schedule): JsonResponse
    {
        $this->ensureCanMutateSchedule($request->user(), $schedule);

        $validated = $request->validated();
        $schedule->fill(collect($validated)->only([
            'title', 'description', 'start_at', 'end_at', 'all_day',
        ])->all());
        if ($schedule->end_at < $schedule->start_at) {
            abort(422, '終了時刻は開始時刻以上にしてください。');
        }
        $schedule->save();

        AuditLogger::log($request, 'schedule.updated', $schedule, []);

        return response()->json([
            'message' => '予定を更新しました。',
            'data' => $schedule,
        ]);
    }

    public function destroy(Request $request, Schedule $schedule): JsonResponse
    {
        $this->ensureCanMutateSchedule($request->user(), $schedule);

        $id = $schedule->id;
        $schedule->delete();

        AuditLogger::log($request, 'schedule.deleted', null, ['deleted_schedule_id' => $id]);

        return response()->json(['message' => '予定を削除しました。']);
    }

    private function resolveTargetUserId(User $actor, int $requestedUserId): int
    {
        if ($requestedUserId === $actor->id) {
            return $actor->id;
        }

        if (! in_array($actor->role, [User::ROLE_SUPERADMIN, User::ROLE_ADMIN], true)) {
            abort(403, '他ユーザーの予定を参照できません。');
        }

        $target = User::query()->findOrFail($requestedUserId);
        $this->ensureActorCanAccessTargetUser($actor, $target);

        return $target->id;
    }

    private function ensureCanManageScheduleForUser(User $actor, int $ownerId): void
    {
        if ($ownerId === $actor->id) {
            return;
        }
        if (! in_array($actor->role, [User::ROLE_SUPERADMIN, User::ROLE_ADMIN], true)) {
            abort(403, '他ユーザーの予定を作成できません。');
        }
        $target = User::query()->findOrFail($ownerId);
        $this->ensureActorCanAccessTargetUser($actor, $target);
    }

    private function ensureCanViewSchedule(User $actor, Schedule $schedule): void
    {
        if ($schedule->user_id === $actor->id) {
            return;
        }
        if (! in_array($actor->role, [User::ROLE_SUPERADMIN, User::ROLE_ADMIN], true)) {
            abort(404);
        }
        $target = $schedule->owner;
        if (! $target) {
            abort(404);
        }
        $this->ensureActorCanAccessTargetUser($actor, $target);
    }

    private function ensureCanMutateSchedule(User $actor, Schedule $schedule): void
    {
        if ($schedule->user_id === $actor->id) {
            return;
        }
        if (! in_array($actor->role, [User::ROLE_SUPERADMIN, User::ROLE_ADMIN], true)) {
            abort(403);
        }
        $target = $schedule->owner;
        if (! $target) {
            abort(403);
        }
        $this->ensureActorCanAccessTargetUser($actor, $target);
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
