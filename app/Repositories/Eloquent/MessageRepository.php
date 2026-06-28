<?php

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\MessageRepositoryInterface;
use App\Enums\MessageSenderType;
use App\Models\Conversation;
use App\Models\Message;

class MessageRepository implements MessageRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(Conversation $conversation, array $attributes): Message
    {
        $message = $conversation->messages()->create($attributes);

        $conversation->forceFill(['last_message_at' => $message->created_at])->save();

        return $message;
    }

    public function markCustomerMessagesRead(Conversation $conversation): int
    {
        return $conversation->messages()
            ->where('sender_type', MessageSenderType::Customer->value)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }
}
