<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use Carbon\CarbonImmutable;

class AttendanceSummaryService
{
    private const TZ = 'Asia/Tokyo';

    /**
     * @param  list<AttendanceRecord>  $records  ordered by recorded_at asc, then id asc
     * @return array{period: array{from: string, to: string}, days: list<array{date: string, work_minutes: int, status: string}>, total_work_minutes: int}
     */
    public function summarizeFromRecords(string $fromDate, string $toDate, array $records): array
    {
        $from = CarbonImmutable::parse($fromDate, self::TZ)->startOfDay();
        $to = CarbonImmutable::parse($toDate, self::TZ)->startOfDay();

        $days = [];
        $cursor = $from;
        while ($cursor->lte($to)) {
            $key = $cursor->toDateString();
            $days[$key] = [
                'date' => $key,
                'work_minutes' => 0,
                'status' => 'none',
            ];
            $cursor = $cursor->addDay();
        }

        $openIn = null;
        foreach ($records as $record) {
            if ($record->type === AttendanceRecord::TYPE_CLOCK_IN) {
                $openIn = $record;
            } elseif ($record->type === AttendanceRecord::TYPE_CLOCK_OUT && $openIn !== null) {
                $inTokyo = $openIn->recorded_at->clone()->timezone(self::TZ);
                $outTokyo = $record->recorded_at->clone()->timezone(self::TZ);
                $dayKey = $inTokyo->toDateString();
                $minutes = max(0, (int) floor($inTokyo->diffInSeconds($outTokyo, true) / 60));
                if (isset($days[$dayKey])) {
                    $days[$dayKey]['work_minutes'] += $minutes;
                    $days[$dayKey]['status'] = 'complete';
                }
                $openIn = null;
            }
        }

        if ($openIn !== null) {
            $dayKey = $openIn->recorded_at->clone()->timezone(self::TZ)->toDateString();
            if (isset($days[$dayKey])) {
                $days[$dayKey]['status'] = 'incomplete';
            }
        }

        $dayList = array_values($days);
        $total = (int) array_sum(array_column($dayList, 'work_minutes'));

        return [
            'period' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ],
            'days' => $dayList,
            'total_work_minutes' => $total,
        ];
    }

    /**
     * @return list<AttendanceRecord>
     */
    public function recordsForSummary(int $userId, CarbonImmutable $fromStartTokyo, CarbonImmutable $toEndTokyo): array
    {
        $fromUtc = $fromStartTokyo->clone()->utc();
        $toUtc = $toEndTokyo->clone()->utc();

        return AttendanceRecord::query()
            ->where('user_id', $userId)
            ->whereBetween('recorded_at', [$fromUtc, $toUtc])
            ->orderBy('recorded_at')
            ->orderBy('id')
            ->get()
            ->all();
    }
}
