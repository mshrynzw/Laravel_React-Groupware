<?php

namespace App\Http\Controllers\Api;

use App\Events\ChatMessageSent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\StoreChatMessageRequest;
use App\Models\ChatMessage;
use App\Models\ChatRoom;
use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatMessageController extends Controller
{
    public function index(Request $request, ChatRoom $chat_room): JsonResponse
    {
        $this->ensureMember($request->user(), $chat_room);

        $beforeId = (int) $request->query('before_id', 0);
        $limit = min(100, max(1, (int) $request->query('limit', 50)));

        $query = ChatMessage::query()
            ->where('chat_room_id', $chat_room->id)
            ->with(['author:id,name,email'])
            ->orderByDesc('id');

        if ($beforeId > 0) {
            $query->where('id', '<', $beforeId);
        }

        $messages = $query->limit($limit)->get()->sortBy('id')->values();

        return response()->json(['data' => $messages]);
    }

    public function store(StoreChatMessageRequest $request, ChatRoom $chat_room): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->ensureMember($user, $chat_room);

        $message = ChatMessage::create([
            'chat_room_id' => $chat_room->id,
            'user_id' => $user->id,
            'body' => $request->validated('body'),
        ]);

        $chat_room->touch();

        AuditLogger::log($request, 'chat.message_sent', $message, [
            'chat_room_id' => $chat_room->id,
        ]);

        $message->load('author:id,name,email');

        broadcast(new ChatMessageSent($message));

        return response()->json([
            'message' => '送信しました。',
            'data' => $message,
        ], 201);
    }

    private function ensureMember(?User $user, ChatRoom $chat_room): void
    {
        if (! $user) {
            abort(401);
        }

        if (! $chat_room->users()->where('users.id', $user->id)->exists()) {
            abort(403);
        }
    }
}
