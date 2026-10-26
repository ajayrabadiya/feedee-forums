<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class FrontendPagesTest extends TestCase
{
    /**
     * @return array<string, array{string, string}>
     */
    public static function pageProvider(): array
    {
        return [
            'home' => ['/', 'Latest posts'],
            'rules' => ['/rules', 'Community Rules'],
            'contact' => ['/contact', 'Get in touch'],
            'support' => ['/support', 'Support Feedee Forums'],
            'single post' => ['/p/k7Xq92Lm', 'cinnamon roll recipe right'],
            'login' => ['/login', 'Log in'],
            'register' => ['/register', 'Create your account'],
        ];
    }

    #[DataProvider('pageProvider')]
    public function test_frontend_page_renders(string $uri, string $expectedText): void
    {
        $response = $this->get($uri);

        $response->assertOk();
        $response->assertSee($expectedText);
        $response->assertSee(asset('assets/css/style.css'), false);
        $response->assertDontSee('.html"', false);
    }

    public function test_unknown_page_renders_custom_not_found_page(): void
    {
        $response = $this->get('/this-page-does-not-exist');

        $response->assertNotFound();
        $response->assertSee('This post has floated away');
    }

    public function test_json_ld_schema_context_is_rendered_literally(): void
    {
        $response = $this->get('/');

        $response->assertSee('"@context": "https://schema.org"', false);
    }
}
