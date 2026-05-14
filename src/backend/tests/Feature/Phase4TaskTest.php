<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase4TaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_invalid_status_returns_422(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $this->actingAs($user);

        $this->postJson('/api/tasks', [
            'title' => 'x',
            'status' => 'bogus',
        ])->assertUnprocessable();
    }

    public function test_assignee_cannot_delete_task(): void
    {
        $creator = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $assignee = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $group = Group::create(['name' => 'G1', 'description' => null, 'created_by' => null, 'updated_by' => null]);
        $creator->groups()->attach($group->id);
        $assignee->groups()->attach($group->id);

        $task = Task::create([
            'title' => '共有',
            'description' => null,
            'status' => Task::STATUS_TODO,
            'due_at' => null,
            'creator_user_id' => $creator->id,
            'assignee_user_id' => $assignee->id,
            'position' => 0,
        ]);

        $this->actingAs($assignee);
        $this->deleteJson("/api/tasks/{$task->id}")->assertForbidden();
    }

    public function test_creator_can_delete_task(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $task = Task::create([
            'title' => 'x',
            'description' => null,
            'status' => Task::STATUS_TODO,
            'due_at' => null,
            'creator_user_id' => $user->id,
            'assignee_user_id' => null,
            'position' => 0,
        ]);

        $this->actingAs($user);
        $this->deleteJson("/api/tasks/{$task->id}")->assertOk();
        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }
}
