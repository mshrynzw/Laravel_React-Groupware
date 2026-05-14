<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase3CrossCutTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_get_announcements_returns_401(): void
    {
        $this->getJson('/api/announcements')->assertUnauthorized();
    }

    public function test_guest_get_files_returns_401(): void
    {
        $this->getJson('/api/files')->assertUnauthorized();
    }

    public function test_phase3_list_uses_paginate_shape(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $this->actingAs($user);
        $res = $this->getJson('/api/announcements')->assertOk();
        $res->assertJsonStructure(['data', 'links', 'current_page', 'per_page', 'total']);
    }

    public function test_phase3_response_includes_x_request_id(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $this->actingAs($user);
        $res = $this->getJson('/api/announcements');
        $res->assertOk();
        $this->assertNotEmpty($res->headers->get('X-Request-Id'));
    }
}
