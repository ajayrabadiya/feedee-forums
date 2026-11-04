{!! '<'.'?xml version="1.0" encoding="UTF-8"?>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">
@foreach ($pages as $page)
    <url>
        <loc>{{ $page['url'] }}</loc>
@if ($page['lastmod'])
        <lastmod>{{ $page['lastmod']->toAtomString() }}</lastmod>
@endif
        <changefreq>{{ $page['changefreq'] }}</changefreq>
        <priority>{{ $page['priority'] }}</priority>
    </url>
@endforeach
@foreach ($posts as $post)
    <url>
        <loc>{{ $postUrl($post) }}</loc>
        <lastmod>{{ $post->updated_at->toAtomString() }}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.7</priority>
@foreach ($post->media as $image)
        <image:image>
            <image:loc>{{ $image->url() }}</image:loc>
        </image:image>
@endforeach
    </url>
@endforeach
</urlset>
