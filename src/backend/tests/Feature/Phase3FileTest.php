<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\StoredFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Phase3FileTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_cannot_download_other_users_file(): void
    {
        Storage::fake('local');

        $owner = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $other = User::factory()->create(['role' => User::ROLE_MEMBER]);

        $path = 'uploads/test/doc.pdf';
        Storage::disk('local')->put($path, 'binary');

        $file = StoredFile::create([
            'user_id' => $owner->id,
            'original_name' => 'doc.pdf',
            'disk' => 'local',
            'path' => $path,
            'size' => 7,
            'mime_type' => 'application/pdf',
            'visibility' => 'private',
        ]);

        $this->actingAs($other);
        $this->get("/api/files/{$file->id}/download")->assertNotFound();
    }

    public function test_oversized_upload_returns_422(): void
    {
        Storage::fake('local');

        $user = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $this->actingAs($user);

        $big = UploadedFile::fake()->create('big.pdf', 11000, 'application/pdf');

        $this->actingAs($user)->post('/api/files', [
            'file' => $big,
        ], [
            'Accept' => 'application/json',
            'X-Requested-With' => 'XMLHttpRequest',
        ])->assertUnprocessable();
    }

    public function test_member_can_upload_and_list_own_file(): void
    {
        Storage::fake('local');

        $user = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $this->actingAs($user);

        $upload = UploadedFile::fake()->create('note.pdf', 100, 'application/pdf');

        $res = $this->post('/api/files', [
            'file' => $upload,
        ])->assertCreated();

        $id = (int) $res->json('data.id');
        $this->assertDatabaseHas('files', ['id' => $id, 'user_id' => $user->id]);

        $list = $this->getJson('/api/files')->assertOk();
        $this->assertGreaterThanOrEqual(1, count($list->json('data')));
    }

    public function test_admin_can_list_file_from_same_group_member(): void
    {
        Storage::fake('local');

        $group = Group::create([
            'name' => 'G-Phase3-Files',
            'description' => null,
            'created_by' => null,
            'updated_by' => null,
        ]);

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $member = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $admin->groups()->attach($group->id);
        $member->groups()->attach($group->id);

        $path = 'uploads/test/m.pdf';
        Storage::disk('local')->put($path, 'x');

        StoredFile::create([
            'user_id' => $member->id,
            'original_name' => 'm.pdf',
            'disk' => 'local',
            'path' => $path,
            'size' => 1,
            'mime_type' => 'application/pdf',
            'visibility' => 'private',
        ]);

        $this->actingAs($admin);
        $this->getJson('/api/files')->assertOk()->assertJsonCount(1, 'data');
    }
}
