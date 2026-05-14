<?php

namespace Tests\Feature;

use App\Http\Requests\Workflow\StoreWorkflowRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkflowPayloadSchemaVersionTest extends TestCase
{
    use RefreshDatabase;

    public function test_request_create_merges_default_schema_version(): void
    {
        $member = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $this->actingAs($member);

        $response = $this->postJson('/api/requests', [
            'type' => 'paid_leave',
            'payload' => [
                'start_date' => '2026-08-01',
                'end_date' => '2026-08-02',
                'reason' => '休暇',
            ],
        ])->assertCreated();

        $payload = $response->json('request.payload');
        $this->assertSame(StoreWorkflowRequest::DEFAULT_PAYLOAD_SCHEMA_VERSION, $payload['schema_version']);
    }

    public function test_request_create_accepts_explicit_schema_version(): void
    {
        $member = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $this->actingAs($member);

        $response = $this->postJson('/api/requests', [
            'type' => 'paid_leave',
            'payload' => [
                'schema_version' => 2,
                'start_date' => '2026-08-10',
                'end_date' => '2026-08-11',
                'reason' => '休暇',
            ],
        ])->assertCreated();

        $this->assertSame(2, $response->json('request.payload.schema_version'));
    }
}
