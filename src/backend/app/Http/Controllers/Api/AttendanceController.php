<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\User;
use App\Services\AttendanceClockService;
use App\Services\AttendanceSummaryService;
use App\Support\AuditLogger;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AttendanceController extends Controller
{
    private const TZ = 'Asia/Tokyo';

    public function __construct(
        private readonly AttendanceClockService $clockService,
        private readonly AttendanceSummaryService $summaryService,
    ) {}

    public function clockIn(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $result = $this->clockService->clockIn($user, $request->string('source')->toString() ?: null);
        if (! $result['ok']) {
            throw $this->clockError($result['code']);
        }

        /** @var AttendanceRecord $record */
        $record = $result['record'];
        AuditLogger::log($request, 'attendance.clock_in', $record, [
            'user_id' => $user->id,
            'recorded_at' => $record->recorded_at->toIso8601String(),
        ]);

        return response()->json([
            'message' => '出勤を記録しました。',
            'data' => $this->serializeRecord($record),
        ], 201);
    }

    public function clockOut(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $result = $this->clockService->clockOut($user, $request->string('source')->toString() ?: null);
        if (! $result['ok']) {
            throw $this->clockError($result['code']);
        }

        /** @var AttendanceRecord $record */
        $record = $result['record'];
        AuditLogger::log($request, 'attendance.clock_out', $record, [
            'user_id' => $user->id,
            'recorded_at' => $record->recorded_at->toIso8601String(),
        ]);

        return response()->json([
            'message' => '退勤を記録しました。',
            'data' => $this->serializeRecord($record),
        ], 201);
    }

    public function records(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from' => ['required', 'date_format:Y-m-d'],
            'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
            'user_id' => ['sometimes', 'integer', 'exists:users,id'],
        ]);

        /** @var User $actor */
        $actor = $request->user();
        $targetUserId = $this->resolveTargetUserId($actor, (int) ($validated['user_id'] ?? $actor->id));

        $fromStart = CarbonImmutable::parse($validated['from'], self::TZ)->startOfDay();
        $toEnd = CarbonImmutable::parse($validated['to'], self::TZ)->endOfDay();
        $fromUtc = $fromStart->utc();
        $toUtc = $toEnd->utc();

        $query = AttendanceRecord::query()
            ->where('user_id', $targetUserId)
            ->whereBetween('recorded_at', [$fromUtc, $toUtc])
            ->orderBy('recorded_at')
            ->orderBy('id');

        return response()->json($query->paginate((int) $request->query('per_page', 50)));
    }

    public function summary(Request $request): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        if ($request->filled('month')) {
            $validated = $request->validate([
                'month' => ['required', 'regex:/^\d{4}-\d{2}$/'],
                'user_id' => ['sometimes', 'integer', 'exists:users,id'],
            ]);
            $fromDate = $validated['month'].'-01';
            $toDate = CarbonImmutable::parse($fromDate, self::TZ)->endOfMonth()->toDateString();
        } else {
            $validated = $request->validate([
                'from' => ['required', 'date_format:Y-m-d'],
                'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
                'user_id' => ['sometimes', 'integer', 'exists:users,id'],
            ]);
            $fromDate = $validated['from'];
            $toDate = $validated['to'];
        }

        $targetUserId = $this->resolveTargetUserId($actor, (int) ($validated['user_id'] ?? $actor->id));

        $fromStartTokyo = CarbonImmutable::parse($fromDate, self::TZ)->startOfDay();
        $toEndTokyo = CarbonImmutable::parse($toDate, self::TZ)->endOfDay();

        $records = $this->summaryService->recordsForSummary($targetUserId, $fromStartTokyo, $toEndTokyo);
        $payload = $this->summaryService->summarizeFromRecords(
            CarbonImmutable::parse($fromDate, self::TZ)->toDateString(),
            CarbonImmutable::parse($toDate, self::TZ)->toDateString(),
            $records,
        );

        return response()->json([
            'data' => $payload,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeRecord(AttendanceRecord $record): array
    {
        return [
            'id' => $record->id,
            'type' => $record->type,
            'recorded_at' => $record->recorded_at->toIso8601String(),
            'source' => $record->source,
        ];
    }

    private function resolveTargetUserId(User $actor, int $requestedUserId): int
    {
        if ($requestedUserId === $actor->id) {
            return $actor->id;
        }

        if (! in_array($actor->role, [User::ROLE_SUPERADMIN, User::ROLE_ADMIN], true)) {
            abort(403, '他ユーザーの勤怠を参照できません。');
        }

        $target = User::query()->findOrFail($requestedUserId);
        $this->ensureActorCanAccessTargetUser($actor, $target);

        return $target->id;
    }

    private function ensureActorCanAccessTargetUser(User $actor, User $target): void
    {
        if ($actor->isSuperAdmin()) {
            return;
        }

        $actorGroupIds = $actor->groups()->pluck('groups.id')->all();
        $inScope = $target->groups()->whereIn('groups.id', $actorGroupIds)->exists();
        if (! $inScope) {
            abort(403, '対象ユーザーへのアクセスが許可されていません。');
        }
    }

    private function clockError(string $code): ValidationException
    {
        $messages = [
            'already_clocked_in' => ['既に出勤打刻が未完了です。退勤してから再度出勤してください。'],
            'clock_out_without_clock_in' => ['出勤打刻がないため退勤できません。'],
        ];

        return ValidationException::withMessages([
            'code' => $messages[$code] ?? ['打刻できません。'],
        ]);
    }
}
