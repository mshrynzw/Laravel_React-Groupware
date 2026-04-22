<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManagementValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_cannot_create_group_or_user(): void
    {
        $member = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $this->actingAs($member);

        $this->postJson('/api/groups', [
            'name' => '禁止グループ',
        ])->assertForbidden();

        $this->postJson('/api/users', [
            'name' => 'Nope',
            'email' => 'nope@example.com',
        ])->assertForbidden();
    }

    public function test_admin_cannot_assign_primary_group_outside_user_groups(): void
    {
        $groupA = Group::create(['name' => 'A']);
        $groupB = Group::create(['name' => 'B']);

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $admin->groups()->sync([$groupA->id]);

        $target = User::factory()->create();
        $target->groups()->sync([$groupA->id]);

        $this->actingAs($admin);

        $this->putJson("/api/users/{$target->id}", [
            'name' => $target->name,
            'role' => User::ROLE_MEMBER,
            'group_ids' => [$groupA->id],
            'primary_group_id' => $groupB->id,
        ])->assertStatus(422);
    }
}
