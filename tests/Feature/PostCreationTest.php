<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostCreationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return [
            'title' => 'My favourite cinnamon roll recipe',
            'topic' => 'Recipes',
            'body' => 'Soft, gooey and easy to make.',
            'agree' => '1',
            ...$overrides,
        ];
    }

    public function test_guest_is_sent_to_login_from_create_post(): void
    {
        $this->get(route('posts.create'))->assertRedirect(route('login'));
    }

    public function test_guest_cannot_submit_a_post(): void
    {
        $this->post(route('posts.store'), $this->validPayload())->assertRedirect(route('login'));

        $this->assertSame(0, Post::count());
    }

    public function test_guest_returns_to_create_post_after_logging_in(): void
    {
        User::factory()->create(['email' => 'maya@example.com']);

        $this->get(route('posts.create'))->assertRedirect(route('login'));

        $response = $this->post(route('login.store'), ['login' => 'maya@example.com', 'password' => 'password']);

        $response->assertRedirect(route('posts.create'));
    }

    public function test_customer_can_view_create_post_page(): void
    {
        $response = $this->actingAs(User::factory()->create())->get(route('posts.create'));

        $response->assertOk();
        $response->assertSee('Create a post');
        $response->assertSee('action="'.route('posts.store').'"', false);
        $response->assertDontSee('data-demo', false);
    }

    public function test_customer_post_belongs_to_them_even_if_another_author_is_submitted(): void
    {
        $customer = User::factory()->create();
        $otherUser = User::factory()->create();

        $response = $this->actingAs($customer)->post(route('posts.store'), $this->validPayload([
            'user_id' => $otherUser->id,
            'author_id' => $otherUser->id,
        ]));

        $post = Post::sole();
        $response->assertRedirect(route('posts.show', $post));
        $response->assertSessionHas('status');

        $this->assertSame($customer->id, $post->user_id);
        $this->assertSame('My favourite cinnamon roll recipe', $post->title);
        $this->assertSame('Recipes', $post->topic);
    }

    public function test_admin_can_create_a_post(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('posts.store'), $this->validPayload());

        $this->assertSame($admin->id, Post::sole()->user_id);
    }

    public function test_post_fields_are_validated(): void
    {
        $response = $this->actingAs(User::factory()->create())->post(route('posts.store'), [
            'title' => '',
            'topic' => 'Not a real topic',
            'body' => str_repeat('a', 3001),
        ]);

        $response->assertSessionHasErrors(['title', 'topic', 'body', 'agree']);
        $this->assertSame(0, Post::count());
    }

    public function test_dashboard_only_lists_the_users_own_posts(): void
    {
        $customer = User::factory()->create();
        Post::factory()->for($customer)->create(['title' => 'Mine to see']);
        Post::factory()->create(['title' => 'Someone else entirely']);

        $response = $this->actingAs($customer)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Mine to see');
        $response->assertDontSee('Someone else entirely');
    }
}
