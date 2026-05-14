<?php

use App\Models\ChatRoom;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('chat.{roomId}', function (User $user, string $roomId) {
    $room = ChatRoom::query()->find((int) $roomId);

    if (! $room) {
        return false;
    }

    return $room->users()->where('users.id', $user->id)->exists();
});
