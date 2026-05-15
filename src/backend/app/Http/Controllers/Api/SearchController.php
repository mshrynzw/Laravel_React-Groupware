<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SearchController extends Controller
{
    public function __construct(
        private readonly SearchService $searchService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'max:200'],
            'type' => ['sometimes', 'string', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:30'],
        ]);

        /** @var User $user */
        $user = $request->user();

        $types = [];
        if (! empty($validated['type'])) {
            $types = array_values(array_filter(array_map(
                trim(...),
                explode(',', (string) $validated['type'])
            )));
            $invalid = array_diff($types, SearchService::ALL_TYPES);
            if ($invalid !== []) {
                return response()->json([
                    'message' => 'type は '.implode(', ', SearchService::ALL_TYPES).' のいずれかです。',
                ], 422);
            }
        }

        $payload = DB::connection()->transaction(function () use ($user, $validated, $types): array {
            if (DB::connection()->getDriverName() === 'pgsql') {
                DB::statement('SET LOCAL statement_timeout = 2000');
            }

            return $this->searchService->search(
                $user,
                $validated['q'],
                $types,
                (int) ($validated['page'] ?? 1),
                (int) ($validated['per_page'] ?? 20),
            );
        });

        return response()->json($payload);
    }
}
