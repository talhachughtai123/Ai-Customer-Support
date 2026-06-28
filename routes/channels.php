<?php

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Any authenticated team member may listen on a conversation channel.
// (The public chat widget will use a token-scoped channel in MVP 3c.)
Broadcast::channel('conversation.{conversation}', function (User $user, Conversation $conversation) {
    return $user !== null;
});
