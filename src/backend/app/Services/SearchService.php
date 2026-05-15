<?php

namespace App\Services;

use App\Models\Announcement;
use App\Models\StoredFile;
use App\Models\Task;
use App\Models\User;
use App\Models\WikiPage;
use App\Services\Search\ElasticsearchSearchAdapter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class SearchService
{
    private const MAX_PER_PAGE = 30;

    /** @var list<string> */
    public const ALL_TYPES = ['announcement', 'wiki', 'task', 'user', 'file'];

    public function __construct(
        private readonly ElasticsearchSearchAdapter $elasticsearch,
    ) {}

    /**
     * @param  list<string>  $types
     * @return array{data: list<array<string, mixed>>, meta: array<string, int>, engine?: string}
     */
    public function search(User $user, string $query, array $types, int $page, int $perPage): array
    {
        $perPage = min(self::MAX_PER_PAGE, max(1, $perPage));
        $page = max(1, $page);
        $types = $types === [] ? self::ALL_TYPES : array_values(array_intersect($types, self::ALL_TYPES));

        $engine = 'database';
        $hits = null;

        if (ElasticsearchSearchAdapter::isConfigured()) {
            $esHits = $this->elasticsearch->search($user, $query, $types);
            if ($esHits !== null) {
                $hits = collect($esHits);
                $engine = 'elasticsearch';
            }
        }

        if ($hits === null) {
            $hits = $this->collectDatabaseHits($user, $query, $types);
        }

        $sorted = $hits->sortByDesc(fn (array $row): int => $row['_sort_ts'])->values();
        $total = $sorted->count();
        $slice = $sorted->slice(($page - 1) * $perPage, $perPage)->map(function (array $row): array {
            unset($row['_sort_ts']);

            return $row;
        })->values()->all();

        return [
            'data' => $slice,
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => max(1, (int) ceil($total / $perPage)),
            ],
            'engine' => $engine,
        ];
    }

    /**
     * @param  list<string>  $types
     */
    private function collectDatabaseHits(User $user, string $query, array $types): \Illuminate\Support\Collection
    {
        $hits = collect();
        if (in_array('announcement', $types, true)) {
            $hits = $hits->merge($this->searchAnnouncements($user, $query));
        }
        if (in_array('wiki', $types, true)) {
            $hits = $hits->merge($this->searchWiki($user, $query));
        }
        if (in_array('task', $types, true)) {
            $hits = $hits->merge($this->searchTasks($user, $query));
        }
        if (in_array('user', $types, true)) {
            $hits = $hits->merge($this->searchUsers($user, $query));
        }
        if (in_array('file', $types, true)) {
            $hits = $hits->merge($this->searchFiles($user, $query));
        }

        return $hits;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function searchAnnouncements(User $user, string $query): array
    {
        $q = Announcement::query()->select(['id', 'title', 'body', 'published_at', 'updated_at']);

        if (! $this->isAnnouncementAdmin($user)) {
            $q->publishedForList();
        }

        $this->applyTextMatch($q, ['title', 'body'], $query);

        return $q->limit(50)->get()->map(function (Announcement $row) use ($query): array {
            $snippetSource = $row->body !== '' ? $row->body : $row->title;

            return [
                'type' => 'announcement',
                'id' => $row->id,
                'title' => $row->title,
                'snippet' => $this->makeSnippet($snippetSource, $query),
                'url' => '/announcements/'.$row->id,
                '_sort_ts' => ($row->updated_at ?? $row->published_at ?? now())->getTimestamp(),
            ];
        })->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function searchWiki(User $user, string $query): array
    {
        unset($user);

        $q = WikiPage::query()->select(['id', 'slug', 'title', 'body', 'updated_at']);
        $this->applyTextMatch($q, ['title', 'body'], $query);

        return $q->limit(50)->get()->map(function (WikiPage $row) use ($query): array {
            return [
                'type' => 'wiki',
                'id' => $row->id,
                'title' => $row->title,
                'snippet' => $this->makeSnippet($row->body !== '' ? $row->body : $row->title, $query),
                'url' => '/wiki/view/'.rawurlencode($row->slug),
                '_sort_ts' => $row->updated_at->getTimestamp(),
            ];
        })->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function searchTasks(User $user, string $query): array
    {
        $q = $this->scopedTasksQuery($user)->select(['id', 'title', 'description', 'updated_at']);
        $this->applyTextMatch($q, ['title', 'description'], $query);

        return $q->limit(50)->get()->map(function (Task $row) use ($query): array {
            $snippetSource = $row->description !== null && $row->description !== ''
                ? $row->description
                : $row->title;

            return [
                'type' => 'task',
                'id' => $row->id,
                'title' => $row->title,
                'snippet' => $this->makeSnippet($snippetSource, $query),
                'url' => '/tasks',
                '_sort_ts' => $row->updated_at->getTimestamp(),
            ];
        })->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function searchUsers(User $actor, string $query): array
    {
        $q = User::query()->select(['id', 'name', 'email', 'updated_at']);

        if (! $actor->isSuperAdmin()) {
            $groupIds = $actor->groups()->pluck('groups.id')->all();
            $q->whereHas('groups', fn ($gq) => $gq->whereIn('groups.id', $groupIds));
        }

        $this->applyTextMatch($q, ['name', 'email'], $query);

        return $q->limit(50)->get()->map(function (User $row) use ($query): array {
            return [
                'type' => 'user',
                'id' => $row->id,
                'title' => $row->name,
                'snippet' => $this->makeSnippet($row->email, $query),
                'url' => '/users',
                '_sort_ts' => $row->updated_at?->getTimestamp() ?? time(),
            ];
        })->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function searchFiles(User $actor, string $query): array
    {
        $q = $this->scopedFilesQuery($actor)->select(['id', 'original_name', 'mime_type', 'updated_at']);
        $this->applyTextMatch($q, ['original_name', 'mime_type'], $query);

        return $q->limit(50)->get()->map(function (StoredFile $row) use ($query): array {
            return [
                'type' => 'file',
                'id' => $row->id,
                'title' => $row->original_name,
                'snippet' => $this->makeSnippet((string) ($row->mime_type ?? ''), $query),
                'url' => '/files',
                '_sort_ts' => $row->updated_at?->getTimestamp() ?? time(),
            ];
        })->all();
    }

    /**
     * @param  Builder<Announcement>|Builder<WikiPage>|Builder<Task>|Builder<User>|Builder<StoredFile>  $query
     * @param  list<string>  $columns
     */
    private function applyTextMatch(Builder $query, array $columns, string $term): void
    {
        $term = trim($term);
        if ($term === '') {
            $query->whereRaw('0 = 1');

            return;
        }

        if (DB::connection()->getDriverName() === 'pgsql') {
            $vector = implode(" || ' ' || ", array_map(
                fn (string $col): string => "coalesce({$col}, '')",
                $columns
            ));
            $query->whereRaw(
                "to_tsvector('simple', {$vector}) @@ plainto_tsquery('simple', ?)",
                [$term]
            );

            return;
        }

        $pattern = '%'.addcslashes($term, '%_\\').'%';
        $query->where(function ($q) use ($columns, $pattern): void {
            foreach ($columns as $column) {
                $q->orWhere($column, 'like', $pattern);
            }
        });
    }

    private function makeSnippet(string $text, string $query, int $radius = 60): string
    {
        $plain = preg_replace('/\s+/u', ' ', strip_tags($text)) ?? '';
        $plain = trim($plain);
        if ($plain === '') {
            return '';
        }

        $lowerPlain = mb_strtolower($plain);
        $lowerQuery = mb_strtolower($query);
        $pos = mb_strpos($lowerPlain, $lowerQuery);

        if ($pos === false) {
            $excerpt = mb_substr($plain, 0, 140);

            return mb_strlen($plain) > 140 ? $excerpt.'…' : $excerpt;
        }

        $start = max(0, $pos - $radius);
        $length = $radius * 2 + mb_strlen($query);
        $excerpt = mb_substr($plain, $start, $length);
        $prefix = $start > 0 ? '…' : '';
        $suffix = ($start + mb_strlen($excerpt)) < mb_strlen($plain) ? '…' : '';

        return $prefix.$excerpt.$suffix;
    }

    private function isAnnouncementAdmin(User $user): bool
    {
        return in_array($user->role, [User::ROLE_ADMIN, User::ROLE_SUPERADMIN], true);
    }

    /**
     * @return Builder<Task>
     */
    private function scopedTasksQuery(User $actor): Builder
    {
        $query = Task::query();

        if ($actor->isSuperAdmin()) {
            return $query;
        }

        if ($actor->isAdmin()) {
            $groupIds = $actor->groups()->pluck('groups.id')->all();

            return $query->where(function ($q) use ($actor, $groupIds): void {
                $q->where('creator_user_id', $actor->id)
                    ->orWhere('assignee_user_id', $actor->id);
                if ($groupIds !== []) {
                    $q->orWhereHas('creator', fn ($uq) => $uq->whereHas('groups', fn ($gq) => $gq->whereIn('groups.id', $groupIds)))
                        ->orWhereHas('assignee', fn ($uq) => $uq->whereHas('groups', fn ($gq) => $gq->whereIn('groups.id', $groupIds)));
                }
            });
        }

        return $query->where(function ($q) use ($actor): void {
            $q->where('creator_user_id', $actor->id)
                ->orWhere('assignee_user_id', $actor->id);
        });
    }

    /**
     * @return Builder<StoredFile>
     */
    private function scopedFilesQuery(User $actor): Builder
    {
        $query = StoredFile::query();

        if ($actor->isSuperAdmin()) {
            return $query;
        }

        if ($actor->isAdmin()) {
            $groupIds = $actor->groups()->pluck('groups.id')->all();

            return $query->where(function ($q) use ($actor, $groupIds): void {
                $q->where('user_id', $actor->id);
                if ($groupIds !== []) {
                    $q->orWhereHas('owner', function ($uq) use ($groupIds): void {
                        $uq->whereHas('groups', fn ($gq) => $gq->whereIn('groups.id', $groupIds));
                    });
                }
            });
        }

        return $query->where('user_id', $actor->id);
    }
}
