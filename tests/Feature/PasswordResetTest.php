<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use App\Rules\Turnstile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use RuntimeException;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        $this->user = User::factory()->create(['name' => 'maya_bakes', 'email' => 'maya@example.com']);
    }

    /**
     * Request a reset link and return the token from the email.
     */
    private function requestToken(string $email = 'maya@example.com'): string
    {
        $this->post(route('password.email'), ['email' => $email]);

        $token = null;

        Notification::assertSentTo($this->user, ResetPasswordNotification::class, function (ResetPasswordNotification $notification) use (&$token): bool {
            $token = $notification->token;

            return true;
        });

        return $token;
    }

    /**
     * @return array<string, string>
     */
    private function resetPayload(string $token, array $overrides = []): array
    {
        return [
            'token' => $token,
            'email' => 'maya@example.com',
            'password' => 'brand-new-secret-9',
            'password_confirmation' => 'brand-new-secret-9',
            ...$overrides,
        ];
    }

    public function test_login_page_links_to_the_forgot_password_page(): void
    {
        $this->get(route('login'))->assertSee('href="'.route('password.request').'"', false);

        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('Forgot password?')
            ->assertSee('action="'.route('password.email').'"', false)
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false);
    }

    public function test_signed_in_members_cannot_open_the_reset_pages(): void
    {
        $this->actingAs($this->user)->get(route('password.request'))->assertRedirect();
        $this->actingAs($this->user)->get(route('password.reset', 'some-token'))->assertRedirect();
    }

    public function test_reset_link_is_emailed_to_a_registered_address(): void
    {
        $response = $this->from(route('password.request'))->post(route('password.email'), ['email' => '  Maya@Example.com ']);

        $response->assertRedirect(route('password.request'));
        $response->assertSessionHas('resetLinkSent', 'maya@example.com');

        Notification::assertSentTo($this->user, ResetPasswordNotification::class, function (ResetPasswordNotification $notification): bool {
            $mail = $notification->toMail($this->user);

            return $mail->subject === 'Reset your Feedee Forums password'
                && $mail->actionUrl === route('password.reset', ['token' => $notification->token, 'email' => 'maya@example.com']);
        });

        $this->followingRedirects()->post(route('password.email'), ['email' => 'maya@example.com'])
            ->assertSee('If an account exists for maya@example.com');
    }

    public function test_unknown_addresses_get_the_same_reply_and_no_email(): void
    {
        $response = $this->from(route('password.request'))->post(route('password.email'), ['email' => 'nobody@example.com']);

        $response->assertRedirect(route('password.request'));
        $response->assertSessionHas('resetLinkSent', 'nobody@example.com');
        $response->assertSessionHasNoErrors();
        Notification::assertNothingSent();
    }

    public function test_repeat_requests_within_a_minute_send_one_email(): void
    {
        $this->post(route('password.email'), ['email' => 'maya@example.com']);
        $this->post(route('password.email'), ['email' => 'maya@example.com'])->assertSessionHas('resetLinkSent');

        Notification::assertSentToTimes($this->user, ResetPasswordNotification::class, 1);
    }

    public function test_forgot_password_form_is_validated(): void
    {
        $this->post(route('password.email'), ['email' => ''])->assertSessionHasErrors('email');
        $this->post(route('password.email'), ['email' => 'not-an-email'])->assertSessionHasErrors('email');

        Notification::assertNothingSent();
    }

    public function test_mail_failure_shows_a_message_instead_of_an_error_page(): void
    {
        Password::shouldReceive('sendResetLink')->once()->andThrow(new RuntimeException('SMTP is down'));

        $this->from(route('password.request'))
            ->post(route('password.email'), ['email' => 'maya@example.com'])
            ->assertRedirect(route('password.request'))
            ->assertSessionHasErrors(['email' => 'We couldn\'t send the email right now. Please try again in a few minutes.']);
    }

    public function test_forgot_password_form_uses_the_security_check_when_enabled(): void
    {
        config(['services.turnstile.site_key' => 'site-key', 'services.turnstile.secret_key' => 'secret-key']);

        $this->get(route('password.request'))->assertSee('class="cf-turnstile"', false);
        $this->post(route('password.email'), ['email' => 'maya@example.com'])->assertSessionHasErrors(Turnstile::FIELD);

        Notification::assertNothingSent();
    }

    public function test_reset_page_shows_the_form_with_the_email_filled_in(): void
    {
        $this->get(route('password.reset', ['token' => 'abc123', 'email' => 'maya@example.com']))
            ->assertOk()
            ->assertSee('name="token" value="abc123"', false)
            ->assertSee('value="maya@example.com"', false)
            ->assertSee('<meta name="referrer" content="no-referrer">', false);
    }

    public function test_member_can_reset_their_password_and_log_in_with_it(): void
    {
        $token = $this->requestToken();
        $oldRememberToken = $this->user->remember_token;

        $this->post(route('password.store'), $this->resetPayload($token))
            ->assertRedirect(route('login'))
            ->assertSessionHas('status', 'Your password has been reset. You can log in with your new password now.');

        $this->user->refresh();
        $this->assertTrue(Hash::check('brand-new-secret-9', $this->user->password));
        $this->assertNotSame($oldRememberToken, $this->user->remember_token);
        $this->assertGuest();

        $this->post(route('login.store'), ['login' => 'maya@example.com', 'password' => 'password'])->assertSessionHasErrors('login');
        $this->post(route('login.store'), ['login' => 'maya@example.com', 'password' => 'brand-new-secret-9']);
        $this->assertAuthenticatedAs($this->user);
    }

    public function test_reset_link_only_works_once(): void
    {
        $token = $this->requestToken();

        $this->post(route('password.store'), $this->resetPayload($token))->assertRedirect(route('login'));
        $this->post(route('password.store'), $this->resetPayload($token, ['password' => 'another-secret-1', 'password_confirmation' => 'another-secret-1']))
            ->assertSessionHasErrors(['email' => 'This reset link is invalid or has expired. Please request a new one.']);

        $this->assertTrue(Hash::check('brand-new-secret-9', $this->user->fresh()->password));
    }

    public function test_reset_signs_the_account_out_on_other_devices(): void
    {
        config(['session.driver' => 'database']);
        $other = User::factory()->create();
        DB::table('sessions')->insert([
            ['id' => 'maya-phone', 'user_id' => $this->user->id, 'payload' => '', 'last_activity' => now()->timestamp],
            ['id' => 'other-laptop', 'user_id' => $other->id, 'payload' => '', 'last_activity' => now()->timestamp],
        ]);

        $this->post(route('password.store'), $this->resetPayload($this->requestToken()));

        $this->assertDatabaseMissing('sessions', ['id' => 'maya-phone']);
        $this->assertDatabaseHas('sessions', ['id' => 'other-laptop']);
    }

    public function test_invalid_wrong_email_and_expired_links_are_rejected(): void
    {
        $token = $this->requestToken();
        User::factory()->create(['email' => 'someone@example.com']);
        $message = 'This reset link is invalid or has expired. Please request a new one.';

        $this->post(route('password.store'), $this->resetPayload('not-the-real-token'))->assertSessionHasErrors(['email' => $message]);
        $this->post(route('password.store'), $this->resetPayload($token, ['email' => 'someone@example.com']))->assertSessionHasErrors(['email' => $message]);
        $this->post(route('password.store'), $this->resetPayload($token, ['email' => 'nobody@example.com']))->assertSessionHasErrors(['email' => $message]);

        $this->travel(61)->minutes();
        $this->post(route('password.store'), $this->resetPayload($token))->assertSessionHasErrors(['email' => $message]);

        $this->assertTrue(Hash::check('password', $this->user->fresh()->password));
    }

    public function test_new_password_must_be_confirmed_and_long_enough(): void
    {
        $token = $this->requestToken();

        $this->post(route('password.store'), $this->resetPayload($token, ['password_confirmation' => 'something-else']))->assertSessionHasErrors('password');
        $this->post(route('password.store'), $this->resetPayload($token, ['password' => 'short', 'password_confirmation' => 'short']))->assertSessionHasErrors('password');
        $this->post(route('password.store'), $this->resetPayload('', []))->assertSessionHasErrors('token');

        $this->assertTrue(Hash::check('password', $this->user->fresh()->password));
    }
}
