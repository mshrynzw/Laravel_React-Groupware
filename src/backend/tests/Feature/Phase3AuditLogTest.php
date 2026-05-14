<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Phase3AuditLogTest extends TestCase
{
    use RefreshDatabase;

    private const REQ_ID = 'b2c3d4e5-f6a7-41b8-9abc-1234567890cd';

    public function test_announcement_created_audit_has_request_id_in_payload(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->actingAs($admin);

        $this->withHeader('X-Request-Id', self::REQ_ID)->postJson('/api/announcements', [
            'title' => '監査テスト',
            'body' => '<p>本文</p>',
            'published_at' => now()->subMinute()->toIso8601String(),
        ])->assertCreated();

        $log = AuditLog::query()
            ->where('event', 'announcement.created')
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame($admin->id, (int) $log->actor_user_id);
        $this->assertSame(self::REQ_ID, strtolower((string) ($log->payload['request_id'] ?? '')));
        $this->assertNotNull($log->auditable_id);
    }

    public function test_file_uploaded_audit_has_request_id(): void
    {
        Storage::fake('local');

        $user = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $this->actingAs($user);

        $file = UploadedFile::fake()->create('doc.pdf', 50, 'application/pdf');

        $this->withHeader('X-Request-Id', self::REQ_ID)
            ->post('/api/files', ['file' => $file], [
                'Accept' => 'application/json',
                'X-Requested-With' => 'XMLHttpRequest',
            ])
            ->assertCreated();

        $log = AuditLog::query()
            ->where('event', 'file.uploaded')
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame(self::REQ_ID, strtolower((string) ($log->payload['request_id'] ?? '')));
    }

    public function test_announcement_deleted_audit_recorded(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->actingAs($admin);

        $create = $this->postJson('/api/announcements', [
            'title' => '削除対象',
            'body' => '<p>x</p>',
            'published_at' => now()->subMinute()->toIso8601String(),
        ])->assertCreated();

        $id = (int) $create->json('data.id');

        $this->withHeader('X-Request-Id', self::REQ_ID)->deleteJson("/api/announcements/{$id}")->assertOk();

        $log = AuditLog::query()
            ->where('event', 'announcement.deleted')
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame($id, (int) ($log->payload['deleted_announcement_id'] ?? 0));
        $this->assertSame(self::REQ_ID, strtolower((string) ($log->payload['request_id'] ?? '')));
    }
}
