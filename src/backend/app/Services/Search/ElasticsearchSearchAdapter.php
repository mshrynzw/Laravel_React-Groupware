<?php

namespace App\Services\Search;

use App\Models\Announcement;
use App\Models\StoredFile;
use App\Models\Task;
use App\Models\User;
use App\Models\WikiPage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ElasticsearchSearchAdapter
{
    public function __construct(
        private readonly SearchDocumentIndexer $indexer,
    ) {}

    public static function isConfigured(): bool
    {
        return config('search.driver') === 'elasticsearch'
            && (string) config('search.elasticsearch.url') !== '';
    }

    public function ping(): bool
    {
        if (! self::isConfigured()) {
            return false;
        }

        try {
            $response = Http::timeout(1)->get(rtrim((string) config('search.elasticsearch.url'), '/'));

            return $response->successful();
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @param  list<string>  $types
     * @return list<array<string, mixed>>|null null = ES 利用不可
     */
    public function search(User $user, string $query, array $types): ?array
    {
        if (! $this->ping()) {
            return null;
        }

        $index = (string) config('search.elasticsearch.index');
        $url = rtrim((string) config('search.elasticsearch.url'), '/')."/{$index}/_search";

        $typeFilter = $types === []
            ? []
            : [['terms' => ['type' => $types]]];

        $body = [
            'size' => 80,
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'multi_match' => [
                                'query' => $query,
                                'fields' => ['title^3', 'body'],
                                'type' => 'best_fields',
                            ],
                        ],
                    ],
                    'filter' => $typeFilter,
                ],
            ],
            'highlight' => [
                'fields' => [
                    'title' => new \stdClass,
                    'body' => ['fragment_size' => 120],
                ],
            ],
        ];

        try {
            $response = Http::timeout((int) config('search.elasticsearch.timeout_seconds', 2))
                ->post($url, $body);
        } catch (\Throwable) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $hits = [];
        foreach ($response->json('hits.hits', []) as $raw) {
            $source = $raw['_source'] ?? [];
            $type = (string) ($source['type'] ?? '');
            $id = (int) ($source['id'] ?? 0);
            if ($type === '' || $id === 0) {
                continue;
            }

            $highlight = $raw['highlight'] ?? [];
            $snippet = $highlight['body'][0] ?? $highlight['title'][0] ?? Str::limit((string) ($source['body'] ?? ''), 140);

            $hit = [
                'type' => $type,
                'id' => $id,
                'title' => (string) ($source['title'] ?? ''),
                'snippet' => strip_tags((string) $snippet),
                'url' => (string) ($source['url'] ?? '/'),
                '_sort_ts' => (int) ($source['sort_ts'] ?? 0),
            ];

            if ($this->canViewHit($user, $hit, $source)) {
                $hits[] = $hit;
            }
        }

        return $hits;
    }

    public function reindex(): bool
    {
        if (! $this->ping()) {
            return false;
        }

        $index = (string) config('search.elasticsearch.index');
        $base = rtrim((string) config('search.elasticsearch.url'), '/');

        Http::timeout(5)->delete("{$base}/{$index}");
        Http::timeout(5)->put("{$base}/{$index}", [
            'settings' => ['number_of_shards' => 1, 'number_of_replicas' => 0],
            'mappings' => [
                'properties' => [
                    'type' => ['type' => 'keyword'],
                    'id' => ['type' => 'integer'],
                    'title' => ['type' => 'text'],
                    'body' => ['type' => 'text'],
                    'url' => ['type' => 'keyword'],
                    'sort_ts' => ['type' => 'long'],
                ],
            ],
        ]);

        $bulk = '';
        foreach ($this->indexer->allDocuments() as $doc) {
            $bulk .= json_encode(['index' => ['_index' => $index, '_id' => $doc['type'].'-'.$doc['id']]], JSON_THROW_ON_ERROR)."\n";
            $bulk .= json_encode($doc, JSON_THROW_ON_ERROR)."\n";
        }

        if ($bulk === '') {
            return true;
        }

        $response = Http::timeout(30)
            ->withHeaders(['Content-Type' => 'application/x-ndjson'])
            ->withBody($bulk, 'application/x-ndjson')
            ->post("{$base}/_bulk");

        return $response->successful();
    }

    /**
     * @param  array<string, mixed>  $hit
     * @param  array<string, mixed>  $source
     */
    private function canViewHit(User $user, array $hit, array $source): bool
    {
        return match ($hit['type']) {
            'announcement' => $this->canViewAnnouncement($user, (int) $hit['id'], (bool) ($source['published'] ?? false)),
            'wiki' => WikiPage::query()->whereKey($hit['id'])->exists(),
            'task' => $this->canViewTask($user, (int) $hit['id']),
            'user' => $this->canViewUser($user, (int) $hit['id']),
            'file' => $this->canViewFile($user, (int) $hit['id']),
            default => false,
        };
    }

    private function canViewAnnouncement(User $user, int $id, bool $published): bool
    {
        if (in_array($user->role, [User::ROLE_ADMIN, User::ROLE_SUPERADMIN], true)) {
            return Announcement::query()->whereKey($id)->exists();
        }

        return $published && Announcement::query()->publishedForList()->whereKey($id)->exists();
    }

    private function canViewTask(User $user, int $id): bool
    {
        $query = Task::query()->whereKey($id);
        if ($user->isSuperAdmin()) {
            return $query->exists();
        }
        if ($user->isAdmin()) {
            $groupIds = $user->groups()->pluck('groups.id')->all();

            return $query->where(function ($q) use ($user, $groupIds): void {
                $q->where('creator_user_id', $user->id)->orWhere('assignee_user_id', $user->id);
                if ($groupIds !== []) {
                    $q->orWhereHas('creator', fn ($uq) => $uq->whereHas('groups', fn ($gq) => $gq->whereIn('groups.id', $groupIds)))
                        ->orWhereHas('assignee', fn ($uq) => $uq->whereHas('groups', fn ($gq) => $gq->whereIn('groups.id', $groupIds)));
                }
            })->exists();
        }

        return $query->where(function ($q) use ($user): void {
            $q->where('creator_user_id', $user->id)->orWhere('assignee_user_id', $user->id);
        })->exists();
    }

    private function canViewUser(User $actor, int $id): bool
    {
        if ($actor->isSuperAdmin()) {
            return User::query()->whereKey($id)->exists();
        }

        $groupIds = $actor->groups()->pluck('groups.id')->all();

        return User::query()->whereKey($id)->whereHas('groups', fn ($q) => $q->whereIn('groups.id', $groupIds))->exists();
    }

    private function canViewFile(User $actor, int $id): bool
    {
        $file = StoredFile::query()->whereKey($id)->first();
        if (! $file) {
            return false;
        }
        if ($actor->isSuperAdmin()) {
            return true;
        }
        if ($file->user_id === $actor->id) {
            return true;
        }
        if ($actor->isAdmin()) {
            $groupIds = $actor->groups()->pluck('groups.id')->all();

            return $file->owner && $file->owner->groups()->whereIn('groups.id', $groupIds)->exists();
        }

        return false;
    }
}
