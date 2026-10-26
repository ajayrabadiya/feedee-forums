<?php

namespace Tests\Feature;

use App\Models\ContactMessage;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminContactMessagesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
    }

    public function test_admin_sees_contact_messages_newest_first(): void
    {
        ContactMessage::factory()->create(['name' => 'Older Sender', 'subject' => 'Feedback or ideas', 'created_at' => now()->subDay()]);
        $new = ContactMessage::factory()->create(['name' => 'Newer Sender', 'email' => 'newer@example.com', 'subject' => 'Account help', 'post_reference' => 'k7Xq92Lm']);
        ContactMessage::factory()->read()->create(['name' => 'Read Sender']);

        $response = $this->actingAs($this->admin)->get(route('admin.contacts.index'));

        $response->assertOk();
        $response->assertSeeInOrder(['Newer Sender', 'Older Sender']);
        $response->assertSee('newer@example.com');
        $response->assertSee('#k7Xq92Lm');
        $response->assertSee('href="'.route('admin.contacts.show', $new).'"', false);
        $response->assertSee('data-status="unread"', false);
        $response->assertSee('data-status="read"', false);
        $response->assertSee('data-tab="unread" role="tab" aria-selected="true"', false);
    }

    public function test_sidebar_shows_unread_count(): void
    {
        ContactMessage::factory()->count(3)->create();
        ContactMessage::factory()->read()->create();

        $content = $this->actingAs($this->admin)->get(route('admin.dashboard'))->getContent();

        $this->assertMatchesRegularExpression('#Contact messages\s*<span class="count hot" title="Unread messages">3</span>#', $content);
    }

    public function test_opening_a_message_marks_it_read_and_shows_details(): void
    {
        $author = User::factory()->create(['name' => 'Maya', 'username' => 'maya_bakes']);
        $post = Post::factory()->create(['title' => 'Cinnamon rolls']);
        $message = ContactMessage::factory()->for($author)->create([
            'message' => "Line one\nLine two",
            'post_reference' => $post->public_id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.contacts.show', $message));

        $response->assertOk();
        $response->assertSee('Line one<br />', false);
        $response->assertSee('@maya_bakes', false);
        $response->assertSee('Cinnamon rolls');
        $response->assertSee('mailto:'.$message->email, false);
        $this->assertNotNull($message->fresh()->read_at);
    }

    public function test_admin_can_mark_a_message_unread(): void
    {
        $message = ContactMessage::factory()->read()->create();

        $this->actingAs($this->admin)
            ->patch(route('admin.contacts.update', $message), ['read' => '0'])
            ->assertRedirect(route('admin.contacts.index'));

        $this->assertNull($message->fresh()->read_at);
    }

    public function test_admin_can_delete_a_message(): void
    {
        $message = ContactMessage::factory()->create();

        $this->actingAs($this->admin)
            ->delete(route('admin.contacts.destroy', $message))
            ->assertRedirect(route('admin.contacts.index'));

        $this->assertModelMissing($message);
    }

    public function test_message_content_is_escaped(): void
    {
        $message = ContactMessage::factory()->create(['name' => '<b>bold</b>', 'message' => '<script>alert(1)</script>']);

        $this->actingAs($this->admin)->get(route('admin.contacts.index'))
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertDontSee('<b>bold</b>', false);

        $this->actingAs($this->admin)->get(route('admin.contacts.show', $message))
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
    }

    public function test_customers_and_guests_cannot_access_contact_messages(): void
    {
        $message = ContactMessage::factory()->create();
        $customer = User::factory()->create();

        $this->actingAs($customer)->get(route('admin.contacts.index'))->assertForbidden();
        $this->actingAs($customer)->get(route('admin.contacts.show', $message))->assertForbidden();
        $this->actingAs($customer)->delete(route('admin.contacts.destroy', $message))->assertForbidden();
        $this->assertNull($message->fresh()->read_at);

        auth()->logout();
        $this->get(route('admin.contacts.index'))->assertRedirect(route('login'));
        $this->assertModelExists($message);
    }
}
