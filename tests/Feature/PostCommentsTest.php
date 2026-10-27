<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostCommentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_page_lists_comments_oldest_first(): void
    {
        $post = Post::factory()->create();
        Comment::factory()->for($post)->create(['body' => 'First comment here', 'created_at' => now()->subHour()]);
        Comment::factory()->for($post)->create(['body' => 'Second comment here']);

        $response = $this->get(route('posts.show', $post));

        $response->assertOk();
        $response->assertSee('id="comments"', false);
        $response->assertSeeInOrder(['First comment here', 'Second comment here']);
        $response->assertSee('2 comments');
    }

    public function test_guest_sees_login_prompt_instead_of_the_form(): void
    {
        $post = Post::factory()->create();

        $response = $this->get(route('posts.show', $post));

        $response->assertSee('to join the conversation');
        $response->assertSee(e(route('login', ['redirect' => '/p/'.$post->public_id.'#comments'])), false);
        $response->assertDontSee('action="'.route('posts.comments.store', $post).'"', false);
    }

    public function test_member_can_comment(): void
    {
        $member = User::factory()->create(['name' => 'Lena']);
        $post = Post::factory()->create();

        $this->actingAs($member)->get(route('posts.show', $post))
            ->assertSee('action="'.route('posts.comments.store', $post).'"', false);

        $response = $this->actingAs($member)->post(route('posts.comments.store', $post), [
            'body' => "  Looks delicious!\nRecipe please?  ",
            'user_id' => 999,
        ]);

        $comment = Comment::sole();
        $response->assertRedirect(route('posts.show', $post).'#comment-'.$comment->id);
        $response->assertSessionHas('status', 'Your comment has been posted.');
        $this->assertSame($member->id, $comment->user_id);
        $this->assertSame($post->id, $comment->post_id);
        $this->assertSame("Looks delicious!\nRecipe please?", $comment->body);

        $this->get(route('posts.show', $post))
            ->assertSee('Looks delicious!<br />', false)
            ->assertSee('Lena');
    }

    public function test_guest_cannot_comment(): void
    {
        $post = Post::factory()->create();

        $this->post(route('posts.comments.store', $post), ['body' => 'Hello there'])->assertRedirect(route('login'));

        $this->assertSame(0, Comment::count());
    }

    public function test_comments_are_validated(): void
    {
        $post = Post::factory()->create();
        $member = User::factory()->create();

        $this->actingAs($member)->post(route('posts.comments.store', $post), ['body' => '   '])->assertSessionHasErrors('body');
        $this->actingAs($member)->post(route('posts.comments.store', $post), ['body' => str_repeat('a', Comment::MAX_LENGTH + 1)])->assertSessionHasErrors('body');

        $this->assertSame(0, Comment::count());
    }

    public function test_cannot_comment_on_pending_or_rejected_posts(): void
    {
        $owner = User::factory()->create();

        foreach ([Post::factory()->for($owner)->pending()->create(), Post::factory()->for($owner)->rejected()->create()] as $post) {
            $this->actingAs($owner)->post(route('posts.comments.store', $post), ['body' => 'Is this live?'])->assertForbidden();
            $this->actingAs($owner)->get(route('posts.show', $post))->assertDontSee('id="comments"', false);
        }

        $this->assertSame(0, Comment::count());
    }

    public function test_comment_author_can_delete_their_comment(): void
    {
        $comment = Comment::factory()->create();
        $post = $comment->post;

        $this->actingAs($comment->user)->get(route('posts.show', $post))
            ->assertSee('action="'.route('comments.destroy', $comment).'"', false);

        $this->actingAs($comment->user)->delete(route('comments.destroy', $comment))
            ->assertRedirect(route('posts.show', $post).'#comments');

        $this->assertModelMissing($comment);
    }

    public function test_post_owner_and_admin_can_delete_comments_on_the_post(): void
    {
        $post = Post::factory()->create();
        $first = Comment::factory()->for($post)->create();
        $second = Comment::factory()->for($post)->create();

        $this->actingAs($post->user)->delete(route('comments.destroy', $first))->assertRedirect();
        $this->actingAs(User::factory()->admin()->create())->delete(route('comments.destroy', $second))->assertRedirect();

        $this->assertModelMissing($first);
        $this->assertModelMissing($second);
    }

    public function test_other_members_cannot_delete_comments(): void
    {
        $comment = Comment::factory()->create();
        $stranger = User::factory()->create();

        $this->actingAs($stranger)->get(route('posts.show', $comment->post))
            ->assertDontSee('action="'.route('comments.destroy', $comment).'"', false);

        $this->actingAs($stranger)->delete(route('comments.destroy', $comment))->assertForbidden();

        $this->assertModelExists($comment);
    }

    public function test_post_author_is_labelled_in_comments(): void
    {
        $post = Post::factory()->create();
        Comment::factory()->for($post)->for($post->user)->create();

        $this->get(route('posts.show', $post))
            ->assertSee('<li class="comment is-author" id="comment-', false)
            ->assertSee('<span class="comment-badge"><i class="bi bi-patch-check-fill" aria-hidden="true"></i>Author</span>', false);
    }

    public function test_comment_content_is_escaped(): void
    {
        $comment = Comment::factory()->create(['body' => '<script>alert(1)</script>']);

        $this->get(route('posts.show', $comment->post))
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
    }

    public function test_feed_cards_show_comment_counts(): void
    {
        $post = Post::factory()->create();
        Comment::factory()->for($post)->count(3)->create();

        $this->get(route('home'))
            ->assertSee('3 comments')
            ->assertSee(route('posts.show', $post).'#comments', false);
    }

    public function test_deleting_a_post_deletes_its_comments(): void
    {
        $comment = Comment::factory()->create();

        $this->actingAs($comment->post->user)->delete(route('posts.destroy', $comment->post));

        $this->assertModelMissing($comment);
    }
}
