<?php

namespace Tests\Feature;

use App\Mail\TestEmail;
use App\Models\ContactMessage;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\ContactMessageReceived;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AdminSettingsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create(['email' => 'admin@example.com']);
    }

    /**
     * @return array<string, mixed>
     */
    private function smtpPayload(array $overrides = []): array
    {
        return [
            'mail_mailer' => 'smtp',
            'mail_host' => 'smtp.example.com',
            'mail_port' => '587',
            'mail_encryption' => 'tls',
            'mail_username' => 'mailer@example.com',
            'mail_password' => 'super-secret-app-password',
            'mail_from_address' => 'hello@feedeeforums.com',
            'mail_from_name' => 'Feedee Forums',
            'mail_contact_recipient' => 'team@feedeeforums.com',
            ...$overrides,
        ];
    }

    public function test_settings_page_and_sidebar_link(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.settings'));

        $response->assertOk();
        $response->assertSee('Support button');
        $response->assertSee('Email sending');
        $response->assertSee('value="https://youpay.me/zaiyaxx"', false);
        $response->assertSee('href="'.route('admin.settings').'" class="active" aria-current="page"', false);
    }

    public function test_frontend_support_button_uses_default_links(): void
    {
        $response = $this->get(route('home'));

        $response->assertSee('class="support-fab"', false);
        $response->assertSee('href="https://youpay.me/zaiyaxx"', false);
        $response->assertSee('href="https://cash.app/$zaiyaxx"', false);
        $response->assertSee('$zaiyaxx · US only');
    }

    public function test_admin_can_change_support_links_and_frontend_updates(): void
    {
        $this->actingAs($this->admin)->put(route('admin.settings.support'), [
            'support_enabled' => '1',
            'youpay_url' => 'https://youpay.me/newname',
            'youpay_note' => 'Wishlist gifts',
            'cashapp_url' => '',
            'cashapp_note' => '',
        ])->assertRedirect(route('admin.settings'))->assertSessionHas('status');

        auth()->logout();
        $response = $this->get(route('login'));

        $response->assertSee('href="https://youpay.me/newname"', false);
        $response->assertSee('Wishlist gifts');
        $response->assertDontSee('cash.app', false);
        $response->assertDontSee('youpay.me/zaiyaxx', false);
    }

    public function test_support_button_can_be_turned_off(): void
    {
        $this->actingAs($this->admin)->put(route('admin.settings.support'), [
            'support_enabled' => '0',
            'youpay_url' => 'https://youpay.me/zaiyaxx',
            'cashapp_url' => 'https://cash.app/$zaiyaxx',
        ]);

        $this->get(route('home'))->assertDontSee('class="support-fab"', false);
    }

    public function test_support_button_hides_when_both_links_are_empty(): void
    {
        Setting::setMany(['support.youpay_url' => '', 'support.cashapp_url' => '']);

        $this->get(route('home'))->assertDontSee('class="support-fab"', false);
    }

    public function test_support_links_must_be_https_urls(): void
    {
        $this->actingAs($this->admin)->put(route('admin.settings.support'), [
            'support_enabled' => '1',
            'youpay_url' => 'javascript:alert(1)',
            'cashapp_url' => 'http://cash.app/$insecure',
            'youpay_note' => str_repeat('a', 61),
        ])->assertSessionHasErrors(['youpay_url', 'cashapp_url', 'youpay_note']);

        $this->assertSame('https://youpay.me/zaiyaxx', Setting::get('support.youpay_url'));
    }

    public function test_admin_can_save_smtp_settings_and_password_is_encrypted(): void
    {
        $this->actingAs($this->admin)->put(route('admin.settings.mail'), $this->smtpPayload())
            ->assertRedirect(route('admin.settings'))
            ->assertSessionHasNoErrors();

        $this->assertSame('smtp', Setting::get('mail.mailer'));
        $this->assertSame('smtp.example.com', Setting::get('mail.host'));
        $this->assertSame('super-secret-app-password', Setting::get('mail.password'));

        $stored = DB::table('settings')->where('key', 'mail.password')->value('value');
        $this->assertNotSame('super-secret-app-password', $stored);
        $this->assertStringNotContainsString('super-secret', $stored);

        $page = $this->actingAs($this->admin)->get(route('admin.settings'));
        $page->assertDontSee('super-secret-app-password');
        $page->assertSee('saved, leave blank to keep');
    }

    public function test_blank_password_keeps_the_saved_one(): void
    {
        $this->actingAs($this->admin)->put(route('admin.settings.mail'), $this->smtpPayload());
        $this->actingAs($this->admin)->put(route('admin.settings.mail'), $this->smtpPayload(['mail_password' => '', 'mail_host' => 'smtp.other.com']));

        $this->assertSame('super-secret-app-password', Setting::get('mail.password'));
        $this->assertSame('smtp.other.com', Setting::get('mail.host'));
    }

    public function test_saved_mail_settings_are_applied_to_laravel_config(): void
    {
        $this->actingAs($this->admin)->put(route('admin.settings.mail'), $this->smtpPayload(['mail_encryption' => 'ssl', 'mail_port' => '465']));

        Setting::applyMailConfig();

        $this->assertSame('smtp', config('mail.default'));
        $this->assertSame('smtp.example.com', config('mail.mailers.smtp.host'));
        $this->assertSame(465, config('mail.mailers.smtp.port'));
        $this->assertSame('smtps', config('mail.mailers.smtp.scheme'));
        $this->assertSame('mailer@example.com', config('mail.mailers.smtp.username'));
        $this->assertSame('super-secret-app-password', config('mail.mailers.smtp.password'));
        $this->assertSame('hello@feedeeforums.com', config('mail.from.address'));
        $this->assertSame('Feedee Forums', config('mail.from.name'));
    }

    public function test_smtp_fields_are_validated(): void
    {
        $this->actingAs($this->admin)->put(route('admin.settings.mail'), $this->smtpPayload([
            'mail_host' => 'http://bad host',
            'mail_port' => '70000',
            'mail_encryption' => 'starttls-maybe',
            'mail_from_address' => 'not-an-email',
            'mail_contact_recipient' => 'nope',
        ]))->assertSessionHasErrors(['mail_host', 'mail_port', 'mail_encryption', 'mail_from_address', 'mail_contact_recipient']);

        $this->assertNull(DB::table('settings')->where('key', 'mail.mailer')->value('value'));
    }

    public function test_log_mailer_does_not_require_smtp_fields(): void
    {
        $this->actingAs($this->admin)->put(route('admin.settings.mail'), [
            'mail_mailer' => 'log',
            'mail_from_address' => 'hello@feedeeforums.com',
            'mail_from_name' => 'Feedee Forums',
        ])->assertSessionHasNoErrors();

        $this->assertSame('log', Setting::get('mail.mailer'));
    }

    public function test_test_email_is_sent_to_the_given_address(): void
    {
        Mail::fake();

        $this->actingAs($this->admin)
            ->post(route('admin.settings.mail.test'), ['test_recipient' => 'check@example.com'])
            ->assertRedirect(route('admin.settings'))
            ->assertSessionHas('status');

        Mail::assertSent(TestEmail::class, fn (TestEmail $mail) => $mail->hasTo('check@example.com'));
    }

    public function test_test_email_requires_a_valid_address(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.settings.mail.test'), ['test_recipient' => 'nope'])
            ->assertSessionHasErrors('test_recipient');
    }

    public function test_contact_messages_also_go_to_the_extra_recipient(): void
    {
        Notification::fake();
        Setting::set('mail.contact_recipient', 'team@feedeeforums.com');

        $this->post(route('contact.store'), [
            'name' => 'Maya',
            'email' => 'maya@example.com',
            'subject' => 'Account help',
            'message' => 'Please help me with my account.',
        ])->assertRedirect(route('contact'));

        Notification::assertSentTo($this->admin, ContactMessageReceived::class);
        Notification::assertSentOnDemand(ContactMessageReceived::class, function ($notification, $channels, AnonymousNotifiable $notifiable) {
            return $notifiable->routes['mail'] === 'team@feedeeforums.com';
        });
        $this->assertSame(1, ContactMessage::count());
    }

    public function test_customers_cannot_view_or_change_settings(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer)->get(route('admin.settings'))->assertForbidden();
        $this->actingAs($customer)->put(route('admin.settings.support'), ['youpay_url' => 'https://evil.example'])->assertForbidden();
        $this->actingAs($customer)->put(route('admin.settings.mail'), $this->smtpPayload())->assertForbidden();
        $this->actingAs($customer)->post(route('admin.settings.mail.test'), ['test_recipient' => 'x@example.com'])->assertForbidden();

        $this->assertSame('https://youpay.me/zaiyaxx', Setting::get('support.youpay_url'));
        $this->assertNull(DB::table('settings')->where('key', 'mail.host')->value('value'));
    }
}
