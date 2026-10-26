<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use App\Rules\NoUrls;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostTitleNoUrlsTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_post_rejects_a_title_with_a_link(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->from(route('posts.create'))
            ->post(route('posts.store'), [
                'title' => 'Big sale at https://myshop.example.com',
                'topic' => 'Recipes',
                'agree' => '1',
            ]);

        $response->assertRedirect(route('posts.create'));
        $response->assertSessionHasErrors(['title' => "Links aren't allowed in the title. Describe your post in words instead."]);
        $response->assertSessionHasInput('title', 'Big sale at https://myshop.example.com');
        $this->assertSame(0, Post::count());
    }

    public function test_create_post_accepts_a_normal_title(): void
    {
        $this->actingAs(User::factory()->create())->post(route('posts.store'), [
            'title' => 'Learning Node.js and baking 3.5 hour bread',
            'topic' => 'Recipes',
            'agree' => '1',
        ])->assertSessionHasNoErrors();

        $this->assertSame(1, Post::count());
    }

    public function test_edit_post_also_rejects_a_title_with_a_link(): void
    {
        $owner = User::factory()->create();
        $post = Post::factory()->for($owner)->create(['title' => 'Original title']);

        $this->actingAs($owner)->put(route('posts.update', $post), [
            'title' => 'Now with www.spam-link',
            'topic' => 'Recipes',
        ])->assertSessionHasErrors('title');

        $this->assertSame('Original title', $post->fresh()->title);
    }

    public function test_form_exposes_the_shared_pattern_for_instant_feedback(): void
    {
        $this->actingAs(User::factory()->create())->get(route('posts.create'))
            ->assertSee('data-no-urls="'.e(NoUrls::PATTERN).'"', false)
            ->assertSee('id="titleUrlError"', false);
    }
}
