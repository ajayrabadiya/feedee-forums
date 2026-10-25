<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminPagesTest extends TestCase
{
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
        $response = $this->get($uri);

        $response->assertOk();
        $response->assertSee($expectedText);
        $response->assertSee('<meta name="robots" content="noindex, nofollow">', false);
        $response->assertSee(asset('assets/css/admin.css'), false);
        $response->assertSee(asset('assets/js/admin.js'), false);
        $response->assertDontSee('.html"', false);
    }

    public function test_sidebar_marks_current_page_as_active(): void
    {
        $response = $this->get('/admin/reports');

        $response->assertSee('<a href="'.route('admin.reports').'" class="active" aria-current="page">', false);
        $response->assertDontSee('<a href="'.route('admin.posts').'" class="active"', false);
    }
}
