<?php

namespace Database\Factories;

use App\Enums\PostStatus;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->sentence(6),
            'topic' => fake()->randomElement(Post::TOPICS),
            'body' => fake()->paragraphs(2, true),
        ];
    }

    /**
     * Indicate that the post is waiting for moderation.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => ['status' => PostStatus::Pending]);
    }

    /**
     * Indicate that a moderator rejected the post.
     */
    public function rejected(string $reason = 'Spam or self-promotion'): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PostStatus::Rejected,
            'rejection_reason' => $reason,
        ]);
    }

    /**
     * Indicate that the post is featured at the top of the feed.
     */
    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PostStatus::Approved,
            'is_featured' => true,
            'featured_at' => now(),
        ]);
    }
}
