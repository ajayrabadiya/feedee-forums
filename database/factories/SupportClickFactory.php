<?php

namespace Database\Factories;

use App\Enums\SupportClickFilter;
use App\Enums\SupportPlatform;
use App\Models\IpCountryRange;
use App\Models\SupportClick;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Support button opens by default; use link() for clicks on a payment link.
 *
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
            'visitor_hash' => hash('sha256', fake()->unique()->uuid()),
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

    /**
     * Indicate that the visitor clicked a payment link.
     */
    public function link(SupportPlatform $platform = SupportPlatform::CashApp): static
    {
        return $this->state(fn (array $attributes) => [
            'platform' => $platform,
            'support_url' => $platform === SupportPlatform::CashApp ? 'https://cash.app/$zaiyaxx' : 'https://youpay.me/zaiyaxx',
        ]);
    }

    /**
     * Indicate the visitor's country (null for unknown).
     */
    public function fromCountry(?string $code): static
    {
        return $this->state(fn (array $attributes) => [
            'country_code' => $code,
            'country_name' => IpCountryRange::countryName($code),
        ]);
    }

    /**
     * Indicate that the click is left out of the counts.
     */
    public function filtered(SupportClickFilter $reason = SupportClickFilter::Bot): static
    {
        return $this->state(fn (array $attributes) => [
            'filtered_as' => $reason,
        ]);
    }

    /**
     * Indicate that the same visitor made the click.
     */
    public function byVisitor(string $visitor): static
    {
        return $this->state(fn (array $attributes) => [
            'visitor_hash' => hash('sha256', $visitor),
        ]);
    }
}
