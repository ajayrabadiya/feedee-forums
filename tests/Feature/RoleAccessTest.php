<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{string}>
     */
    public static function adminUriProvider(): array
    {
        return [
            'dashboard' => ['/admin'],
            'posts' => ['/admin/posts'],
            'reports' => ['/admin/reports'],
            'docs' => ['/admin/docs'],
        ];
    }

    #[DataProvider('adminUriProvider')]
    public function test_guest_is_redirected_to_login_from_admin_pages(string $uri): void
    {
        $this->get($uri)->assertRedirect(route('login'));
    }

    #[DataProvider('adminUriProvider')]
    public function test_customer_is_forbidden_from_admin_pages(string $uri): void
    {
        $response = $this->actingAs(User::factory()->create())->get($uri);

        $response->assertForbidden();
        $response->assertDontSee('Waiting for review');
    }

    public function test_admin_json_requests_from_customers_are_forbidden(): void
    {
        $this->actingAs(User::factory()->create())
            ->getJson('/admin/posts')
            ->assertForbidden();
    }

    #[DataProvider('adminUriProvider')]
    public function test_admin_can_access_admin_pages(string $uri): void
    {
        $this->actingAs(User::factory()->admin()->create())->get($uri)->assertOk();
    }

    public function test_customer_and_guest_dashboards_are_protected(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->get(route('profile.edit'))->assertRedirect(route('login'));
    }

    public function test_header_dashboard_link_points_admins_to_admin_dashboard(): void
    {
        $response = $this->actingAs(User::factory()->admin()->create())->get('/rules');

        $response->assertSee('href="'.route('admin.dashboard').'"><i class="bi bi-grid-1x2"></i>Dashboard', false);
    }

    public function test_create_admin_command_creates_an_administrator(): void
    {
        $this->artisan('app:create-admin', ['--username' => 'site_admin', '--email' => 'Admin@Example.com'])
            ->expectsQuestion('Password (min. 8 characters)', 'super-secret-1')
            ->expectsQuestion('Confirm password', 'super-secret-1')
            ->assertSuccessful();

        $admin = User::where('email', 'admin@example.com')->sole();

        $this->assertSame(UserRole::Admin, $admin->role);
        $this->assertSame('site_admin', $admin->username);
    }

    public function test_create_admin_command_rejects_invalid_input(): void
    {
        User::factory()->create(['username' => 'site_admin']);

        $this->artisan('app:create-admin', ['--username' => 'site_admin', '--email' => 'admin@example.com'])
            ->expectsQuestion('Password (min. 8 characters)', 'short')
            ->expectsQuestion('Confirm password', 'short')
            ->assertFailed();

        $this->assertSame(0, User::where('role', UserRole::Admin)->count());
    }
}
