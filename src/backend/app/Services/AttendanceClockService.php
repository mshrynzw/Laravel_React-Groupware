<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class AttendanceClockService
{
    public function lastRecord(User $user): ?AttendanceRecord
    {
        return AttendanceRecord::query()
            ->where('user_id', $user->id)
            ->orderByDesc('recorded_at')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * @return array{ok: true, record: AttendanceRecord}|array{ok: false, code: string}
     */
    public function clockIn(User $user, ?string $source = null): array
    {
        $last = $this->lastRecord($user);
        if ($last !== null && $last->type === AttendanceRecord::TYPE_CLOCK_IN) {
            return ['ok' => false, 'code' => 'already_clocked_in'];
        }

        $record = DB::transaction(function () use ($user, $source): AttendanceRecord {
            return AttendanceRecord::create([
                'user_id' => $user->id,
                'type' => AttendanceRecord::TYPE_CLOCK_IN,
                'recorded_at' => CarbonImmutable::now(),
                'source' => $source ?? 'web',
            ]);
        });

        return ['ok' => true, 'record' => $record];
    }

    /**
     * @return array{ok: true, record: AttendanceRecord}|array{ok: false, code: string}
     */
    public function clockOut(User $user, ?string $source = null): array
    {
        $last = $this->lastRecord($user);
        if ($last === null || $last->type !== AttendanceRecord::TYPE_CLOCK_IN) {
            return ['ok' => false, 'code' => 'clock_out_without_clock_in'];
        }

        $record = DB::transaction(function () use ($user, $source): AttendanceRecord {
            return AttendanceRecord::create([
                'user_id' => $user->id,
                'type' => AttendanceRecord::TYPE_CLOCK_OUT,
                'recorded_at' => CarbonImmutable::now(),
                'source' => $source ?? 'web',
            ]);
        });

        return ['ok' => true, 'record' => $record];
    }
}
