<?php

namespace Tests\Feature;

use App\Models\ContactMessage;
use App\Models\User;
use App\Notifications\ContactMessageReceived;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use RuntimeException;
use Tests\TestCase;

class ContactFormTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'name' => 'Maya Baker',
            'email' => 'Maya@Example.com',
            'subject' => 'Account help',
            'post_id' => '#k7Xq92Lm',
            'message' => 'I cannot change my display name, can you help?',
            ...$overrides,
        ];
    }

    public function test_contact_page_renders_a_real_form(): void
    {
        $response = $this->get(route('contact'));

        $response->assertOk();
        $response->assertSee('action="'.route('contact.store').'"', false);
        $response->assertDontSee('data-demo', false);
        $response->assertSee('<option value="Rejected post appeal"', false);
    }

    public function test_guest_message_is_saved_and_emailed_to_every_admin(): void
    {
        Notification::fake();
        $admins = User::factory()->admin()->count(2)->create();
        $customer = User::factory()->create();

        $response = $this->post(route('contact.store'), $this->payload());

        $response->assertRedirect(route('contact'));
        $response->assertSessionHas('status');

        $message = ContactMessage::sole();
        $this->assertSame('Maya Baker', $message->name);
        $this->assertSame('maya@example.com', $message->email);
        $this->assertSame('Account help', $message->subject);
        $this->assertSame('k7Xq92Lm', $message->post_reference);
        $this->assertNull($message->user_id);
        $this->assertNull($message->read_at);
        $this->assertNotNull($message->ip_address);

        Notification::assertSentTo($admins, ContactMessageReceived::class);
        Notification::assertNotSentTo($customer, ContactMessageReceived::class);
    }

    public function test_email_contains_the_message_and_replies_go_to_the_sender(): void
    {
        $admin = User::factory()->admin()->create();
        $message = ContactMessage::factory()->create([
            'name' => 'Maya Baker',
            'email' => 'maya@example.com',
            'subject' => 'Feedback or ideas',
            'message' => 'Love the new feed layout!',
        ]);

        $mail = (new ContactMessageReceived($message))->toMail($admin);

        $this->assertSame('New contact message: Feedback or ideas', $mail->subject);
        $this->assertSame([['maya@example.com', 'Maya Baker']], $mail->replyTo);
        $this->assertContains('Love the new feed layout!', $mail->introLines);
        $this->assertSame(route('admin.contacts.show', $message), $mail->actionUrl);
    }

    public function test_logged_in_user_is_prefilled_and_linked(): void
    {
        Notification::fake();
        $user = User::factory()->create(['name' => 'Logged In Lena', 'email' => 'lena@example.com']);

        $this->actingAs($user)->get(route('contact'))
            ->assertSee('value="Logged In Lena"', false)
            ->assertSee('value="lena@example.com"', false);

        $this->actingAs($user)->post(route('contact.store'), $this->payload(['user_id' => 999]));

        $this->assertSame($user->id, ContactMessage::sole()->user_id);
    }

    public function test_invalid_input_is_rejected_with_errors(): void
    {
        Notification::fake();

        $response = $this->from(route('contact'))->post(route('contact.store'), [
            'name' => '',
            'email' => 'not-an-email',
            'subject' => 'Free money',
            'post_id' => 'bad id!',
            'message' => 'short',
        ]);

        $response->assertRedirect(route('contact'));
        $response->assertSessionHasErrors(['name', 'email', 'subject', 'post_id', 'message']);
        $response->assertSessionHasInput('email', 'not-an-email');
        $this->assertSame(0, ContactMessage::count());
        Notification::assertNothingSent();
    }

    public function test_spam_trap_blocks_bots(): void
    {
        Notification::fake();

        $this->post(route('contact.store'), $this->payload(['website' => 'http://spam.example']))
            ->assertSessionHasErrors('website');

        $this->assertSame(0, ContactMessage::count());
        Notification::assertNothingSent();
    }

    public function test_message_is_saved_even_if_mail_fails(): void
    {
        User::factory()->admin()->create();
        Notification::shouldReceive('send')->once()->andThrow(new RuntimeException('SMTP unavailable'));

        $this->post(route('contact.store'), $this->payload())
            ->assertRedirect(route('contact'))
            ->assertSessionHas('status');

        $this->assertSame(1, ContactMessage::count());
    }
}
