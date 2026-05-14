<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase3AnnouncementTest extends TestCase
{
    use RefreshDatabase;

    public function test_draft_announcement_not_in_public_index(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $member = User::factory()->create(['role' => User::ROLE_MEMBER]);

        Announcement::create([
            'title' => '下書き',
            'body' => '<p>非公開</p>',
            'author_user_id' => $admin->id,
            'published_at' => null,
            'updated_by' => $admin->id,
        ]);

        $this->actingAs($member);
        $res = $this->getJson('/api/announcements');
        $res->assertOk();
        $this->assertCount(0, $res->json('data'));
    }

    public function test_member_cannot_post_announcement(): void
    {
        $member = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $this->actingAs($member);

        $this->postJson('/api/announcements', [
            'title' => '不正',
            'body' => '<p>x</p>',
            'published_at' => now()->toIso8601String(),
        ])->assertForbidden();
    }

    public function test_scheduled_announcement_not_in_public_index(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $member = User::factory()->create(['role' => User::ROLE_MEMBER]);

        Announcement::create([
            'title' => '予約',
            'body' => '<p>未来</p>',
            'author_user_id' => $admin->id,
            'published_at' => now()->addDay(),
            'updated_by' => $admin->id,
        ]);

        $this->actingAs($member);
        $this->getJson('/api/announcements')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_admin_sees_draft_in_admin_list(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        Announcement::create([
            'title' => '下書き',
            'body' => '<p>x</p>',
            'author_user_id' => $admin->id,
            'published_at' => null,
            'updated_by' => $admin->id,
        ]);

        $this->actingAs($admin);
        $this->getJson('/api/admin/announcements')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_public_show_returns_404_for_draft_when_member(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $member = User::factory()->create(['role' => User::ROLE_MEMBER]);

        $a = Announcement::create([
            'title' => '下書き',
            'body' => '<p>x</p>',
            'author_user_id' => $admin->id,
            'published_at' => null,
            'updated_by' => $admin->id,
        ]);

        $this->actingAs($member);
        $this->getJson("/api/announcements/{$a->id}")->assertNotFound();
    }

    public function test_dangerous_body_returns_422(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->actingAs($admin);

        $this->postJson('/api/announcements', [
            'title' => 'x',
            'body' => '<a href="javascript:alert(1)">bad</a>',
            'published_at' => now()->toIso8601String(),
        ])->assertUnprocessable();
    }

    public function test_put_updates_announcement_like_patch(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->actingAs($admin);

        $create = $this->postJson('/api/announcements', [
            'title' => '元',
            'body' => '<p>old</p>',
            'published_at' => now()->subMinute()->toIso8601String(),
        ])->assertCreated();

        $id = (int) $create->json('data.id');

        $this->putJson("/api/announcements/{$id}", [
            'title' => 'PUT更新',
            'body' => '<p>new</p>',
            'published_at' => now()->subMinute()->toIso8601String(),
        ])->assertOk();

        $this->getJson("/api/announcements/{$id}")
            ->assertOk()
            ->assertJsonPath('title', 'PUT更新');
    }
}
