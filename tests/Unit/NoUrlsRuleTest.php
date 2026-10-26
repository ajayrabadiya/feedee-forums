<?php

namespace Tests\Unit;

use App\Rules\NoUrls;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class NoUrlsRuleTest extends TestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function urlProvider(): array
    {
        return [
            'https link' => ['Check https://example.com now'],
            'http link' => ['http://spam.site'],
            'uppercase scheme' => ['HTTPS://EXAMPLE.COM'],
            'other scheme' => ['ftp://files.example.org/x'],
            'www prefix' => ['Visit www.myshop'],
            'bare domain' => ['Best deals at example.com'],
            'short link' => ['bit.ly/abc123'],
            'subdomain' => ['shop.example.co today'],
            'domain with path' => ['mysite.io/promo'],
            'bracket dot' => ['example[.]com'],
            'paren dot' => ['example(.)net'],
            'ip address' => ['go to 192.168.1.20 now'],
            'trailing punctuation' => ['Visit example.org!'],
        ];
    }

    /**
     * @return array<string, array{string}>
     */
    public static function plainTitleProvider(): array
    {
        return [
            'simple sentence' => ['Finally got my grandma\'s cinnamon roll recipe right'],
            'sentence with full stop' => ['Great day. Info coming soon'],
            'version number' => ['My 3.5 hour bake'],
            'abbreviation' => ['e.g. pancakes vs waffles'],
            'decimal price' => ['Lunch for 12.99 today'],
            'javascript name' => ['Learning Node.js this week'],
            'emoji and symbols' => ['Sunset ride 🌅 #coast'],
            'colon without slashes' => ['Recipe: lemon chicken'],
        ];
    }

    #[DataProvider('urlProvider')]
    public function test_titles_with_links_are_detected(string $title): void
    {
        $this->assertTrue(NoUrls::containsUrl($title));
    }

    #[DataProvider('plainTitleProvider')]
    public function test_normal_titles_are_allowed(string $title): void
    {
        $this->assertFalse(NoUrls::containsUrl($title));
    }
}
