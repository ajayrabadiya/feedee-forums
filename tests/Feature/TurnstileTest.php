<?php

namespace Tests\Feature;

use App\Models\User;
use App\Rules\Turnstile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TurnstileTest extends TestCase
{
    use RefreshDatabase;

    private const VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    private function enableTurnstile(): void
    {
        config(['services.turnstile.site_key' => 'site-key-123', 'services.turnstile.secret_key' => 'secret-key-456']);
    }

    private function cloudflareSays(bool $success): void
    {
        Http::fake([self::VERIFY_URL => Http::response(['success' => $success, 'error-codes' => $success ? [] : ['invalid-input-response']])]);
    }

    /**
     * @return array<string, string>
     */
    private function registration(array $overrides = []): array
    {
        return [
            'username' => 'maya_bakes',
            'email' => 'maya@example.com',
            'password' => 'secret-password-1',
            'password_confirmation' => 'secret-password-1',
            'terms' => '1',
            ...$overrides,
        ];
    }

    public function test_widget_is_hidden_and_not_required_until_keys_are_set(): void
    {
        $this->get(route('login'))->assertOk()->assertDontSee('cf-turnstile', false)->assertDontSee('Verifying you\'re human');
        $this->get(route('register'))->assertOk()->assertDontSee('challenges.cloudflare.com', false);

        $user = User::factory()->create(['email' => 'maya@example.com']);

        $this->post(route('login.store'), ['login' => 'maya@example.com', 'password' => 'password'])->assertRedirect();
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_and_register_pages_show_the_real_widget(): void
    {
        $this->enableTurnstile();

        foreach (['login', 'register'] as $page) {
            $response = $this->get(route($page));

            $response->assertSee('<div class="cf-turnstile" data-sitekey="site-key-123"', false);
            $response->assertSee('<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>', false);
            $response->assertDontSee('secret-key-456');
            $this->assertSame(1, substr_count($response->getContent(), 'turnstile/v0/api.js'));
        }
    }

    public function test_login_requires_the_security_check(): void
    {
        $this->enableTurnstile();
        Http::fake();
        User::factory()->create(['email' => 'maya@example.com']);

        $this->from(route('login'))
            ->post(route('login.store'), ['login' => 'maya@example.com', 'password' => 'password'])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors([Turnstile::FIELD => 'Please complete the security check.']);

        $this->assertGuest();
        Http::assertNothingSent();
    }

    public function test_login_succeeds_when_cloudflare_confirms_the_visitor(): void
    {
        $this->enableTurnstile();
        $this->cloudflareSays(true);
        $user = User::factory()->create(['email' => 'maya@example.com']);

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.7'])
            ->post(route('login.store'), ['login' => 'maya@example.com', 'password' => 'password', Turnstile::FIELD => 'token-abc'])
            ->assertRedirect();

        $this->assertAuthenticatedAs($user);
        Http::assertSent(fn (Request $request): bool => $request->url() === self::VERIFY_URL
            && $request['secret'] === 'secret-key-456'
            && $request['response'] === 'token-abc'
            && $request['remoteip'] === '203.0.113.7');
    }

    public function test_login_fails_when_cloudflare_rejects_the_token(): void
    {
        $this->enableTurnstile();
        $this->cloudflareSays(false);
        User::factory()->create(['email' => 'maya@example.com']);

        $this->post(route('login.store'), ['login' => 'maya@example.com', 'password' => 'password', Turnstile::FIELD => 'expired-token'])
            ->assertSessionHasErrors([Turnstile::FIELD => 'The security check failed. Please try again.']);

        $this->assertGuest();
    }

    public function test_registration_requires_a_passed_security_check(): void
    {
        $this->enableTurnstile();
        Http::fake([self::VERIFY_URL => Http::sequence()->push(['success' => false])->push(['success' => true])]);

        $this->post(route('register.store'), $this->registration())->assertSessionHasErrors(Turnstile::FIELD);
        $this->post(route('register.store'), $this->registration([Turnstile::FIELD => 'bad-token']))->assertSessionHasErrors(Turnstile::FIELD);
        $this->assertSame(0, User::count());

        $this->post(route('register.store'), $this->registration([Turnstile::FIELD => 'good-token']))->assertRedirect(route('dashboard'));
        $this->assertSame(1, User::where('username', 'maya_bakes')->count());
    }

    public function test_cloudflare_being_unreachable_fails_safely(): void
    {
        if (function_exists('xdebug_info') && in_array('develop', xdebug_info('mode'), true)) {
            $this->markTestSkipped('Xdebug 3.3 in develop mode crashes PHP on failed HTTP connections. Run with XDEBUG_MODE=off.');
        }

        $this->enableTurnstile();
        Http::fake([self::VERIFY_URL => Http::failedConnection('Connection timed out')]);
        User::factory()->create(['email' => 'maya@example.com']);

        $this->post(route('login.store'), ['login' => 'maya@example.com', 'password' => 'password', Turnstile::FIELD => 'token-abc'])
            ->assertSessionHasErrors([Turnstile::FIELD => 'The security check failed. Please try again.']);

        $this->assertGuest();
    }
}
