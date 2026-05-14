<?php

namespace Tests\Unit;

use App\Models\AttendanceRecord;
use App\Models\User;
use App\Services\AttendanceSummaryService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceSummaryServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_summarize_pairs_clock_in_and_clock_out(): void
    {
        $user = User::factory()->create();
        $service = new AttendanceSummaryService;

        $in = CarbonImmutable::parse('2026-05-10 01:00:00', 'UTC');
        $out = CarbonImmutable::parse('2026-05-10 10:00:00', 'UTC');

        $records = [
            new AttendanceRecord([
                'user_id' => $user->id,
                'type' => AttendanceRecord::TYPE_CLOCK_IN,
                'recorded_at' => $in,
                'source' => 'test',
            ]),
            new AttendanceRecord([
                'user_id' => $user->id,
                'type' => AttendanceRecord::TYPE_CLOCK_OUT,
                'recorded_at' => $out,
                'source' => 'test',
            ]),
        ];

        $summary = $service->summarizeFromRecords('2026-05-01', '2026-05-31', $records);
        $this->assertSame(540, $summary['total_work_minutes']);
        $day = collect($summary['days'])->firstWhere('date', '2026-05-10');
        $this->assertNotNull($day);
        $this->assertSame(540, $day['work_minutes']);
        $this->assertSame('complete', $day['status']);
    }

    public function test_summarize_marks_incomplete_when_clock_in_unclosed(): void
    {
        $user = User::factory()->create();
        $service = new AttendanceSummaryService;

        $in = CarbonImmutable::parse('2026-05-12 02:00:00', 'UTC');
        $records = [
            new AttendanceRecord([
                'user_id' => $user->id,
                'type' => AttendanceRecord::TYPE_CLOCK_IN,
                'recorded_at' => $in,
                'source' => 'test',
            ]),
        ];

        $summary = $service->summarizeFromRecords('2026-05-01', '2026-05-31', $records);
        $day = collect($summary['days'])->firstWhere('date', '2026-05-12');
        $this->assertNotNull($day);
        $this->assertSame('incomplete', $day['status']);
        $this->assertSame(0, $day['work_minutes']);
    }
}
