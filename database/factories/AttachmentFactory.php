<?php

namespace Database\Factories;

use App\Models\Attachment;
use App\Models\Message;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attachment>
 */
class AttachmentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->word().'.png';

        return [
            'message_id' => Message::factory(),
            'disk' => 'public',
            'path' => 'attachments/'.fake()->uuid().'/'.$name,
            'name' => $name,
            'mime_type' => 'image/png',
            'size' => fake()->numberBetween(1024, 5_000_000),
        ];
    }
}
