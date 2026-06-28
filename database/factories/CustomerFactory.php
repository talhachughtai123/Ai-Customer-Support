<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Synthetic customers only — never seed real PII (org policy).
 *
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->optional()->e164PhoneNumber(),
            'locale' => fake()->randomElement(['en', 'ar', 'fr']),
            'country' => fake()->countryCode(),
            'avatar' => null,
        ];
    }
}
