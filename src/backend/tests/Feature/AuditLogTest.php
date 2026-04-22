<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_failure_creates_audit_log(): void
    {
        User::factory()->create([
            'email' => 'exists@example.com',
            'password' => 'password123',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'exists@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(422);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'auth.login_failed',
        ]);
    }

    public function test_group_creation_creates_audit_log(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_SUPERADMIN]);
        $this->actingAs($admin);

        $response = $this->postJson('/api/groups', [
            'name' => '監査対象グループ',
            'description' => 'desc',
        ]);

        $response->assertCreated();

        $groupId = $response->json('group.id');
        $log = AuditLog::query()->where('event', 'group.created')->latest('id')->first();

        $this->assertNotNull($log);
        $this->assertSame($groupId, $log->auditable_id);
    }
}
