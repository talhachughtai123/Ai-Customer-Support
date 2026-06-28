<?php

namespace Database\Factories;

use App\Enums\MessageSenderType;
use App\Enums\MessageType;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Message>
 */
class MessageFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'conversation_id' => Conversation::factory(),
            'sender_type' => MessageSenderType::Customer,
            'sender_id' => null,
            'type' => MessageType::Text,
            'body' => fake()->sentence(),
            'read_at' => null,
        ];
    }

    public function fromCustomer(): static
    {
        return $this->state(fn () => [
            'sender_type' => MessageSenderType::Customer,
            'sender_id' => null,
        ]);
    }

    public function fromAgent(int $userId): static
    {
        return $this->state(fn () => [
            'sender_type' => MessageSenderType::Agent,
            'sender_id' => $userId,
            'read_at' => now(),
        ]);
    }

    public function read(): static
    {
        return $this->state(fn () => ['read_at' => now()]);
    }
}
