<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\File\StoreFileRequest;
use App\Models\StoredFile;
use App\Models\User;
use App\Services\FileStorageService;
use App\Support\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileController extends Controller
{
    public function __construct(
        private readonly FileStorageService $fileStorageService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        $query = StoredFile::query()
            ->with(['owner:id,name,email'])
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if ($actor->isSuperAdmin()) {
            // 全件
        } elseif ($actor->isAdmin()) {
            $groupIds = $actor->groups()->pluck('groups.id')->all();
            $query->where(function ($q) use ($actor, $groupIds): void {
                $q->where('user_id', $actor->id);
                if ($groupIds !== []) {
                    $q->orWhereHas('owner', function ($uq) use ($groupIds): void {
                        $uq->whereHas('groups', fn ($gq) => $gq->whereIn('groups.id', $groupIds));
                    });
                }
            });
        } else {
            $query->where('user_id', $actor->id);
        }

        return response()->json($query->paginate((int) $request->query('per_page', 15)));
    }

    public function store(StoreFileRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $uploaded = $request->file('file');
        if (! $uploaded) {
            abort(422, 'ファイルが指定されていません。');
        }

        $stored = $this->fileStorageService->store($user, $uploaded);

        AuditLogger::log($request, 'file.uploaded', $stored, [
            'original_name' => $stored->original_name,
            'size' => $stored->size,
            'mime_type' => $stored->mime_type,
        ]);

        return response()->json([
            'message' => 'ファイルをアップロードしました。',
            'data' => $this->serializeFile($stored),
        ], 201);
    }

    public function download(Request $request, StoredFile $stored_file): StreamedResponse|RedirectResponse
    {
        $this->ensureCanAccessFile($request->user(), $stored_file);

        $disk = Storage::disk($stored_file->disk);

        if (! $disk->exists($stored_file->path)) {
            abort(404, 'ファイルが見つかりません。');
        }

        if ($stored_file->disk === 's3' && method_exists($disk, 'temporaryUrl')) {
            $url = $disk->temporaryUrl($stored_file->path, now()->addMinutes(5));

            return redirect()->away($url);
        }

        return $disk->response($stored_file->path, $stored_file->original_name, [
            'Content-Type' => $stored_file->mime_type,
        ]);
    }

    public function destroy(Request $request, StoredFile $stored_file): JsonResponse
    {
        $this->ensureCanAccessFile($request->user(), $stored_file);

        $id = $stored_file->id;
        $this->fileStorageService->deleteFromDisk($stored_file);
        $stored_file->delete();

        AuditLogger::log($request, 'file.deleted', null, [
            'deleted_file_id' => $id,
        ]);

        return response()->json(['message' => 'ファイルを削除しました。']);
    }

    private function ensureCanAccessFile(?User $actor, StoredFile $storedFile): void
    {
        if (! $actor) {
            abort(401);
        }

        if ($actor->id === $storedFile->user_id) {
            return;
        }

        if ($actor->isSuperAdmin()) {
            return;
        }

        if ($actor->isAdmin()) {
            $groupIds = $actor->groups()->pluck('groups.id')->all();
            $owner = $storedFile->owner;
            if ($owner && $owner->groups()->whereIn('groups.id', $groupIds)->exists()) {
                return;
            }
        }

        abort(404);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeFile(StoredFile $f): array
    {
        return [
            'id' => $f->id,
            'original_name' => $f->original_name,
            'size' => $f->size,
            'mime_type' => $f->mime_type,
            'created_at' => $f->created_at?->toIso8601String(),
            'user_id' => $f->user_id,
        ];
    }
}
