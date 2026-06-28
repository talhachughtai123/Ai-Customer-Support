<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ParticipantTyping implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  string  $side  who is typing: "agent" or "customer". Each client
     *                        shows the indicator only for the opposite side.
     */
    public function __construct(
        public int $conversationId,
        public string $token,
        public string $name,
        public string $side,
    ) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('conversation.'.$this->conversationId),
            new Channel('widget.'.$this->token),
        ];
    }

    public function broadcastAs(): string
    {
        return 'participant.typing';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'name' => $this->name,
            'side' => $this->side,
        ];
    }
}
