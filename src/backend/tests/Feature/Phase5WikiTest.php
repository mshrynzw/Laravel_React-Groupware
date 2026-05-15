<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use App\Models\WikiPage;
use App\Models\WikiRevision;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase5WikiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_list_wiki_pages(): void
    {
        $this->getJson('/api/wiki/pages')->assertUnauthorized();
    }

    public function test_guest_cannot_access_wiki_tree_or_revisions(): void
    {
        $this->getJson('/api/wiki/pages/tree')->assertUnauthorized();

        $page = WikiPage::factory()->create();
        $this->getJson("/api/wiki/pages/{$page->id}/revisions")->assertUnauthorized();
    }

    public function test_member_can_fetch_wiki_tree(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $member = User::factory()->create(['role' => User::ROLE_MEMBER]);

        $root = WikiPage::factory()->create([
            'slug' => 'root-p',
            'title' => 'Root',
            'parent_id' => null,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);
        WikiPage::factory()->create([
            'slug' => 'child-p',
            'title' => 'Child',
            'parent_id' => $root->id,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $this->actingAs($member);
        $this->getJson('/api/wiki/pages/tree')
            ->assertOk()
            ->assertJsonPath('data.0.slug', 'root-p')
            ->assertJsonPath('data.0.children.0.slug', 'child-p');
    }

    public function test_admin_update_persists_revision_and_audit(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $page = WikiPage::factory()->create([
            'slug' => 'rev-page',
            'title' => 'T0',
            'body' => 'B0',
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);
        $page->refresh();
        $ts = $page->updated_at->toIso8601String();

        $this->actingAs($admin);
        $this->putJson("/api/wiki/pages/{$page->id}", [
            'title' => 'T1',
            'body' => 'B1',
            'updated_at' => $ts,
        ])->assertOk();

        $this->assertDatabaseHas('wiki_revisions', [
            'wiki_page_id' => $page->id,
            'title' => 'T0',
            'body' => 'B0',
            'editor_user_id' => $admin->id,
        ]);

        $log = AuditLog::query()->where('event', 'wiki.revision_saved')->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertSame($page->id, $log->payload['wiki_page_id'] ?? null);
    }

    public function test_admin_cannot_set_parent_to_descendant(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $parent = WikiPage::factory()->create([
            'slug' => 'p-tree',
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);
        $child = WikiPage::factory()->create([
            'slug' => 'c-tree',
            'parent_id' => $parent->id,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);
        $parent->refresh();
        $ts = $parent->updated_at->toIso8601String();

        $this->actingAs($admin);
        $this->putJson("/api/wiki/pages/{$parent->id}", [
            'parent_id' => $child->id,
            'updated_at' => $ts,
        ])->assertStatus(422);
    }

    public function test_member_can_list_revisions(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $member = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $page = WikiPage::factory()->create([
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        WikiRevision::query()->create([
            'wiki_page_id' => $page->id,
            'title' => 'Old',
            'body' => 'x',
            'editor_user_id' => $admin->id,
        ]);

        $this->actingAs($member);
        $this->getJson("/api/wiki/pages/{$page->id}/revisions")
            ->assertOk()
            ->assertJsonPath('data.0.title', 'Old');
    }

    public function test_show_revision_wrong_page_returns_404(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $a = WikiPage::factory()->create(['created_by' => $admin->id, 'updated_by' => $admin->id]);
        $b = WikiPage::factory()->create(['created_by' => $admin->id, 'updated_by' => $admin->id]);
        $rev = WikiRevision::query()->create([
            'wiki_page_id' => $a->id,
            'title' => 'x',
            'body' => 'y',
            'editor_user_id' => $admin->id,
        ]);

        $this->actingAs($admin);
        $this->getJson("/api/wiki/pages/{$b->id}/revisions/{$rev->id}")->assertNotFound();
    }

    public function test_delete_wiki_page_cascades_revisions(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $page = WikiPage::factory()->create([
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);
        $rev = WikiRevision::query()->create([
            'wiki_page_id' => $page->id,
            'title' => 'x',
            'body' => 'y',
            'editor_user_id' => $admin->id,
        ]);

        $this->actingAs($admin);
        $this->deleteJson("/api/wiki/pages/{$page->id}")->assertOk();
        $this->assertDatabaseMissing('wiki_revisions', ['id' => $rev->id]);
    }

    public function test_member_can_list_and_view_by_slug(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $member = User::factory()->create(['role' => User::ROLE_MEMBER]);

        WikiPage::factory()->create([
            'slug' => 'handbook',
            'title' => '手順',
            'body' => '# 手順',
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $this->actingAs($member);
        $this->getJson('/api/wiki/pages')->assertOk()->assertJsonPath('data.0.slug', 'handbook');
        $this->getJson('/api/wiki/pages/by-slug/handbook')->assertOk()->assertJsonPath('title', '手順');
    }

    public function test_member_cannot_create_wiki_page(): void
    {
        $member = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $this->actingAs($member);

        $this->postJson('/api/wiki/pages', [
            'slug' => 'x',
            'title' => 'X',
            'body' => 'y',
        ])->assertForbidden();
    }

    public function test_admin_can_create_wiki_page_and_audit_logged(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->actingAs($admin);

        $this->postJson('/api/wiki/pages', [
            'slug' => 'onboarding',
            'title' => 'オンボーディング',
            'body' => "# ようこそ\n",
        ])->assertCreated();

        $this->assertDatabaseHas('wiki_pages', ['slug' => 'onboarding']);

        $log = AuditLog::query()->where('event', 'wiki.created')->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertSame('onboarding', $log->payload['slug'] ?? null);
    }

    public function test_duplicate_slug_returns_422(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        WikiPage::factory()->create(['slug' => 'dup', 'created_by' => $admin->id, 'updated_by' => $admin->id]);

        $this->actingAs($admin);
        $this->postJson('/api/wiki/pages', [
            'slug' => 'dup',
            'title' => '二重',
            'body' => 'x',
        ])->assertUnprocessable();
    }

    public function test_update_with_stale_updated_at_returns_409(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $page = WikiPage::factory()->create([
            'slug' => 'lock-test',
            'title' => 'A',
            'body' => 'b',
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $page->refresh();
        $stale = $page->updated_at->toIso8601String();

        $this->actingAs($admin);
        $this->travel(2)->seconds();

        $this->putJson("/api/wiki/pages/{$page->id}", [
            'title' => 'B',
            'updated_at' => $stale,
        ])->assertOk();

        $this->putJson("/api/wiki/pages/{$page->id}", [
            'title' => 'C',
            'updated_at' => $stale,
        ])->assertStatus(409);
    }

    public function test_unknown_slug_returns_404(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $this->actingAs($user);
        $this->getJson('/api/wiki/pages/by-slug/no-such-page')->assertNotFound();
    }

    public function test_member_cannot_delete_wiki_page(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $member = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $page = WikiPage::factory()->create([
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $this->actingAs($member);
        $this->deleteJson("/api/wiki/pages/{$page->id}")->assertForbidden();
    }

    public function test_admin_can_delete_wiki_page(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $page = WikiPage::factory()->create([
            'slug' => 'to-delete',
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $this->actingAs($admin);
        $this->deleteJson("/api/wiki/pages/{$page->id}")->assertOk();
        $this->assertDatabaseMissing('wiki_pages', ['id' => $page->id]);

        $log = AuditLog::query()->where('event', 'wiki.deleted')->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertSame('to-delete', $log->payload['slug'] ?? null);
    }
}
