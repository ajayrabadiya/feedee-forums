<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminPagesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{string, string}>
     */
    public static function pageProvider(): array
    {
        return [
            'dashboard' => ['/admin', 'Waiting for review'],
            'posts' => ['/admin/posts', 'Require approval for new posts'],
            'reports' => ['/admin/reports', 'Reporter identities are never shown to post authors'],
            'docs' => ['/admin/docs', '30-day auto cleanup'],
        ];
    }

    #[DataProvider('pageProvider')]
    public function test_admin_page_renders(string $uri, string $expectedText): void
    {
        $response = $this->actingAs(User::factory()->admin()->create())->get($uri);

        $response->assertOk();
        $response->assertSee($expectedText);
        $response->assertSee('<meta name="robots" content="noindex, nofollow">', false);
        $response->assertSee(asset('assets/css/admin.css'), false);
        $response->assertSee(asset('assets/js/admin.js'), false);
        $response->assertDontSee('.html"', false);
    }

    public function test_sidebar_marks_current_page_as_active(): void
    {
        $response = $this->actingAs(User::factory()->admin()->create())->get('/admin/customers');

        $response->assertSee('<a href="'.route('admin.customers').'" class="active" aria-current="page">', false);
        $response->assertDontSee('<a href="'.route('admin.posts').'" class="active"', false);
    }

    public function test_sidebar_only_shows_dashboard_posts_customers_and_contacts(): void
    {
        $content = $this->actingAs(User::factory()->admin()->create())->get('/admin')->getContent();

        preg_match('#<aside class="admin-side".*?</aside>#s', $content, $sidebar);

        $this->assertNotEmpty($sidebar);
        foreach (['Dashboard', 'Posts', 'Customers', 'Contact messages'] as $item) {
            $this->assertStringContainsString($item, $sidebar[0]);
        }
        foreach (['Reports', 'Admin guide', 'View site', 'Log out'] as $removed) {
            $this->assertStringNotContainsString($removed, $sidebar[0]);
        }
    }

    public function test_topbar_has_no_notification_bell(): void
    {
        $this->actingAs(User::factory()->admin()->create())->get('/admin')
            ->assertDontSee('bi-bell', false)
            ->assertDontSee('open reports')
            ->assertDontSee('Search posts, users or IDs')
            ->assertDontSee('Admin guide')
            ->assertDontSee('Scheduler &amp; cleanup', false)
            ->assertDontSee('data-run-cleanup', false);
    }

    public function test_topbar_shows_the_signed_in_administrator(): void
    {
        $admin = User::factory()->admin()->create(['name' => 'Jordan Admin']);

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertSee('Jordan Admin');
        $response->assertSee('Administrator');
        $response->assertSee('action="'.route('logout').'"', false);
    }
}
