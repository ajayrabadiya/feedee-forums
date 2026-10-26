<?php

namespace Database\Factories;

use App\Enums\MediaType;
use App\Models\Post;
use App\Models\PostMedia;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PostMedia>
 */
class PostMediaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'post_id' => Post::factory(),
            'media_type' => MediaType::Image,
            'disk' => config('media.disk'),
            'file_path' => MediaType::Image->directory().'/'.Str::random(40).'.jpg',
            'original_name' => 'photo.jpg',
            'alt_text' => fake()->sentence(4),
            'mime_type' => 'image/jpeg',
            'file_size' => fake()->numberBetween(10_000, 2_000_000),
        ];
    }

    /**
     * Indicate that the media item is a video.
     */
    public function video(): static
    {
        return $this->state(fn (array $attributes) => [
            'media_type' => MediaType::Video,
            'file_path' => MediaType::Video->directory().'/'.Str::random(40).'.mp4',
            'original_name' => 'clip.mp4',
            'alt_text' => null,
            'mime_type' => 'video/mp4',
        ]);
    }
}
