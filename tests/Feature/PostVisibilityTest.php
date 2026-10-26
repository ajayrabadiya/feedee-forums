<?php

namespace Tests\Feature;

use App\Enums\PostStatus;
use App\Models\Post;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_existing_and_new_posts_are_approved_when_approval_is_off(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('posts.store'), [
            'title' => 'Instant post', 'topic' => 'Recipes', 'agree' => '1',
        ])->assertSessionHas('status', 'Thanks for sharing! Your post has been published.');

        $this->assertSame(PostStatus::Approved, Post::sole()->status);
    }

    public function test_new_customer_posts_wait_for_review_when_approval_is_on(): void
    {
        Setting::set(Setting::REQUIRE_POST_APPROVAL, '1');
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('posts.store'), [
            'title' => 'Needs review', 'topic' => 'Recipes', 'agree' => '1',
        ])->assertSessionHas('status', 'Thanks for sharing! Your post will appear in the feed once a moderator approves it.');

        $this->assertSame(PostStatus::Pending, Post::sole()->status);
    }

    public function test_admin_posts_skip_the_review_queue(): void
    {
        Setting::set(Setting::REQUIRE_POST_APPROVAL, '1');

        $this->actingAs(User::factory()->admin()->create())->post(route('posts.store'), [
            'title' => 'Admin announcement', 'topic' => 'The Lounge', 'agree' => '1',
        ]);

        $this->assertSame(PostStatus::Approved, Post::sole()->status);
    }

    public function test_feed_only_shows_approved_posts(): void
    {
        Post::factory()->create(['title' => 'Approved post']);
        Post::factory()->pending()->create(['title' => 'Pending post']);
        Post::factory()->rejected()->create(['title' => 'Rejected post']);

        $this->get(route('home'))
            ->assertSee('Approved post')
            ->assertDontSee('Pending post')
            ->assertDontSee('Rejected post');
    }

    public function test_featured_posts_are_pinned_at_the_top_of_the_first_page(): void
    {
        Post::factory()->create(['title' => 'Brand new regular post', 'created_at' => now()]);
        Post::factory()->featured()->create(['title' => 'Pinned welcome post', 'created_at' => now()->subDays(3)]);

        $response = $this->get(route('home'));

        $response->assertSeeInOrder(['Featured', 'Featured by moderators', 'Pinned welcome post', 'Today', 'Brand new regular post']);
        $this->assertSame(1, substr_count($response->getContent(), 'Pinned welcome post</a>'));

        $this->get(route('home', ['topic' => $response->viewData('featuredPosts')->first()->topic]))
            ->assertDontSee('Featured by moderators');
    }

    public function test_unapproved_posts_are_hidden_from_the_public_but_visible_to_owner_and_admins(): void
    {
        $owner = User::factory()->create();
        $pending = Post::factory()->for($owner)->pending()->create();
        $rejected = Post::factory()->for($owner)->rejected('Off topic')->create(['rejection_note' => 'Try The Lounge.']);

        foreach ([$pending, $rejected] as $post) {
            $this->get(route('posts.show', $post))->assertNotFound();
            $this->actingAs(User::factory()->create())->get(route('posts.show', $post))->assertNotFound();
            auth()->logout();
        }

        $this->actingAs($owner)->get(route('posts.show', $pending))->assertOk()->assertSee('Waiting for review');
        $this->actingAs($owner)->get(route('posts.show', $rejected))->assertOk()->assertSee('Not approved: Off topic')->assertSee('Try The Lounge.');
        $this->actingAs(User::factory()->admin()->create())->get(route('posts.show', $pending))->assertOk();
    }

    public function test_dashboard_shows_moderation_status_to_the_owner(): void
    {
        $owner = User::factory()->create();
        Post::factory()->for($owner)->pending()->create();
        Post::factory()->for($owner)->rejected('Duplicate post')->create();

        $this->actingAs($owner)->get(route('dashboard'))
            ->assertSee('Waiting for review')
            ->assertSee('Not approved: Duplicate post');
    }

    public function test_owner_editing_a_rejected_post_resubmits_it_for_review(): void
    {
        $owner = User::factory()->create();
        $post = Post::factory()->for($owner)->rejected()->create();

        $this->actingAs($owner)->put(route('posts.update', $post), [
            'title' => 'Fixed title', 'topic' => 'Recipes',
        ])->assertSessionHasNoErrors();

        $post->refresh();
        $this->assertSame(PostStatus::Pending, $post->status);
        $this->assertNull($post->rejection_reason);
    }

    public function test_admin_editing_a_rejected_post_keeps_it_rejected(): void
    {
        $post = Post::factory()->rejected()->create();

        $this->actingAs(User::factory()->admin()->create())->put(route('posts.update', $post), [
            'title' => 'Admin tidy-up', 'topic' => 'Recipes',
        ]);

        $this->assertSame(PostStatus::Rejected, $post->fresh()->status);
    }

    public function test_editing_does_not_change_approval_status_of_approved_posts(): void
    {
        $owner = User::factory()->create();
        $post = Post::factory()->for($owner)->featured()->create();
        Setting::set(Setting::REQUIRE_POST_APPROVAL, '1');

        $this->actingAs($owner)->put(route('posts.update', $post), ['title' => 'Edited', 'topic' => 'Recipes']);

        $post->refresh();
        $this->assertSame(PostStatus::Approved, $post->status);
        $this->assertTrue($post->is_featured);
    }
}
