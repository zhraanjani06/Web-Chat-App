<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\Conversation;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('presence-chat.{conversationId}', function ($user, $conversationId) {
    $conversation = Conversation::find($conversationId);
    if ($conversation && $conversation->users->contains($user->id)) {
        return ['id' => $user->id, 'name' => $user->name];
    }
    return false;
});
