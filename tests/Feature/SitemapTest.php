<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\PostMedia;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use SimpleXMLElement;
use Tests\TestCase;

class SitemapTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.url' => 'https://feedeeforums.com']);
        Storage::fake(config('media.disk'));
    }

    /**
     * @return array{0: string, 1: array<string, SimpleXMLElement>}
     */
    private function sitemap(): array
    {
        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/xml; charset=UTF-8');

        $content = $response->getContent();
        $xml = new SimpleXMLElement($content);
        $urls = [];

        foreach ($xml->url as $url) {
            $urls[(string) $url->loc] = $url;
        }

        return [$content, $urls];
    }

    public function test_sitemap_lists_the_public_pages_on_the_live_domain(): void
    {
        [, $urls] = $this->sitemap();

        $this->assertSame([
            'https://feedeeforums.com/',
            'https://feedeeforums.com/rules',
            'https://feedeeforums.com/support',
            'https://feedeeforums.com/contact',
        ], array_keys($urls));
        $this->assertSame('1.0', (string) $urls['https://feedeeforums.com/']->priority);
        $this->assertSame('hourly', (string) $urls['https://feedeeforums.com/']->changefreq);
    }

    public function test_sitemap_lists_published_post_pages_without_direct_image_urls(): void
    {
        $this->travelTo(Carbon::parse('2026-11-04 10:00:00'));

        $post = Post::factory()->create(['title' => 'Sunday feast & more', 'created_at' => now()->subDays(2), 'updated_at' => now()->subDay()]);
        $image = PostMedia::factory()->for($post)->create();

        [$content, $urls] = $this->sitemap();
        $entry = $urls['https://feedeeforums.com/p/'.$post->public_id] ?? null;

        $this->assertNotNull($entry);
        $this->assertSame('2026-11-03T10:00:00+00:00', (string) $entry->lastmod);
        $this->assertSame('weekly', (string) $entry->changefreq);
        $this->assertSame('0.7', (string) $entry->priority);
        $this->assertSame('2026-11-03T10:00:00+00:00', (string) $urls['https://feedeeforums.com/']->lastmod);
        $this->assertStringNotContainsString('/storage/', $content);
        $this->assertStringNotContainsString($image->file_path, $content);
        $this->assertStringNotContainsString('xmlns:image', $content);
        $this->assertStringNotContainsString('<image:', $content);
        $this->assertCount(count($urls), array_unique(array_keys($urls)));
    }

    public function test_hidden_and_expired_posts_and_private_pages_are_left_out(): void
    {
        $pending = Post::factory()->pending()->create();
        $rejected = Post::factory()->rejected('Off topic')->create();
        $expired = Post::factory()->expired(now()->subDays(Post::LIFETIME_DAYS + 1))->create();

        $content = $this->get('/sitemap.xml')->getContent();

        foreach ([$pending, $rejected, $expired] as $post) {
            $this->assertStringNotContainsString('/p/'.$post->public_id, $content);
        }

        foreach (['/login', '/register', '/admin', '/dashboard', '/create-post', 'k7Xq92Lm'] as $private) {
            $this->assertStringNotContainsString($private.'<', $content);
        }
    }

    public function test_footer_links_to_the_generated_sitemap(): void
    {
        $this->assertFileDoesNotExist(public_path('sitemap.xml'));

        $this->get(route('home'))->assertSee('<a href="'.route('sitemap').'">Sitemap</a>', false);
    }
}
