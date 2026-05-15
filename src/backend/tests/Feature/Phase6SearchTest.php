<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\Group;
use App\Models\StoredFile;
use App\Models\Task;
use App\Models\User;
use App\Models\WikiPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase6SearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_search(): void
    {
        $this->getJson('/api/search?q=test')->assertUnauthorized();
    }

    public function test_empty_query_returns_422(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $this->actingAs($user);

        $this->getJson('/api/search')->assertUnprocessable();
        $this->getJson('/api/search?q=')->assertUnprocessable();
    }

    public function test_member_finds_published_announcement_and_wiki(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $member = User::factory()->create(['role' => User::ROLE_MEMBER]);

        Announcement::create([
            'title' => 'VPN 接続手順',
            'body' => '社内 VPN の設定方法です。',
            'author_user_id' => $admin->id,
            'published_at' => now()->subDay(),
            'updated_by' => $admin->id,
        ]);

        Announcement::create([
            'title' => '下書きのお知らせ',
            'body' => 'VPN 下書き',
            'author_user_id' => $admin->id,
            'published_at' => null,
            'updated_by' => $admin->id,
        ]);

        WikiPage::factory()->create([
            'slug' => 'vpn-setup',
            'title' => 'VPN マニュアル',
            'body' => 'VPN 設定',
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $this->actingAs($member);
        $res = $this->getJson('/api/search?q=VPN&type=announcement,wiki')->assertOk();
        $types = collect($res->json('data'))->pluck('type')->unique()->sort()->values()->all();
        $this->assertSame(['announcement', 'wiki'], $types);
        $this->assertFalse(
            collect($res->json('data'))->contains(fn (array $row): bool => $row['title'] === '下書きのお知らせ')
        );
    }

    public function test_admin_can_find_draft_announcement(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        Announcement::create([
            'title' => '未公開 VPN',
            'body' => 'draft',
            'author_user_id' => $admin->id,
            'published_at' => null,
            'updated_by' => $admin->id,
        ]);

        $this->actingAs($admin);
        $this->getJson('/api/search?q=VPN&type=announcement')
            ->assertOk()
            ->assertJsonPath('data.0.title', '未公開 VPN');
    }

    public function test_member_cannot_find_other_users_task(): void
    {
        $creator = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $other = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $viewer = User::factory()->create(['role' => User::ROLE_MEMBER]);

        Task::create([
            'title' => '秘密の VPN タスク',
            'description' => '他人には見えない',
            'status' => Task::STATUS_TODO,
            'due_at' => null,
            'creator_user_id' => $creator->id,
            'assignee_user_id' => $other->id,
            'position' => 0,
        ]);

        $this->actingAs($viewer);
        $this->getJson('/api/search?q=VPN&type=task')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_member_finds_own_task(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_MEMBER]);

        Task::create([
            'title' => '自分の VPN 作業',
            'description' => null,
            'status' => Task::STATUS_TODO,
            'due_at' => null,
            'creator_user_id' => $user->id,
            'assignee_user_id' => null,
            'position' => 0,
        ]);

        $this->actingAs($user);
        $this->getJson('/api/search?q=VPN&type=task')
            ->assertOk()
            ->assertJsonPath('data.0.type', 'task');
    }

    public function test_invalid_type_returns_422(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $this->actingAs($user);

        $this->getJson('/api/search?q=test&type=invalid')->assertStatus(422);
    }

    public function test_member_finds_colleague_in_same_group(): void
    {
        $group = Group::create(['name' => 'G-search', 'description' => null, 'created_by' => null, 'updated_by' => null]);
        $member = User::factory()->create(['role' => User::ROLE_MEMBER, 'name' => '山田 VPN']);
        $colleague = User::factory()->create(['role' => User::ROLE_MEMBER, 'name' => '佐藤 VPN']);
        $outsider = User::factory()->create(['role' => User::ROLE_MEMBER, 'name' => '外部 VPN']);
        $member->groups()->attach($group->id);
        $colleague->groups()->attach($group->id);

        $this->actingAs($member);
        $titles = collect($this->getJson('/api/search?q=VPN&type=user')->json('data'))->pluck('title')->all();
        $this->assertContains('山田 VPN', $titles);
        $this->assertContains('佐藤 VPN', $titles);
        $this->assertNotContains('外部 VPN', $titles);
    }

    public function test_member_finds_own_file_by_name(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_MEMBER]);
        StoredFile::create([
            'user_id' => $user->id,
            'original_name' => 'vpn-manual.pdf',
            'disk' => 'local',
            'path' => 'uploads/test/vpn.pdf',
            'size' => 100,
            'mime_type' => 'application/pdf',
            'visibility' => 'private',
        ]);

        $this->actingAs($user);
        $this->getJson('/api/search?q=vpn&type=file')
            ->assertOk()
            ->assertJsonPath('data.0.type', 'file')
            ->assertJsonPath('data.0.title', 'vpn-manual.pdf');
    }
}
