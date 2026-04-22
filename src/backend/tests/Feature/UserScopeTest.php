<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_cannot_access_user_outside_assigned_groups(): void
    {
        $groupA = Group::create(['name' => 'A']);
        $groupB = Group::create(['name' => 'B']);

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $admin->groups()->sync([$groupA->id]);

        $outsideUser = User::factory()->create();
        $outsideUser->groups()->sync([$groupB->id]);

        $this->actingAs($admin);

        $this->getJson("/api/users/{$outsideUser->id}")
            ->assertForbidden();
    }
}
