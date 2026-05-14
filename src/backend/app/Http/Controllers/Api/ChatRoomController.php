<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\StoreChatRoomRequest;
use App\Models\ChatRoom;
use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatRoomController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $rooms = $user->chatRooms()
            ->with(['users:id,name,email'])
            ->orderByDesc('chat_rooms.updated_at')
            ->get();

        return response()->json(['data' => $rooms]);
    }

    public function store(StoreChatRoomRequest $request): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $type = $request->validated('type');

        if ($type === ChatRoom::TYPE_GROUP) {
            $room = ChatRoom::create([
                'name' => $request->validated('name'),
                'type' => ChatRoom::TYPE_GROUP,
            ]);
            $room->users()->attach($actor->id);
        } else {
            $otherId = (int) $request->validated('participant_user_id');
            $other = User::query()->findOrFail($otherId);
            $this->ensureActorCanAccessTargetUser($actor, $other);

            $room = ChatRoom::query()
                ->where('type', ChatRoom::TYPE_DIRECT)
                ->whereHas('users', fn ($q) => $q->where('users.id', $actor->id))
                ->whereHas('users', fn ($q) => $q->where('users.id', $otherId))
                ->first();

            if (! $room) {
                $room = ChatRoom::create([
                    'name' => null,
                    'type' => ChatRoom::TYPE_DIRECT,
                ]);
                $room->users()->sync([$actor->id, $otherId]);
            }
        }

        AuditLogger::log($request, 'chat.room_created', $room, [
            'type' => $room->type,
        ]);

        return response()->json([
            'message' => 'ルームを用意しました。',
            'data' => $room->load('users:id,name,email'),
        ], 201);
    }

    private function ensureActorCanAccessTargetUser(User $actor, User $target): void
    {
        if ($actor->isSuperAdmin()) {
            return;
        }

        $actorGroupIds = $actor->groups()->pluck('groups.id')->all();
        if (! $target->groups()->whereIn('groups.id', $actorGroupIds)->exists()) {
            abort(403, '対象ユーザーへのアクセスが許可されていません。');
        }
    }
}
