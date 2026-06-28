<?php

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Any authenticated team member may listen on a conversation channel.
// The customer widget listens on the public `widget.{token}` channel instead —
// public channels need no auth callback; the unguessable token is the credential.
Broadcast::channel('conversation.{conversation}', function (User $user, Conversation $conversation) {
    return $user !== null;
});
