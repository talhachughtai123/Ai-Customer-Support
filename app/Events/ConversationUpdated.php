<?php

namespace App\Events;

use App\Models\Conversation;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConversationUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Conversation $conversation) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        // Agent inbox (private) + customer widget (public token channel), so a
        // status change such as "closed" reaches the visitor too.
        return [
            new PrivateChannel('conversation.'.$this->conversation->id),
            new Channel('widget.'.$this->conversation->token),
        ];
    }

    public function broadcastAs(): string
    {
        return 'conversation.updated';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->conversation->id,
            'status' => $this->conversation->status->value,
            'assigned_agent' => $this->conversation->assignedAgent
                ? ['id' => $this->conversation->assignedAgent->id, 'name' => $this->conversation->assignedAgent->name]
                : null,
        ];
    }
}
