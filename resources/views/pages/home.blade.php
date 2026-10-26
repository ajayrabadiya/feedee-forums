@extends('layouts.app')

@section('title', 'Feedee Forums - Share stories, recipes and everyday moments')
@section('description', 'Feedee Forums is a friendly community to share stories, recipes, art, meetups and everyday moments. Browse the latest posts, newest first.')

@push('meta')
    <link rel="canonical" href="{{ route('home') }}">
    @if ($search !== '' || $topic || $posts->currentPage() > 1)
        <meta name="robots" content="noindex, follow">
    @endif
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
    @php
        $filtered = $search !== '' || $topic;

        $dayLabel = function (\Illuminate\Support\Carbon $date): string {
            if ($date->isToday()) {
                return 'Today';
            }

            if ($date->isYesterday()) {
                return 'Yesterday';
            }

            return $date->format($date->isCurrentYear() ? 'F j' : 'F j, Y');
        };
    @endphp

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
                        <h2>{{ $topic ?? 'Latest posts' }}</h2>
                        @if ($search !== '')
                            <p>{{ trans_choice('{0} No results|{1} :count result|[2,*] :count results', $posts->total()) }} for &ldquo;{{ $search }}&rdquo; &middot; <a href="{{ route('home', array_filter(['topic' => $topic])) }}" class="feed-clear">Clear search</a></p>
                        @elseif ($topic)
                            <p>{{ trans_choice('{0} No posts|{1} :count post|[2,*] :count posts', $posts->total()) }} in this topic &middot; <a href="{{ route('home') }}" class="feed-clear">All topics</a></p>
                        @else
                            <p>Newest first &middot; {{ $posts->perPage() }} posts per page</p>
                        @endif
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
                    @if ($topic)
                        <input type="hidden" name="topic" value="{{ $topic }}">
                    @endif
                    <input type="search" name="q" value="{{ $search }}" maxlength="100" placeholder="{{ $topic ? 'Search posts in '.$topic : 'Search posts' }}" aria-label="Search posts">
                </form>

                <div class="feed" id="feed">
                    @forelse ($posts->groupBy(fn ($post) => $post->created_at->toDateString()) as $dayPosts)
                        <div class="day-divider"><span>{{ $dayLabel($dayPosts->first()->created_at) }}</span></div>

                        @foreach ($dayPosts as $post)
                            @include('partials.post-card', ['post' => $post])
                        @endforeach
                    @empty
                        <div class="feed-empty">
                            <span class="feed-empty-icon"><i class="bi {{ $filtered ? 'bi-search' : 'bi-images' }}"></i></span>
                            @if ($filtered)
                                <h3>No posts found</h3>
                                <p>Try a different search or browse all topics.</p>
                                <a href="{{ route('home') }}" class="btn-line">See all posts</a>
                            @else
                                <h3>No posts yet</h3>
                                <p>Be the first to share a story, a few photos or a short video with the community.</p>
                                <a href="{{ route('posts.create') }}" class="btn-fill"><i class="bi bi-plus-lg"></i>Create the first post</a>
                            @endif
                        </div>
                    @endforelse
                </div>

                @if ($posts->hasPages())
                    @php
                        $current = $posts->currentPage();
                        $last = $posts->lastPage();
                        $start = max(1, $current - 2);
                        $end = min($last, $current + 2);
                    @endphp
                    <nav class="pager" aria-label="Feed pages">
                        <span class="pager-info">Page {{ $current }} of {{ $last }}</span>
                        <ul class="pager-links">
                            <li>
                                @if ($posts->onFirstPage())
                                    <span class="disabled" aria-disabled="true"><i class="bi bi-chevron-left"></i>Prev</span>
                                @else
                                    <a href="{{ $posts->previousPageUrl() }}" rel="prev"><i class="bi bi-chevron-left"></i>Prev</a>
                                @endif
                            </li>
                            @if ($start > 1)
                                <li><a href="{{ $posts->url(1) }}">1</a></li>
                                @if ($start > 2)
                                    <li><span class="gap" aria-hidden="true">&hellip;</span></li>
                                @endif
                            @endif
                            @foreach (range($start, $end) as $page)
                                <li>
                                    @if ($page === $current)
                                        <span class="current" aria-current="page">{{ $page }}</span>
                                    @else
                                        <a href="{{ $posts->url($page) }}">{{ $page }}</a>
                                    @endif
                                </li>
                            @endforeach
                            @if ($end < $last)
                                @if ($end < $last - 1)
                                    <li><span class="gap" aria-hidden="true">&hellip;</span></li>
                                @endif
                                <li><a href="{{ $posts->url($last) }}">{{ $last }}</a></li>
                            @endif
                            <li>
                                @if ($posts->hasMorePages())
                                    <a href="{{ $posts->nextPageUrl() }}" rel="next">Next<i class="bi bi-chevron-right"></i></a>
                                @else
                                    <span class="disabled" aria-disabled="true">Next<i class="bi bi-chevron-right"></i></span>
                                @endif
                            </li>
                        </ul>
                    </nav>
                @endif
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
                            <li><a href="{{ route('home') }}" @class(['active' => ! $topic]) @if (! $topic) aria-current="page" @endif><span class="topic-icon t-orange"><i class="bi bi-collection"></i></span>All posts</a></li>
                            @foreach ($topicStyles as $name => $style)
                                <li><a href="{{ route('home', ['topic' => $name]) }}" @class(['active' => $topic === $name]) @if ($topic === $name) aria-current="page" @endif><span class="topic-icon {{ $style['color'] }}"><i class="bi {{ $style['icon'] }}"></i></span>{{ $name }}</a></li>
                            @endforeach
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
