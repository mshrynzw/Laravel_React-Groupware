<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Wiki\StoreWikiPageRequest;
use App\Http\Requests\Wiki\UpdateWikiPageRequest;
use App\Models\User;
use App\Models\WikiPage;
use App\Models\WikiRevision;
use App\Support\AuditLogger;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WikiPageController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min(100, max(1, (int) $request->query('per_page', 20)));

        $query = WikiPage::query()
            ->with(['creator:id,name,email', 'editor:id,name,email'])
            ->orderBy('title');

        if ($request->filled('q')) {
            $q = '%'.$request->string('q')->toString().'%';
            $query->where(function ($qry) use ($q): void {
                $qry->where('title', 'like', $q)->orWhere('slug', 'like', $q);
            });
        }

        return response()->json($query->paginate($perPage));
    }

    /**
     * 階層ナビ用ツリー（親 null をルート）。
     */
    public function tree(Request $request): JsonResponse
    {
        $pages = WikiPage::query()
            ->select(['id', 'slug', 'title', 'parent_id'])
            ->orderBy('title')
            ->get();

        $byParent = [];
        foreach ($pages as $p) {
            $key = $p->parent_id ?? 0;
            if (! isset($byParent[$key])) {
                $byParent[$key] = [];
            }
            $byParent[$key][] = $p;
        }

        $build = function (int $parentKey) use (&$build, &$byParent): array {
            $nodes = $byParent[$parentKey] ?? [];
            $out = [];
            foreach ($nodes as $p) {
                $out[] = [
                    'id' => $p->id,
                    'slug' => $p->slug,
                    'title' => $p->title,
                    'children' => $build((int) $p->id),
                ];
            }

            return $out;
        };

        return response()->json(['data' => $build(0)]);
    }

    public function showBySlug(Request $request, string $slug): JsonResponse
    {
        $page = WikiPage::query()
            ->where('slug', $slug)
            ->with([
                'creator:id,name,email',
                'editor:id,name,email',
                'parent:id,slug,title',
            ])
            ->firstOrFail();

        return response()->json($page);
    }

    public function revisions(Request $request, WikiPage $wiki_page): JsonResponse
    {
        $rows = $wiki_page->revisions()
            ->with('editor:id,name,email')
            ->limit(100)
            ->get();

        return response()->json(['data' => $rows]);
    }

    public function showRevision(Request $request, WikiPage $wiki_page, WikiRevision $wiki_revision): JsonResponse
    {
        if ($wiki_revision->wiki_page_id !== $wiki_page->id) {
            abort(404);
        }

        return response()->json(
            $wiki_revision->load('editor:id,name,email')
        );
    }

    public function store(StoreWikiPageRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $page = WikiPage::create([
            'slug' => $request->validated('slug'),
            'title' => $request->validated('title'),
            'body' => $request->validated('body'),
            'parent_id' => $request->validated('parent_id') ?? null,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        AuditLogger::log($request, 'wiki.created', $page, [
            'slug' => $page->slug,
            'title' => $page->title,
        ]);

        return response()->json([
            'message' => 'Wiki ページを作成しました。',
            'data' => $page->load(['creator:id,name,email', 'editor:id,name,email', 'parent:id,slug,title']),
        ], 201);
    }

    public function update(UpdateWikiPageRequest $request, WikiPage $wiki_page): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $incoming = CarbonImmutable::parse($request->validated('updated_at'));
        $dbSecond = $wiki_page->updated_at?->utc()->format('Y-m-d H:i:s');
        $inSecond = $incoming->utc()->format('Y-m-d H:i:s');
        if ($dbSecond !== $inSecond) {
            return response()->json([
                'message' => '他のユーザーにより更新されています。ページを再読み込みしてください。',
            ], 409);
        }

        if ($request->has('parent_id')) {
            $newParent = $request->validated('parent_id');
            $this->assertParentNotCycle($wiki_page->id, $newParent);
        }

        $previousTitle = $wiki_page->title;
        $previousBody = $wiki_page->body;

        if ($request->has('slug')) {
            $wiki_page->slug = $request->validated('slug');
        }
        if ($request->has('title')) {
            $wiki_page->title = $request->validated('title');
        }
        if ($request->has('body')) {
            $wiki_page->body = $request->validated('body');
        }
        if ($request->has('parent_id')) {
            $wiki_page->parent_id = $request->validated('parent_id');
        }

        $wiki_page->updated_by = $user->id;

        return DB::transaction(function () use ($request, $wiki_page, $user, $previousTitle, $previousBody): JsonResponse {
            if ($wiki_page->isDirty(['slug', 'title', 'body', 'parent_id'])) {
                $rev = WikiRevision::create([
                    'wiki_page_id' => $wiki_page->id,
                    'title' => $previousTitle,
                    'body' => $previousBody,
                    'editor_user_id' => $user->id,
                ]);

                AuditLogger::log($request, 'wiki.revision_saved', $rev, [
                    'wiki_page_id' => $wiki_page->id,
                    'slug' => $wiki_page->slug,
                ]);
            }

            $wiki_page->save();

            AuditLogger::log($request, 'wiki.updated', $wiki_page, [
                'slug' => $wiki_page->slug,
                'title' => $wiki_page->title,
            ]);

            return response()->json([
                'message' => 'Wiki ページを更新しました。',
                'data' => $wiki_page->fresh()->load(['creator:id,name,email', 'editor:id,name,email', 'parent:id,slug,title']),
            ]);
        });
    }

    public function destroy(Request $request, WikiPage $wiki_page): JsonResponse
    {
        $this->ensureWikiAdmin($request->user());

        $id = $wiki_page->id;
        $slug = $wiki_page->slug;
        $wiki_page->delete();

        AuditLogger::log($request, 'wiki.deleted', null, [
            'deleted_wiki_page_id' => $id,
            'slug' => $slug,
        ]);

        return response()->json(['message' => 'Wiki ページを削除しました。']);
    }

    /**
     * 親を自分または子孫にできないことを検証する。
     */
    private function assertParentNotCycle(int $pageId, ?int $newParentId): void
    {
        if ($newParentId === null) {
            return;
        }

        if ($newParentId === $pageId) {
            abort(422, '親ページに自分自身は指定できません。');
        }

        $descendants = $this->collectDescendantIds($pageId);
        if (in_array($newParentId, $descendants, true)) {
            abort(422, '親ページに子孫ページは指定できません。');
        }
    }

    /**
     * @return array<int>
     */
    private function collectDescendantIds(int $rootId): array
    {
        $ids = [];
        $queue = [$rootId];
        while ($queue !== []) {
            $id = array_shift($queue);
            foreach (WikiPage::query()->where('parent_id', $id)->pluck('id') as $childId) {
                $cid = (int) $childId;
                $ids[] = $cid;
                $queue[] = $cid;
            }
        }

        return $ids;
    }

    private function ensureWikiAdmin(?User $user): void
    {
        if (! $user || ! in_array($user->role, [User::ROLE_ADMIN, User::ROLE_SUPERADMIN], true)) {
            abort(403);
        }
    }
}
