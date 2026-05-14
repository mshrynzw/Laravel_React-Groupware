<?php

namespace Tests\Unit;

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnnouncementPublishedScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_for_list_excludes_draft_and_future(): void
    {
        $user = User::factory()->create();

        Announcement::create([
            'title' => 'draft',
            'body' => 'x',
            'author_user_id' => $user->id,
            'published_at' => null,
            'updated_by' => $user->id,
        ]);
        Announcement::create([
            'title' => 'future',
            'body' => 'x',
            'author_user_id' => $user->id,
            'published_at' => now()->addDay(),
            'updated_by' => $user->id,
        ]);
        $pub = Announcement::create([
            'title' => 'pub',
            'body' => 'x',
            'author_user_id' => $user->id,
            'published_at' => now()->subMinute(),
            'updated_by' => $user->id,
        ]);

        $ids = Announcement::query()->publishedForList()->pluck('id')->all();
        $this->assertSame([$pub->id], $ids);
    }
}
