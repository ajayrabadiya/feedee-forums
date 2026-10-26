@extends('layouts.app')

@section('title', 'Feedee Forums - Share stories, recipes and everyday moments')
@section('description', 'Feedee Forums is a friendly community to share stories, recipes, art, meetups and everyday moments. Browse the latest posts, newest first.')

@push('meta')
    <link rel="canonical" href="{{ route('home') }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Feedee Forums">
    <meta property="og:title" content="Feedee Forums - Share stories, recipes and everyday moments">
    <meta property="og:description" content="A friendly community to share stories, recipes, art, meetups and everyday moments.">
    <meta property="og:url" content="{{ route('home') }}">
    <meta property="og:image" content="{{ asset('assets/images/logo.png') }}">
    <meta name="twitter:card" content="summary_large_image">
@endpush

@push('head')
    <script>try { if (localStorage.getItem('ff-feed-view') === 'grid') document.documentElement.setAttribute('data-feed-view', 'grid'); } catch (e) {}</script>
    <script type="application/ld+json">
    {
        "@@context": "https://schema.org",
        "@type": "WebSite",
        "name": "Feedee Forums",
        "url": "{{ route('home') }}",
        "potentialAction": {
            "@type": "SearchAction",
            "target": "{{ route('home') }}?q={search_term_string}",
            "query-input": "required name=search_term_string"
        }
    }
    </script>
@endpush

@section('content')
    <main id="main" class="container page">
        <div class="row g-4">

            <!-- Feed -->
            <div class="col-lg-8">

                <section class="welcome">
                    <span class="eyebrow"><i class="bi bi-stars"></i>Welcome to Feedee Forums</span>
                    <h1>Share the moments that make your day a little better.</h1>
                    <p>Post photos, short videos and stories with a kind, welcoming community. Every post stays live for 30 days.</p>
                    <div class="welcome-btns">
                        @auth
                            <a href="{{ route('posts.create') }}" class="btn-fill">Create a post</a>
                        @else
                            <a href="{{ route('register', ['redirect' => request()->getRequestUri()]) }}" class="btn-fill">Join the community</a>
                        @endauth
                        <a href="{{ route('rules') }}" class="btn-line">Read the rules</a>
                    </div>
                </section>

                <div class="feed-head">
                    <div>
                        <h2>Latest posts</h2>
                        <p>Newest first &middot; 20 posts per page</p>
                    </div>
                    <div class="feed-actions">
                        <div class="view-toggle" role="group" aria-label="Feed layout">
                            <button type="button" class="active" data-view="list" aria-pressed="true" title="List view"><i class="bi bi-view-list"></i><span>List</span></button>
                            <button type="button" data-view="grid" aria-pressed="false" title="Grid view"><i class="bi bi-grid"></i><span>Grid</span></button>
                        </div>
                        <a href="{{ route('posts.create') }}" class="btn-fill"><i class="bi bi-plus-lg"></i>Create Post</a>
                    </div>
                </div>

                <form class="feed-search" action="{{ route('home') }}" role="search">
                    <i class="bi bi-search" aria-hidden="true"></i>
                    <input type="search" name="q" value="{{ request('q') }}" placeholder="Search posts" aria-label="Search posts">
                </form>

                <div class="feed" id="feed">

                    <!-- Featured -->
                    <div class="day-divider"><span><i class="bi bi-star-fill text-warning me-1"></i>Featured</span></div>

                    <article class="post-card is-featured">
                        <span class="featured-tag"><i class="bi bi-pin-angle-fill"></i>Featured by moderators</span>
                        <header class="post-head">
                            <img class="avatar" src="https://picsum.photos/seed/ff-team/80/80" width="40" height="40" alt="Feedee Team profile photo" loading="lazy" decoding="async">
                            <div class="post-by">
                                <a href="#" class="author">Feedee Team</a>
                                <span class="post-time"><time datetime="2026-10-21T10:00">2 days ago</time></span>
                            </div>
                            <a href="#" class="topic t-orange">Announcements</a>
                            <div class="dropdown">
                                <button class="icon-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Post options"><i class="bi bi-three-dots"></i></button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="#" data-copy="{{ route('posts.show', 'aN4w8Qz1') }}" data-copy-msg="Post link copied"><i class="bi bi-link-45deg"></i>Copy link</a></li>
                                    <li><a class="dropdown-item text-danger" href="#" data-bs-toggle="modal" data-bs-target="#reportModal"><i class="bi bi-flag"></i>Report post</a></li>
                                </ul>
                            </div>
                        </header>
                        <h2 class="post-title"><a href="{{ route('posts.show', 'aN4w8Qz1') }}">Welcome to Feedee Forums - here's how posting works</a></h2>
                        <p class="post-text">Every post stays live for 30 days before it's permanently removed along with its photos and videos. Our moderators look after the community, so please take a minute to read the rules before you share.</p>
                        <footer class="post-foot">
                            <span><i class="bi bi-eye"></i>4.8k views</span>
                            <a href="{{ route('posts.show', 'aN4w8Qz1') }}" class="read-more">View post <i class="bi bi-arrow-right"></i></a>
                        </footer>
                    </article>

                    <!-- Today -->
                    <div class="day-divider"><span>Today</span></div>

                    <article class="post-card">
                        <header class="post-head">
                            <img class="avatar" src="https://picsum.photos/seed/ff-u4/80/80" width="40" height="40" alt="lena_w profile photo" loading="lazy" decoding="async">
                            <div class="post-by">
                                <a href="#" class="author">lena_w</a>
                                <span class="post-time"><time datetime="2026-10-23T22:50">25 minutes ago</time></span>
                            </div>
                            <a href="#" class="topic t-blue">Meetups</a>
                            <div class="dropdown">
                                <button class="icon-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Post options"><i class="bi bi-three-dots"></i></button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="#" data-copy="{{ route('posts.show', 'Lm3T9vQe') }}" data-copy-msg="Post link copied"><i class="bi bi-link-45deg"></i>Copy link</a></li>
                                    <li><a class="dropdown-item text-danger" href="#" data-bs-toggle="modal" data-bs-target="#reportModal"><i class="bi bi-flag"></i>Report post</a></li>
                                </ul>
                            </div>
                        </header>
                        <h2 class="post-title"><a href="{{ route('posts.show', 'Lm3T9vQe') }}">Anyone near Manchester up for a cafe meetup next month?</a></h2>
                        <p class="post-text">Thinking of a relaxed Saturday afternoon with coffee, cake and good company. Somewhere central and easy to reach. Let me know if you're interested and which weekends work for you!</p>
                        <footer class="post-foot">
                            <span><i class="bi bi-eye"></i>86 views</span>
                            <a href="{{ route('posts.show', 'Lm3T9vQe') }}" class="read-more">View post <i class="bi bi-arrow-right"></i></a>
                        </footer>
                    </article>

                    <article class="post-card">
                        <header class="post-head">
                            <img class="avatar" src="https://picsum.photos/seed/ff-u2/80/80" width="40" height="40" alt="maya_bakes profile photo" loading="lazy" decoding="async">
                            <div class="post-by">
                                <a href="#" class="author">maya_bakes</a>
                                <span class="post-time"><time datetime="2026-10-23T21:05">2 hours ago</time></span>
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
                        <h2 class="post-title"><a href="{{ route('posts.show', 'k7Xq92Lm') }}">Finally got my grandma's cinnamon roll recipe right</a></h2>
                        <p class="post-text">Took me four attempts but the secret was letting the dough rest overnight in the fridge. Soft, sticky and exactly how I remember them.</p>
                        <a href="{{ route('posts.show', 'k7Xq92Lm') }}" class="media-grid count-3" aria-label="View 4 photos">
                            <span class="media-item"><img src="https://picsum.photos/seed/ff-p1/800/600" width="800" height="600" alt="Tray of freshly baked cinnamon rolls with white icing" loading="lazy" decoding="async"></span>
                            <span class="media-item"><img src="https://picsum.photos/seed/ff-p1b/400/300" width="400" height="300" alt="Rolled cinnamon dough before baking" loading="lazy" decoding="async"></span>
                            <span class="media-item"><img src="https://picsum.photos/seed/ff-p1c/400/300" width="400" height="300" alt="Close-up of a single cinnamon roll on a plate" loading="lazy" decoding="async"><span class="media-more">+1</span></span>
                        </a>
                        <footer class="post-foot">
                            <span class="foot-media"><i class="bi bi-images"></i>4 photos</span>
                            <span><i class="bi bi-eye"></i>1.2k views</span>
                            <a href="{{ route('posts.show', 'k7Xq92Lm') }}" class="read-more">View post <i class="bi bi-arrow-right"></i></a>
                        </footer>
                    </article>

                    <article class="post-card">
                        <header class="post-head">
                            <img class="avatar" src="https://picsum.photos/seed/ff-u7/80/80" width="40" height="40" alt="jonah_rides profile photo" loading="lazy" decoding="async">
                            <div class="post-by">
                                <a href="#" class="author">jonah_rides</a>
                                <span class="post-time"><time datetime="2026-10-23T19:12">4 hours ago</time></span>
                            </div>
                            <a href="#" class="topic t-green">Outdoors</a>
                            <div class="dropdown">
                                <button class="icon-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Post options"><i class="bi bi-three-dots"></i></button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="#" data-copy="{{ route('posts.show', 'Qw81rTzA') }}" data-copy-msg="Post link copied"><i class="bi bi-link-45deg"></i>Copy link</a></li>
                                    <li><a class="dropdown-item text-danger" href="#" data-bs-toggle="modal" data-bs-target="#reportModal"><i class="bi bi-flag"></i>Report post</a></li>
                                </ul>
                            </div>
                        </header>
                        <h2 class="post-title"><a href="{{ route('posts.show', 'Qw81rTzA') }}">Sunset ride along the coast this evening</a></h2>
                        <a href="{{ route('posts.show', 'Qw81rTzA') }}" class="media-grid" aria-label="Play video">
                            <span class="media-item">
                                <img src="https://picsum.photos/seed/ff-v1/800/450" width="800" height="450" alt="Video thumbnail of a coastal road at sunset" loading="lazy" decoding="async">
                                <span class="media-play"><i class="bi bi-play-fill"></i></span>
                                <span class="media-badge"><i class="bi bi-camera-video"></i>0:42</span>
                            </span>
                        </a>
                        <footer class="post-foot">
                            <span class="foot-media"><i class="bi bi-camera-video"></i>Video</span>
                            <span><i class="bi bi-eye"></i>642 views</span>
                            <a href="{{ route('posts.show', 'Qw81rTzA') }}" class="read-more">View post <i class="bi bi-arrow-right"></i></a>
                        </footer>
                    </article>

                    <article class="post-card">
                        <header class="post-head">
                            <img class="avatar" src="https://picsum.photos/seed/ff-u3/80/80" width="40" height="40" alt="cozy_comet profile photo" loading="lazy" decoding="async">
                            <div class="post-by">
                                <a href="#" class="author">cozy_comet</a>
                                <span class="post-time"><time datetime="2026-10-23T17:30">6 hours ago</time></span>
                            </div>
                            <a href="#" class="topic t-purple">The Lounge</a>
                            <div class="dropdown">
                                <button class="icon-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Post options"><i class="bi bi-three-dots"></i></button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="#" data-copy="{{ route('posts.show', 'Zr5cH2pW') }}" data-copy-msg="Post link copied"><i class="bi bi-link-45deg"></i>Copy link</a></li>
                                    <li><a class="dropdown-item text-danger" href="#" data-bs-toggle="modal" data-bs-target="#reportModal"><i class="bi bi-flag"></i>Report post</a></li>
                                </ul>
                            </div>
                        </header>
                        <h2 class="post-title"><a href="{{ route('posts.show', 'Zr5cH2pW') }}">What's one small thing that always makes your day better?</a></h2>
                        <p class="post-text">For me it's the first coffee on a quiet morning, before anyone else is awake. Curious to hear what everyone else's is.</p>
                        <footer class="post-foot">
                            <span><i class="bi bi-eye"></i>930 views</span>
                            <a href="{{ route('posts.show', 'Zr5cH2pW') }}" class="read-more">View post <i class="bi bi-arrow-right"></i></a>
                        </footer>
                    </article>

                    <!-- Yesterday -->
                    <div class="day-divider"><span>Yesterday</span></div>

                    <article class="post-card">
                        <header class="post-head">
                            <img class="avatar" src="https://picsum.photos/seed/ff-u5/80/80" width="40" height="40" alt="priya_draws profile photo" loading="lazy" decoding="async">
                            <div class="post-by">
                                <a href="#" class="author">priya_draws</a>
                                <span class="post-time"><time datetime="2026-10-22T18:40">Yesterday at 6:40 PM</time></span>
                            </div>
                            <a href="#" class="topic t-pink">Art &amp; Stories</a>
                            <div class="dropdown">
                                <button class="icon-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Post options"><i class="bi bi-three-dots"></i></button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="#" data-copy="{{ route('posts.show', 'Hb7Ys0kD') }}" data-copy-msg="Post link copied"><i class="bi bi-link-45deg"></i>Copy link</a></li>
                                    <li><a class="dropdown-item text-danger" href="#" data-bs-toggle="modal" data-bs-target="#reportModal"><i class="bi bi-flag"></i>Report post</a></li>
                                </ul>
                            </div>
                        </header>
                        <h2 class="post-title"><a href="{{ route('posts.show', 'Hb7Ys0kD') }}">I painted my favourite cafe corner this weekend</a></h2>
                        <a href="{{ route('posts.show', 'Hb7Ys0kD') }}" class="media-grid count-2" aria-label="View 2 photos">
                            <span class="media-item"><img src="https://picsum.photos/seed/ff-p2/600/600" width="600" height="600" alt="Watercolour painting of a cosy cafe corner with plants" loading="lazy" decoding="async"></span>
                            <span class="media-item"><img src="https://picsum.photos/seed/ff-p2b/600/600" width="600" height="600" alt="The real cafe corner that inspired the painting" loading="lazy" decoding="async"></span>
                        </a>
                        <footer class="post-foot">
                            <span class="foot-media"><i class="bi bi-images"></i>2 photos</span>
                            <span><i class="bi bi-eye"></i>2.1k views</span>
                            <a href="{{ route('posts.show', 'Hb7Ys0kD') }}" class="read-more">View post <i class="bi bi-arrow-right"></i></a>
                        </footer>
                    </article>

                    <article class="post-card">
                        <header class="post-head">
                            <img class="avatar" src="https://picsum.photos/seed/ff-u8/80/80" width="40" height="40" alt="sam_cooks profile photo" loading="lazy" decoding="async">
                            <div class="post-by">
                                <a href="#" class="author">sam_cooks</a>
                                <span class="post-time"><time datetime="2026-10-22T13:15">Yesterday at 1:15 PM</time></span>
                            </div>
                            <a href="#" class="topic t-amber">Recipes</a>
                            <div class="dropdown">
                                <button class="icon-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Post options"><i class="bi bi-three-dots"></i></button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="#" data-copy="{{ route('posts.show', 'Vt2nE6sL') }}" data-copy-msg="Post link copied"><i class="bi bi-link-45deg"></i>Copy link</a></li>
                                    <li><a class="dropdown-item text-danger" href="#" data-bs-toggle="modal" data-bs-target="#reportModal"><i class="bi bi-flag"></i>Report post</a></li>
                                </ul>
                            </div>
                        </header>
                        <h2 class="post-title"><a href="{{ route('posts.show', 'Vt2nE6sL') }}">One-pan lemon garlic chicken for busy weeknights</a></h2>
                        <p class="post-text">Twenty minutes, one pan, very few dishes. Recipe and timings are in the post!</p>
                        <a href="{{ route('posts.show', 'Vt2nE6sL') }}" class="media-grid" aria-label="View photo">
                            <span class="media-item"><img src="https://picsum.photos/seed/ff-p3/800/450" width="800" height="450" alt="Lemon garlic chicken served in a cast iron pan" loading="lazy" decoding="async"></span>
                        </a>
                        <footer class="post-foot">
                            <span class="foot-media"><i class="bi bi-image"></i>1 photo</span>
                            <span><i class="bi bi-eye"></i>1.6k views</span>
                            <a href="{{ route('posts.show', 'Vt2nE6sL') }}" class="read-more">View post <i class="bi bi-arrow-right"></i></a>
                        </footer>
                    </article>

                    <article class="post-card">
                        <header class="post-head">
                            <img class="avatar" src="https://picsum.photos/seed/ff-u6/80/80" width="40" height="40" alt="maple_honey profile photo" loading="lazy" decoding="async">
                            <div class="post-by">
                                <a href="#" class="author">maple_honey</a>
                                <span class="post-time"><time datetime="2026-10-22T09:02">Yesterday at 9:02 AM</time></span>
                            </div>
                            <a href="#" class="topic t-blue">Introductions</a>
                            <div class="dropdown">
                                <button class="icon-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Post options"><i class="bi bi-three-dots"></i></button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="#" data-copy="{{ route('posts.show', 'Pk9dW3mX') }}" data-copy-msg="Post link copied"><i class="bi bi-link-45deg"></i>Copy link</a></li>
                                    <li><a class="dropdown-item text-danger" href="#" data-bs-toggle="modal" data-bs-target="#reportModal"><i class="bi bi-flag"></i>Report post</a></li>
                                </ul>
                            </div>
                        </header>
                        <h2 class="post-title"><a href="{{ route('posts.show', 'Pk9dW3mX') }}">Hi everyone! Long-time reader, first-time poster</a></h2>
                        <p class="post-text">I love baking, slow Sunday mornings and long conversations. Happy to finally join in and looking forward to getting to know you all.</p>
                        <footer class="post-foot">
                            <span><i class="bi bi-eye"></i>410 views</span>
                            <a href="{{ route('posts.show', 'Pk9dW3mX') }}" class="read-more">View post <i class="bi bi-arrow-right"></i></a>
                        </footer>
                    </article>

                    <!-- Older -->
                    <div class="day-divider"><span>September 26</span></div>

                    <article class="post-card">
                        <header class="post-head">
                            <img class="avatar" src="https://picsum.photos/seed/ff-u9/80/80" width="40" height="40" alt="ellie_garden profile photo" loading="lazy" decoding="async">
                            <div class="post-by">
                                <a href="#" class="author">ellie_garden</a>
                                <span class="post-time"><time datetime="2026-09-26T16:20">Sep 26 at 4:20 PM</time></span>
                            </div>
                            <a href="#" class="topic t-green">Outdoors</a>
                            <div class="dropdown">
                                <button class="icon-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Post options"><i class="bi bi-three-dots"></i></button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="#" data-copy="{{ route('posts.show', 'Ga4fR8uN') }}" data-copy-msg="Post link copied"><i class="bi bi-link-45deg"></i>Copy link</a></li>
                                    <li><a class="dropdown-item text-danger" href="#" data-bs-toggle="modal" data-bs-target="#reportModal"><i class="bi bi-flag"></i>Report post</a></li>
                                </ul>
                            </div>
                        </header>
                        <h2 class="post-title"><a href="{{ route('posts.show', 'Ga4fR8uN') }}">My little balcony garden is finally blooming</a></h2>
                        <a href="{{ route('posts.show', 'Ga4fR8uN') }}" class="media-grid count-3" aria-label="View 3 photos">
                            <span class="media-item"><img src="https://picsum.photos/seed/ff-p4/800/600" width="800" height="600" alt="Balcony garden with pots of colourful flowers" loading="lazy" decoding="async"></span>
                            <span class="media-item"><img src="https://picsum.photos/seed/ff-p4b/400/300" width="400" height="300" alt="Close-up of pink petunias" loading="lazy" decoding="async"></span>
                            <span class="media-item"><img src="https://picsum.photos/seed/ff-p4c/400/300" width="400" height="300" alt="Herb planter with basil and mint" loading="lazy" decoding="async"></span>
                        </a>
                        <footer class="post-foot">
                            <span class="foot-media"><i class="bi bi-images"></i>3 photos</span>
                            <span><i class="bi bi-eye"></i>3.4k views</span>
                            <a href="{{ route('posts.show', 'Ga4fR8uN') }}" class="read-more">View post <i class="bi bi-arrow-right"></i></a>
                        </footer>
                    </article>

                </div>

                <nav class="pager" aria-label="Feed pages">
                    <span class="pager-info">Page 1</span>
                    <ul class="pager-links">
                        <li><span class="disabled" aria-disabled="true"><i class="bi bi-chevron-left"></i>Prev</span></li>
                        <li><span class="current" aria-current="page">1</span></li>
                        <li><a href="{{ route('home', ['page' => 2]) }}">2</a></li>
                        <li><a href="{{ route('home', ['page' => 3]) }}">3</a></li>
                        <li><a href="{{ route('home', ['page' => 2]) }}" rel="next">Next<i class="bi bi-chevron-right"></i></a></li>
                    </ul>
                </nav>
            </div>

            <!-- Sidebar -->
            <aside class="col-lg-4">
                <div class="sticky-lg">
                    <div class="box">
                        <div class="box-head"><i class="bi bi-chat-heart"></i>About Feedee Forums</div>
                        <div class="box-body">
                            <p>Niche forum for belly stuffing and feedee content. You can only post photos or short videos (with a caption). Optional body text is allowed. Everything is moderated and permanently removed after 30 days.</p>
                            <div class="stats" data-site-stats hidden>
                                <div><strong data-stat="members"></strong><span>Members</span></div>
                                <div><strong data-stat="online"></strong><span>Online</span></div>
                            </div>
                            <a href="{{ route('posts.create') }}" class="btn-fill w-100"><i class="bi bi-plus-lg"></i>Create Post</a>
                        </div>
                    </div>

                    <div class="box">
                        <div class="box-head"><i class="bi bi-grid"></i>Topics</div>
                        <ul class="topic-list">
                            <li><a href="#"><span class="topic-icon t-orange"><i class="bi bi-megaphone"></i></span>Announcements</a></li>
                            <li><a href="#"><span class="topic-icon t-amber"><i class="bi bi-cup-hot"></i></span>Recipes</a></li>
                            <li><a href="#"><span class="topic-icon t-purple"><i class="bi bi-chat-dots"></i></span>The Lounge</a></li>
                            <li><a href="#"><span class="topic-icon t-blue"><i class="bi bi-calendar-event"></i></span>Meetups</a></li>
                            <li><a href="#"><span class="topic-icon t-pink"><i class="bi bi-palette"></i></span>Art &amp; Stories</a></li>
                            <li><a href="#"><span class="topic-icon t-green"><i class="bi bi-tree"></i></span>Outdoors</a></li>
                        </ul>
                    </div>

                    {{--
                    <div class="support-card">
                        <h3>Keep Feedee Forums running</h3>
                        <p>We're community funded. Chip in through YouPay or Cash App to help cover hosting and moderation.</p>
                        <div class="pay-row">
                            <a href="{{ route('support') }}" class="btn-white sm"><i class="bi bi-gift"></i>YouPay</a>
                            <a href="{{ route('support') }}" class="btn-white sm"><i class="bi bi-currency-dollar"></i>Cash App</a>
                        </div>
                    </div>
                    --}}
                </div>
            </aside>
        </div>
    </main>

    @include('partials.report-modal')
@endsection
