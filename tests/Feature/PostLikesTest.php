<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\PostLike;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostLikesTest extends TestCase
{
    use RefreshDatabase;

    private function like(Post $post, User $user): void
    {
        $like = new PostLike;
        $like->post_id = $post->id;
        $like->user_id = $user->id;
        $like->save();
    }

    public function test_member_can_like_and_unlike_a_post(): void
    {
        $member = User::factory()->create();
        $post = Post::factory()->create();

        $this->actingAs($member)->postJson(route('posts.like', $post))
            ->assertOk()
            ->assertExactJson(['liked' => true, 'count' => 1]);

        $this->assertDatabaseHas('post_likes', ['post_id' => $post->id, 'user_id' => $member->id]);

        $this->actingAs($member)->postJson(route('posts.like', $post))
            ->assertOk()
            ->assertExactJson(['liked' => false, 'count' => 0]);

        $this->assertSame(0, PostLike::count());
    }

    public function test_each_member_counts_once(): void
    {
        $post = Post::factory()->create();
        $this->like($post, User::factory()->create());
        $this->like($post, User::factory()->create());

        $this->actingAs(User::factory()->create())->postJson(route('posts.like', $post))
            ->assertExactJson(['liked' => true, 'count' => 3]);
    }

    public function test_like_works_without_javascript(): void
    {
        $member = User::factory()->create();
        $post = Post::factory()->create();

        $this->actingAs($member)
            ->from(route('posts.show', $post))
            ->post(route('posts.like', $post))
            ->assertRedirect(route('posts.show', $post));

        $this->assertSame(1, $post->likes()->count());
    }

    public function test_guest_cannot_like_and_sees_login_link(): void
    {
        $post = Post::factory()->create();

        $this->postJson(route('posts.like', $post))->assertUnauthorized();
        $this->post(route('posts.like', $post))->assertRedirect(route('login'));
        $this->assertSame(0, PostLike::count());

        $this->get(route('posts.show', $post))
            ->assertSee(e(route('login', ['redirect' => '/p/'.$post->public_id])), false)
            ->assertSee('Log in to like this post');
    }

    public function test_pending_and_rejected_posts_cannot_be_liked(): void
    {
        $owner = User::factory()->create();

        foreach ([Post::factory()->for($owner)->pending()->create(), Post::factory()->for($owner)->rejected()->create()] as $post) {
            $this->actingAs($owner)->postJson(route('posts.like', $post))->assertForbidden();
            $this->actingAs($owner)->get(route('posts.show', $post))->assertDontSee('like-btn', false);
        }

        $this->assertSame(0, PostLike::count());
    }

    public function test_post_page_shows_count_and_liked_state(): void
    {
        $member = User::factory()->create();
        $post = Post::factory()->create();
        $this->like($post, $member);
        $this->like($post, User::factory()->create());

        $this->actingAs($member)->get(route('posts.show', $post))
            ->assertSee('action="'.route('posts.like', $post).'"', false)
            ->assertSee('class="like-btn  is-liked" aria-pressed="true"', false)
            ->assertSee('<span class="like-count">2</span>', false)
            ->assertSee('Unlike this post (2 likes)');

        $this->actingAs(User::factory()->create())->get(route('posts.show', $post))
            ->assertSee('aria-pressed="false"', false)
            ->assertSee('Like this post (2 likes)');
    }

    public function test_feed_cards_show_like_buttons_with_counts(): void
    {
        $member = User::factory()->create();
        $liked = Post::factory()->create(['title' => 'Liked post']);
        $other = Post::factory()->create(['title' => 'Other post']);
        $this->like($liked, $member);

        $response = $this->actingAs($member)->get(route('home'));

        $response->assertSee('action="'.route('posts.like', $liked).'"', false);
        $response->assertSee('action="'.route('posts.like', $other).'"', false);
        $response->assertSee('class="like-btn sm is-liked" aria-pressed="true"', false);
        $response->assertSee('class="like-btn sm" aria-pressed="false"', false);
    }

    public function test_likes_are_removed_with_the_post_or_the_member(): void
    {
        $member = User::factory()->create();
        $post = Post::factory()->create();
        $this->like($post, $member);
        $this->like(Post::factory()->create(), $member);

        $post->delete();
        $this->assertSame(1, PostLike::count());

        $member->delete();
        $this->assertSame(0, PostLike::count());
    }
}
