<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Announcement\StoreAnnouncementRequest;
use App\Http\Requests\Announcement\UpdateAnnouncementRequest;
use App\Models\Announcement;
use App\Models\User;
use App\Support\AuditLogger;
use App\Support\HtmlSanitizer;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Announcement::query()
            ->publishedForList()
            ->with(['author:id,name,email'])
            ->orderByDesc('published_at')
            ->orderByDesc('id');

        if ($request->filled('q')) {
            $q = '%'.$request->string('q')->toString().'%';
            $query->where('title', 'like', $q);
        }

        return response()->json($query->paginate((int) $request->query('per_page', 15)));
    }

    public function adminIndex(Request $request): JsonResponse
    {
        $this->ensureAnnouncementAdmin($request->user());

        $query = Announcement::query()
            ->with(['author:id,name,email', 'updatedByUser:id,name,email'])
            ->orderByDesc('updated_at')
            ->orderByDesc('id');

        if ($request->filled('q')) {
            $q = '%'.$request->string('q')->toString().'%';
            $query->where('title', 'like', $q);
        }

        return response()->json($query->paginate((int) $request->query('per_page', 15)));
    }

    public function show(Request $request, Announcement $announcement): JsonResponse
    {
        $user = $request->user();
        if (! $this->isPublished($announcement)) {
            if (! $user || ! $this->isAnnouncementAdmin($user)) {
                abort(404);
            }
        }

        return response()->json(
            $announcement->load(['author:id,name,email', 'updatedByUser:id,name,email'])
        );
    }

    public function store(StoreAnnouncementRequest $request): JsonResponse
    {
        $body = HtmlSanitizer::sanitize($request->validated('body'));
        $publishedAt = $request->validated('published_at');
        $published = $publishedAt !== null && $publishedAt !== ''
            ? CarbonImmutable::parse((string) $publishedAt)
            : null;

        $announcement = Announcement::create([
            'title' => $request->validated('title'),
            'body' => $body,
            'author_user_id' => $request->user()->id,
            'published_at' => $published,
            'updated_by' => $request->user()->id,
        ]);

        AuditLogger::log($request, 'announcement.created', $announcement, [
            'title' => $announcement->title,
            'published_at' => $announcement->published_at?->toIso8601String(),
        ]);

        return response()->json([
            'message' => 'お知らせを作成しました。',
            'data' => $announcement->load(['author:id,name,email']),
        ], 201);
    }

    public function update(UpdateAnnouncementRequest $request, Announcement $announcement): JsonResponse
    {
        $beforePublished = $announcement->published_at?->toIso8601String();
        $payload = [];

        if ($request->has('title')) {
            $announcement->title = $request->validated('title');
        }
        if ($request->has('body')) {
            $announcement->body = HtmlSanitizer::sanitize($request->validated('body'));
        }
        if ($request->has('published_at')) {
            $raw = $request->validated('published_at');
            $announcement->published_at = $raw !== null && $raw !== ''
                ? CarbonImmutable::parse((string) $raw)
                : null;
            $payload['published_at'] = ['before' => $beforePublished, 'after' => $announcement->published_at?->toIso8601String()];
        }

        $announcement->updated_by = $request->user()->id;
        $announcement->save();

        $logPayload = array_merge([
            'title' => $announcement->title,
        ], $payload);

        AuditLogger::log($request, 'announcement.updated', $announcement, $logPayload);

        return response()->json([
            'message' => 'お知らせを更新しました。',
            'data' => $announcement->load(['author:id,name,email', 'updatedByUser:id,name,email']),
        ]);
    }

    public function destroy(Request $request, Announcement $announcement): JsonResponse
    {
        $this->ensureAnnouncementAdmin($request->user());

        $id = $announcement->id;
        $announcement->delete();

        AuditLogger::log($request, 'announcement.deleted', null, [
            'deleted_announcement_id' => $id,
        ]);

        return response()->json(['message' => 'お知らせを削除しました。']);
    }

    private function ensureAnnouncementAdmin(?User $user): void
    {
        if (! $user || ! $this->isAnnouncementAdmin($user)) {
            abort(403);
        }
    }

    private function isAnnouncementAdmin(User $user): bool
    {
        return in_array($user->role, [User::ROLE_ADMIN, User::ROLE_SUPERADMIN], true);
    }

    private function isPublished(Announcement $announcement): bool
    {
        return $announcement->published_at !== null
            && $announcement->published_at->lte(now());
    }
}
