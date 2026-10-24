@extends('layouts.app')

@section('title', "Finally got my grandma's cinnamon roll recipe right - Feedee Forums")
@section('description', 'Took me four attempts but the secret was letting the dough rest overnight in the fridge. Soft, sticky cinnamon rolls shared by maya_bakes on Feedee Forums.')

@push('meta')
    <link rel="canonical" href="{{ route('posts.show', 'k7Xq92Lm') }}">
    <meta property="og:type" content="article">
    <meta property="og:site_name" content="Feedee Forums">
    <meta property="og:title" content="Finally got my grandma's cinnamon roll recipe right">
    <meta property="og:description" content="Soft, sticky cinnamon rolls shared by maya_bakes on Feedee Forums.">
    <meta property="og:url" content="{{ route('posts.show', 'k7Xq92Lm') }}">
    <meta property="og:image" content="https://picsum.photos/seed/ff-p1/1200/630">
    <meta property="og:image:alt" content="Tray of freshly baked cinnamon rolls with white icing">
    <meta name="twitter:card" content="summary_large_image">
@endpush

@push('head')
    <link rel="preload" as="image" href="https://picsum.photos/seed/ff-p1/1200/750">
    <script type="application/ld+json">
    {
        "@@context": "https://schema.org",
        "@type": "DiscussionForumPosting",
        "headline": "Finally got my grandma's cinnamon roll recipe right",
        "url": "{{ route('posts.show', 'k7Xq92Lm') }}",
        "datePublished": "2026-10-23T21:05:00+05:30",
        "author": { "@type": "Person", "name": "maya_bakes" },
        "image": [
            "https://picsum.photos/seed/ff-p1/1200/750",
            "https://picsum.photos/seed/ff-p1b/1200/750",
            "https://picsum.photos/seed/ff-p1c/1200/750",
            "https://picsum.photos/seed/ff-p1d/1200/750"
        ],
        "video": {
            "@type": "VideoObject",
            "name": "Icing the cinnamon rolls",
            "description": "Short clip of icing being drizzled over warm cinnamon rolls.",
            "thumbnailUrl": "https://picsum.photos/seed/ff-v2/1200/675",
            "uploadDate": "2026-10-23T21:05:00+05:30",
            "duration": "PT0M18S"
        },
        "interactionStatistic": {
            "@type": "InteractionCounter",
            "interactionType": "https://schema.org/ViewAction",
            "userInteractionCount": 1204
        }
    }
    </script>
    <script type="application/ld+json">
    {
        "@@context": "https://schema.org",
        "@type": "BreadcrumbList",
        "itemListElement": [
            { "@type": "ListItem", "position": 1, "name": "Feed", "item": "{{ route('home') }}" },
            { "@type": "ListItem", "position": 2, "name": "Recipes", "item": "{{ url('topic/recipes') }}" },
            { "@type": "ListItem", "position": 3, "name": "Finally got my grandma's cinnamon roll recipe right" }
        ]
    }
    </script>
@endpush

@section('content')
    <main id="main" class="container page">
        <ol class="crumbs light" aria-label="Breadcrumb">
            <li><a href="{{ route('home') }}">Feed</a></li>
            <li><a href="#">Recipes</a></li>
            <li aria-current="page">Cinnamon rolls</li>
        </ol>

        <div class="row g-4">
            <div class="col-lg-8">
                <article class="post-card post-full">
                    <header class="post-head">
                        <img class="avatar" src="https://picsum.photos/seed/ff-u2/80/80" width="40" height="40" alt="maya_bakes profile photo">
                        <div class="post-by">
                            <a href="#" class="author">maya_bakes</a>
                            <span class="post-time"><time datetime="2026-10-23T21:05">October 23, 2026 at 9:05 PM</time></span>
                        </div>
                        <a href="#" class="topic t-amber">Recipes</a>
                        <div class="dropdown">
                            <button class="icon-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Post options"><i class="bi bi-three-dots"></i></button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="#" data-copy="{{ route('posts.show', 'k7Xq92Lm') }}" data-copy-msg="Post link copied"><i class="bi bi-link-45deg"></i>Copy link</a></li>
                                <li><a class="dropdown-item text-danger" href="#" data-bs-toggle="modal" data-bs-target="#reportModal"><i class="bi bi-flag"></i>Report post</a></li>
                            </ul>
                        </div>
                    </header>

                    <h1>Finally got my grandma's cinnamon roll recipe right</h1>

                    <div class="post-chips">
                        <span class="chip"><i class="bi bi-hash"></i>k7Xq92Lm</span>
                        <span class="chip"><i class="bi bi-images"></i>4 photos</span>
                        <span class="chip"><i class="bi bi-camera-video"></i>1 video</span>
                        <span class="chip"><i class="bi bi-eye"></i>1,204 views</span>
                    </div>

                    <div class="post-body">
                        <p>Took me four attempts but I finally cracked it. The secret was letting the dough rest overnight in the fridge, then giving it a slow second rise in the morning. Soft, sticky and exactly how I remember them from her kitchen.</p>
                        <p>I used brown sugar, plenty of cinnamon and a little cardamom in the filling, then a simple cream cheese icing on top while they were still warm. Happy to share the full measurements if anyone wants to try them!</p>
                    </div>

                    <div class="gallery">
                        <div class="gallery-main">
                            <img src="https://picsum.photos/seed/ff-p1/1200/750" width="1200" height="750" alt="Tray of freshly baked cinnamon rolls with white icing" fetchpriority="high">
                            <button class="gallery-prev" type="button" aria-label="Previous photo"><i class="bi bi-chevron-left"></i></button>
                            <button class="gallery-next" type="button" aria-label="Next photo"><i class="bi bi-chevron-right"></i></button>
                            <span class="gallery-count">1 / 4</span>
                        </div>
                        <div class="gallery-thumbs" aria-label="Post photos">
                            <button type="button" class="active" aria-current="true" data-full="https://picsum.photos/seed/ff-p1/1200/750"><img src="https://picsum.photos/seed/ff-p1/176/132" width="176" height="132" alt="Tray of freshly baked cinnamon rolls with white icing" loading="lazy" decoding="async"></button>
                            <button type="button" data-full="https://picsum.photos/seed/ff-p1b/1200/750"><img src="https://picsum.photos/seed/ff-p1b/176/132" width="176" height="132" alt="Rolled cinnamon dough before baking" loading="lazy" decoding="async"></button>
                            <button type="button" data-full="https://picsum.photos/seed/ff-p1c/1200/750"><img src="https://picsum.photos/seed/ff-p1c/176/132" width="176" height="132" alt="Close-up of a single cinnamon roll on a plate" loading="lazy" decoding="async"></button>
                            <button type="button" data-full="https://picsum.photos/seed/ff-p1d/1200/750"><img src="https://picsum.photos/seed/ff-p1d/176/132" width="176" height="132" alt="Grandma's handwritten recipe card on the counter" loading="lazy" decoding="async"></button>
                        </div>
                    </div>

                    <figure class="video-wrap">
                        <video controls playsinline preload="none" poster="https://picsum.photos/seed/ff-v2/1200/675" width="1200" height="675">
                            <source src="https://interactive-examples.mdn.mozilla.net/media/cc0-videos/flower.mp4" type="video/mp4">
                            Your browser does not support HTML5 video.
                        </video>
                        <figcaption class="media-caption"><i class="bi bi-camera-video me-1"></i>Icing the rolls while they're still warm &middot; 0:18</figcaption>
                    </figure>

                    <div class="post-actions">
                        <button type="button" class="btn-soft" data-copy="{{ route('posts.show', 'k7Xq92Lm') }}" data-copy-msg="Post link copied"><i class="bi bi-link-45deg"></i>Copy link</button>
                        <a class="btn-soft" href="https://twitter.com/intent/tweet?url={{ urlencode(route('posts.show', 'k7Xq92Lm')) }}" target="_blank" rel="noopener"><i class="bi bi-twitter-x"></i>Share</a>
                        <a class="btn-soft" href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(route('posts.show', 'k7Xq92Lm')) }}" target="_blank" rel="noopener"><i class="bi bi-facebook"></i>Share</a>
                        <button type="button" class="btn-soft danger ms-auto" data-bs-toggle="modal" data-bs-target="#reportModal"><i class="bi bi-flag"></i>Report</button>
                    </div>
                </article>
            </div>

            <!-- Sidebar -->
            <aside class="col-lg-4">
                <div class="sticky-lg">
                    <div class="box">
                        <div class="box-body pt-4">
                            <div class="author-card">
                                <img class="avatar" src="https://picsum.photos/seed/ff-u2/112/112" width="56" height="56" alt="maya_bakes profile photo" loading="lazy">
                                <div>
                                    <strong>maya_bakes</strong>
                                    <span>Member since March 2025</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="box">
                        <div class="box-head"><i class="bi bi-info-circle"></i>Post details</div>
                        <div class="box-body">
                            <ul class="info-list">
                                <li><span>Post ID</span><span>k7Xq92Lm</span></li>
                                <li><span>Published</span><span>Oct 23, 2026</span></li>
                                <li><span>Topic</span><span>Recipes</span></li>
                                <li><span>Media</span><span>4 photos, 1 video</span></li>
                            </ul>
                        </div>
                    </div>

                    <div class="box">
                        <div class="box-head"><i class="bi bi-clock-history"></i>More recent posts<a href="{{ route('home') }}">View feed</a></div>
                        <div class="pt-2">
                            <a href="{{ route('posts.show', 'Qw81rTzA') }}" class="mini-post">
                                <img src="https://picsum.photos/seed/ff-v1/128/128" width="64" height="64" alt="Coastal road at sunset" loading="lazy" decoding="async">
                                <div><strong>Sunset ride along the coast this evening</strong><span>jonah_rides &middot; 4 hours ago</span></div>
                            </a>
                            <a href="{{ route('posts.show', 'Hb7Ys0kD') }}" class="mini-post">
                                <img src="https://picsum.photos/seed/ff-p2/128/128" width="64" height="64" alt="Watercolour painting of a cafe corner" loading="lazy" decoding="async">
                                <div><strong>I painted my favourite cafe corner this weekend</strong><span>priya_draws &middot; Yesterday</span></div>
                            </a>
                            <a href="{{ route('posts.show', 'Vt2nE6sL') }}" class="mini-post">
                                <img src="https://picsum.photos/seed/ff-p3/128/128" width="64" height="64" alt="Lemon garlic chicken in a pan" loading="lazy" decoding="async">
                                <div><strong>One-pan lemon garlic chicken for busy weeknights</strong><span>sam_cooks &middot; Yesterday</span></div>
                            </a>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </main>

    @include('partials.report-modal')
@endsection
