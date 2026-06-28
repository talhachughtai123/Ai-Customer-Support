<?php

namespace App\Contracts\Repositories;

use App\Models\Conversation;
use App\Models\Message;

interface MessageRepositoryInterface
{
    /**
     * Persist a new message and bump the conversation's last_message_at.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function create(Conversation $conversation, array $attributes): Message;

    /**
     * Mark all unread customer messages in the conversation as read.
     *
     * @return int number of messages updated
     */
    public function markCustomerMessagesRead(Conversation $conversation): int;
}
