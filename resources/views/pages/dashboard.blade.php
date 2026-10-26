@extends('layouts.app')

@section('title', 'Your dashboard - Feedee Forums')

@push('meta')
    <meta name="robots" content="noindex, nofollow">
@endpush

@section('content')
    <main id="main" class="container page">
        <ol class="crumbs light" aria-label="Breadcrumb">
            <li><a href="{{ route('home') }}">Feed</a></li>
            <li aria-current="page">Dashboard</li>
        </ol>

        <div class="row g-4">
            <div class="col-lg-8">
                <section class="panel-card">
                    <h1 class="section-title">Hi, {{ $user->name }}</h1>
                    <p class="section-sub">Here's an overview of your account and the posts you've shared.</p>

                    <div class="stats account-stats">
                        <div><strong>{{ number_format($postCount) }}</strong><span>Posts shared</span></div>
                        <div><strong>{{ number_format($livePostCount) }}</strong><span>Live now</span></div>
                        <div><strong>{{ $user->created_at->format('M Y') }}</strong><span>Member since</span></div>
                    </div>

                    <div class="form-actions">
                        <a href="{{ route('profile.edit') }}" class="btn-soft"><i class="bi bi-person"></i>Edit profile</a>
                        <a href="{{ route('posts.create') }}" class="btn-fill"><i class="bi bi-plus-lg"></i>Create Post</a>
                    </div>
                </section>

                <section class="panel-card">
                    <h2 class="form-section-title">Your recent posts</h2>
                    <p class="form-section-sub">Posts are permanently deleted {{ \App\Models\Post::LIFETIME_DAYS }} days after publishing.</p>

                    @forelse ($recentPosts as $post)
                        @php
                            $postImages = $post->media->filter->isImage();
                            $postVideos = $post->media->filter->isVideo();
                            $cover = $postImages->first();
                        @endphp
                        <article class="my-post">
                            <a href="{{ route('posts.show', $post) }}" class="my-post-thumb" tabindex="-1" aria-hidden="true">
                                @if ($cover)
                                    <img src="{{ $cover->url() }}" width="88" height="66" alt="" loading="lazy" decoding="async" data-media-fallback>
                                @elseif ($postVideos->isNotEmpty())
                                    <i class="bi bi-camera-video"></i>
                                @else
                                    <i class="bi bi-chat-square-text"></i>
                                @endif
                            </a>
                            <div class="my-post-info">
                                <h3><a href="{{ route('posts.show', $post) }}">{{ $post->title }}</a></h3>
                                <p class="my-post-meta">
                                    @if ($post->isPending())
                                        <span class="post-status pending"><i class="bi bi-hourglass-split"></i>Waiting for review</span>
                                    @elseif ($post->isRejected())
                                        <span class="post-status rejected" title="{{ $post->rejection_note }}"><i class="bi bi-x-circle"></i>Not approved: {{ $post->rejection_reason }}</span>
                                    @elseif ($post->is_featured)
                                        <span class="post-status featured"><i class="bi bi-star-fill"></i>Featured</span>
                                    @endif
                                    <span class="topic">{{ $post->topic }}</span>
                                    <span><i class="bi bi-clock"></i>Posted {{ $post->created_at->diffForHumans() }}</span>
                                    @if ($postImages->isNotEmpty())
                                        <span><i class="bi bi-images"></i>{{ trans_choice('{1} :count photo|[2,*] :count photos', $postImages->count()) }}</span>
                                    @endif
                                    @if ($postVideos->isNotEmpty())
                                        <span><i class="bi bi-camera-video"></i>{{ trans_choice('{1} :count video|[2,*] :count videos', $postVideos->count()) }}</span>
                                    @endif
                                    @if ($post->expiresAt()->isFuture())
                                        <span><i class="bi bi-hourglass-split"></i>Deleted {{ $post->expiresAt()->diffForHumans() }}</span>
                                    @else
                                        <span><i class="bi bi-trash3"></i>Scheduled for deletion</span>
                                    @endif
                                </p>
                            </div>
                            <div class="my-post-actions">
                                <a href="{{ route('posts.edit', $post) }}" class="icon-btn" aria-label="Edit post: {{ $post->title }}" title="Edit post"><i class="bi bi-pencil-square"></i></a>
                                <form method="post" action="{{ route('posts.destroy', $post) }}" class="my-post-delete" data-confirm="Delete this post and all of its photos and videos? This can't be undone.">
                                    @csrf
                                    @method('delete')
                                    <button type="submit" class="icon-btn" aria-label="Delete post: {{ $post->title }}" title="Delete post"><i class="bi bi-trash3"></i></button>
                                </form>
                            </div>
                        </article>
                    @empty
                        <div class="my-post-empty">
                            <i class="bi bi-images"></i>
                            <p>You haven't shared anything yet.</p>
                            <a href="{{ route('posts.create') }}" class="btn-fill sm"><i class="bi bi-plus-lg"></i>Create your first post</a>
                        </div>
                    @endforelse
                </section>
            </div>

            <aside class="col-lg-4">
                <div class="sticky-lg">
                    <div class="box">
                        <div class="box-head"><i class="bi bi-person-circle"></i>Your account</div>
                        <div class="box-body">
                            <dl class="account-details">
                                <dt>Username</dt>
                                <dd>{{ $user->username ?? '—' }}</dd>
                                <dt>Email</dt>
                                <dd>{{ $user->email }}</dd>
                                <dt>Account type</dt>
                                <dd>{{ $user->role->label() }}</dd>
                            </dl>
                            <form method="post" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="btn-soft danger w-100"><i class="bi bi-box-arrow-right"></i>Log out</button>
                            </form>
                        </div>
                    </div>

                    <div class="box">
                        <div class="box-head"><i class="bi bi-journal-text"></i>Quick rules</div>
                        <ol class="mini-rules">
                            <li>Be kind and respectful</li>
                            <li>Stay on topic</li>
                            <li>No spam or self-promotion</li>
                            <li>Respect everyone's privacy</li>
                        </ol>
                    </div>
                </div>
            </aside>
        </div>
    </main>
@endsection
