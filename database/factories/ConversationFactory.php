<?php

namespace Database\Factories;

use App\Enums\ConversationStatus;
use App\Models\Conversation;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Conversation>
 */
class ConversationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'assigned_to' => null,
            'status' => fake()->randomElement(ConversationStatus::cases()),
            'channel' => 'web',
            'subject' => fake()->optional()->sentence(4),
            'token' => (string) Str::uuid(),
            'last_message_at' => fake()->dateTimeBetween('-1 week'),
        ];
    }

    public function status(ConversationStatus $status): static
    {
        return $this->state(fn () => ['status' => $status]);
    }
}
