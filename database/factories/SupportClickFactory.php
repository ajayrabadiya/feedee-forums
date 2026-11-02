<?php

namespace Database\Factories;

use App\Models\SupportClick;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupportClick>
 */
class SupportClickFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'page_path' => fake()->randomElement(['/', '/rules', '/contact', '/p/'.fake()->bothify('??##??##')]),
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'clicked_at' => now(),
        ];
    }

    /**
     * Indicate that a signed-in member clicked the button.
     */
    public function byMember(?User $user = null): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user ?? User::factory(),
        ]);
    }
}
