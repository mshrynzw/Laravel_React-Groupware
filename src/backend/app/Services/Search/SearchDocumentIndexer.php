<?php

namespace App\Services\Search;

use App\Models\Announcement;
use App\Models\StoredFile;
use App\Models\Task;
use App\Models\User;
use App\Models\WikiPage;
class SearchDocumentIndexer
{
    /**
     * @return list<array<string, mixed>>
     */
    public function allDocuments(): array
    {
        $docs = [];
        foreach (Announcement::query()->get() as $row) {
            $docs[] = $this->fromAnnouncement($row);
        }
        foreach (WikiPage::query()->get() as $row) {
            $docs[] = $this->fromWiki($row);
        }
        foreach (Task::query()->get() as $row) {
            $docs[] = $this->fromTask($row);
        }
        foreach (User::query()->get() as $row) {
            $docs[] = $this->fromUser($row);
        }
        foreach (StoredFile::query()->get() as $row) {
            $docs[] = $this->fromFile($row);
        }

        return $docs;
    }

    /**
     * @return array<string, mixed>
     */
    public function fromAnnouncement(Announcement $row): array
    {
        return [
            'type' => 'announcement',
            'id' => $row->id,
            'title' => $row->title,
            'body' => $row->body,
            'url' => '/announcements/'.$row->id,
            'sort_ts' => ($row->updated_at ?? $row->published_at ?? now())->getTimestamp(),
            'published' => $row->published_at !== null && $row->published_at->lte(now()),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function fromWiki(WikiPage $row): array
    {
        return [
            'type' => 'wiki',
            'id' => $row->id,
            'title' => $row->title,
            'body' => $row->body,
            'url' => '/wiki/view/'.$row->slug,
            'sort_ts' => $row->updated_at->getTimestamp(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function fromTask(Task $row): array
    {
        return [
            'type' => 'task',
            'id' => $row->id,
            'title' => $row->title,
            'body' => (string) ($row->description ?? ''),
            'url' => '/tasks',
            'sort_ts' => $row->updated_at->getTimestamp(),
            'creator_user_id' => $row->creator_user_id,
            'assignee_user_id' => $row->assignee_user_id,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function fromUser(User $row): array
    {
        return [
            'type' => 'user',
            'id' => $row->id,
            'title' => $row->name,
            'body' => $row->email,
            'url' => '/users',
            'sort_ts' => $row->updated_at?->getTimestamp() ?? time(),
            'group_ids' => $row->groups()->pluck('groups.id')->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function fromFile(StoredFile $row): array
    {
        return [
            'type' => 'file',
            'id' => $row->id,
            'title' => $row->original_name,
            'body' => (string) ($row->mime_type ?? ''),
            'url' => '/files',
            'sort_ts' => $row->updated_at?->getTimestamp() ?? time(),
            'owner_user_id' => $row->user_id,
            'group_ids' => User::query()->find($row->user_id)?->groups()->pluck('groups.id')->all() ?? [],
        ];
    }
}
