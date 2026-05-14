<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RequestIdMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_response_includes_x_request_id(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->getJson('/api/me');
        $response->assertOk();
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            (string) $response->headers->get('X-Request-Id')
        );
    }

    public function test_api_accepts_client_x_request_id_when_valid(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $custom = 'a1b2c3d4-e5f6-41a7-89ab-1234567890ab';

        $response = $this->getJson('/api/me', ['X-Request-Id' => $custom]);
        $response->assertOk();
        $this->assertSame(strtolower($custom), strtolower((string) $response->headers->get('X-Request-Id')));
    }
}
