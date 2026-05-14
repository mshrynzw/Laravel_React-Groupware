<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\Group;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_clock_in_returns_201(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->postJson('/api/attendance/clock-in')
            ->assertCreated()
            ->assertJsonPath('data.type', 'clock_in');

        $this->assertDatabaseHas('attendance_records', [
            'user_id' => $user->id,
            'type' => AttendanceRecord::TYPE_CLOCK_IN,
        ]);
    }

    public function test_double_clock_in_returns_422(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->postJson('/api/attendance/clock-in')->assertCreated();
        $this->postJson('/api/attendance/clock-in')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['code']);
    }

    public function test_clock_out_without_clock_in_returns_422(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->postJson('/api/attendance/clock-out')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['code']);
    }

    public function test_clock_out_after_clock_in_succeeds(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->postJson('/api/attendance/clock-in')->assertCreated();
        $this->postJson('/api/attendance/clock-out')->assertCreated();

        $this->assertDatabaseCount('attendance_records', 2);
    }

    public function test_summary_for_month_totals_work_minutes(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $in = CarbonImmutable::parse('2026-01-30 10:00:00', 'UTC');
        $out = CarbonImmutable::parse('2026-01-30 18:00:00', 'UTC');

        AttendanceRecord::create([
            'user_id' => $user->id,
            'type' => AttendanceRecord::TYPE_CLOCK_IN,
            'recorded_at' => $in,
            'source' => 'test',
        ]);
        AttendanceRecord::create([
            'user_id' => $user->id,
            'type' => AttendanceRecord::TYPE_CLOCK_OUT,
            'recorded_at' => $out,
            'source' => 'test',
        ]);

        $res = $this->getJson('/api/attendance/summary?month=2026-01')
            ->assertOk()
            ->json('data');

        $this->assertSame('2026-01-01', $res['period']['from']);
        $this->assertSame('2026-01-31', $res['period']['to']);
        $this->assertGreaterThan(0, $res['total_work_minutes']);
    }

    public function test_admin_can_query_member_records_in_shared_group(): void
    {
        $group = Group::create(['name' => '勤怠共有']);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $member = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $admin->groups()->sync([$group->id]);
        $member->groups()->sync([$group->id]);

        AttendanceRecord::create([
            'user_id' => $member->id,
            'type' => AttendanceRecord::TYPE_CLOCK_IN,
            'recorded_at' => CarbonImmutable::parse('2026-05-01 01:00:00', 'UTC'),
            'source' => 'test',
        ]);

        $this->actingAs($admin);
        $this->getJson('/api/attendance/records?from=2026-05-01&to=2026-05-31&user_id='.$member->id)
            ->assertOk()
            ->assertJsonPath('data.0.user_id', $member->id);
    }

    public function test_member_cannot_query_other_user_records(): void
    {
        $group = Group::create(['name' => 'G1']);
        $member = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $other = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $member->groups()->sync([$group->id]);
        $other->groups()->sync([$group->id]);

        $this->actingAs($member);
        $this->getJson('/api/attendance/records?from=2026-05-01&to=2026-05-31&user_id='.$other->id)
            ->assertForbidden();
    }
}
