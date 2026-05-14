<?php

namespace Tests\Feature;

use App\Models\Schedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase4ScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_end_before_start_returns_422(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $this->actingAs($user);

        $this->postJson('/api/schedules', [
            'title' => 'bad',
            'start_at' => '2026-05-20T12:00:00Z',
            'end_at' => '2026-05-20T10:00:00Z',
        ])->assertUnprocessable();
    }

    public function test_index_week_range_includes_event_on_boundary_day(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_MEMBER]);
        Schedule::create([
            'user_id' => $user->id,
            'title' => '週末',
            'description' => null,
            'start_at' => '2026-05-16T15:00:00Z',
            'end_at' => '2026-05-16T16:00:00Z',
            'all_day' => false,
        ]);

        $this->actingAs($user);
        $res = $this->getJson('/api/schedules?from=2026-05-10&to=2026-05-16')->assertOk();
        $titles = collect($res->json('data'))->pluck('title')->all();
        $this->assertContains('週末', $titles);
    }

    public function test_index_returns_only_events_in_range(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_MEMBER]);
        Schedule::create([
            'user_id' => $user->id,
            'title' => '内',
            'description' => null,
            'start_at' => '2026-05-10T10:00:00Z',
            'end_at' => '2026-05-10T11:00:00Z',
            'all_day' => false,
        ]);
        Schedule::create([
            'user_id' => $user->id,
            'title' => '外',
            'description' => null,
            'start_at' => '2026-06-01T10:00:00Z',
            'end_at' => '2026-06-01T11:00:00Z',
            'all_day' => false,
        ]);

        $this->actingAs($user);
        $res = $this->getJson('/api/schedules?from=2026-05-01&to=2026-05-31')->assertOk();
        $titles = collect($res->json('data'))->pluck('title')->all();
        $this->assertContains('内', $titles);
        $this->assertNotContains('外', $titles);
    }

    public function test_index_rejects_to_before_from(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $this->actingAs($user);
        $this->getJson('/api/schedules?from=2026-05-10&to=2026-05-05')->assertUnprocessable();
    }
}
