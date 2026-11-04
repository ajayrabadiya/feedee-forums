<?php

namespace App\Http\Controllers;

use App\Enums\MediaType;
use App\Models\Post;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    /**
     * Sitemaps may list at most 50,000 URLs.
     */
    private const MAX_POSTS = 45000;

    /**
     * XML sitemap of the public pages and every post currently visible to the public.
     * URLs use APP_URL, so the live sitemap always points at the canonical domain.
     */
    public function __invoke(): Response
    {
        $posts = Post::query()
            ->published()
            ->with(['media' => fn ($query) => $query->where('media_type', MediaType::Image)])
            ->latest()
            ->latest('id')
            ->take(self::MAX_POSTS)
            ->get(['id', 'public_id', 'title', 'created_at', 'updated_at']);

        $pages = [
            ['url' => $this->url('home'), 'lastmod' => $posts->max('updated_at'), 'changefreq' => 'hourly', 'priority' => '1.0'],
            ['url' => $this->url('rules'), 'lastmod' => null, 'changefreq' => 'monthly', 'priority' => '0.6'],
            ['url' => $this->url('support'), 'lastmod' => null, 'changefreq' => 'monthly', 'priority' => '0.5'],
            ['url' => $this->url('contact'), 'lastmod' => null, 'changefreq' => 'yearly', 'priority' => '0.4'],
        ];

        return response()
            ->view('sitemap', [
                'pages' => $pages,
                'posts' => $posts,
                'postUrl' => fn (Post $post): string => $this->url('posts.show', $post),
            ])
            ->header('Content-Type', 'application/xml; charset=UTF-8')
            ->header('Cache-Control', 'public, max-age=900');
    }

    private function url(string $route, mixed $parameters = []): string
    {
        return rtrim((string) config('app.url'), '/').route($route, $parameters, absolute: false);
    }
}
