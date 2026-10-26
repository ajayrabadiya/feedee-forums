<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_renders_for_guests(): void
    {
        $this->get(route('login'))->assertOk()->assertSee('Log in');
    }

    public function test_customer_can_log_in_with_email_and_lands_on_dashboard(): void
    {
        $user = User::factory()->create(['email' => 'maya@example.com']);

        $response = $this->post(route('login.store'), ['login' => 'maya@example.com', 'password' => 'password']);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_customer_can_log_in_with_username(): void
    {
        $user = User::factory()->create(['username' => 'maya_bakes']);

        $this->post(route('login.store'), ['login' => 'maya_bakes', 'password' => 'password']);

        $this->assertAuthenticatedAs($user);
    }

    public function test_admin_without_previous_page_lands_on_admin_dashboard(): void
    {
        $admin = User::factory()->admin()->create(['email' => 'admin@example.com']);

        $response = $this->post(route('login.store'), ['login' => 'admin@example.com', 'password' => 'password']);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin);
    }

    public function test_invalid_credentials_are_rejected(): void
    {
        User::factory()->create(['email' => 'maya@example.com']);

        $response = $this->from(route('login'))->post(route('login.store'), ['login' => 'maya@example.com', 'password' => 'wrong-password']);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    public function test_login_is_rate_limited(): void
    {
        User::factory()->create(['email' => 'maya@example.com']);

        foreach (range(1, 5) as $attempt) {
            $this->post(route('login.store'), ['login' => 'maya@example.com', 'password' => 'wrong-password']);
        }

        $response = $this->post(route('login.store'), ['login' => 'maya@example.com', 'password' => 'password']);

        $response->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    public function test_login_returns_user_to_the_public_page_they_were_viewing(): void
    {
        $user = User::factory()->create(['email' => 'maya@example.com']);

        $this->get(route('login', ['redirect' => '/p/k7Xq92Lm?sort=new']))->assertOk();

        $response = $this->post(route('login.store'), ['login' => 'maya@example.com', 'password' => 'password']);

        $response->assertRedirect(url('/p/k7Xq92Lm?sort=new'));
        $this->assertAuthenticatedAs($user);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function unsafeRedirectProvider(): array
    {
        return [
            'absolute external url' => ['https://evil.example.com/phish'],
            'protocol relative url' => ['//evil.example.com'],
            'backslash trick' => ['/\\evil.example.com'],
            'javascript scheme' => ['javascript:alert(1)'],
            'relative without slash' => ['evil.example.com'],
            'login loop' => ['/login'],
        ];
    }

    #[DataProvider('unsafeRedirectProvider')]
    public function test_unsafe_redirect_targets_are_ignored(string $target): void
    {
        User::factory()->create(['email' => 'maya@example.com']);

        $this->get(route('login', ['redirect' => $target]))->assertOk();

        $response = $this->post(route('login.store'), ['login' => 'maya@example.com', 'password' => 'password']);

        $response->assertRedirect(route('dashboard'));
    }

    public function test_header_login_link_carries_the_current_page(): void
    {
        $response = $this->get('/rules');

        $response->assertSee(route('login', ['redirect' => '/rules']), false);
        $response->assertSee(route('register', ['redirect' => '/rules']), false);
        $response->assertDontSee('class="user-avatar"', false);
    }

    public function test_header_shows_avatar_menu_for_signed_in_customer(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/rules');

        $response->assertSee('class="user-avatar"', false);
        $response->assertSee('href="'.route('dashboard').'"><i class="bi bi-grid-1x2"></i>Dashboard', false);
        $response->assertSee('href="'.route('profile.edit').'"><i class="bi bi-person"></i>Profile', false);
        $response->assertDontSee('class="btn-line">Log In', false);
        $response->assertDontSee('class="btn-fill">Sign Up', false);
    }

    public function test_authenticated_user_is_redirected_away_from_login(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('login'))->assertRedirect(route('dashboard'));
    }

    public function test_user_can_log_out(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('logout'));

        $response->assertRedirect(route('home'));
        $this->assertGuest();
    }

    public function test_logout_requires_post(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/logout')->assertStatus(405);
        $this->assertAuthenticatedAs($user);
    }
}
