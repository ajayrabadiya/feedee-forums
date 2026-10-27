@extends('layouts.app')

@section('title', $post->title.' - Feedee Forums')
@section('description', \Illuminate\Support\Str::limit($post->body ?: $post->title, 155))

@push('meta')
    <link rel="canonical" href="{{ route('posts.show', $post) }}">
    <meta property="og:type" content="article">
    <meta property="og:site_name" content="Feedee Forums">
    <meta property="og:title" content="{{ $post->title }}">
    <meta property="og:url" content="{{ route('posts.show', $post) }}">
    @if ($images->isNotEmpty())
        <meta property="og:image" content="{{ $images->first()->url() }}">
    @endif
@endpush

@section('content')
    <main id="main" class="container page">
        <ol class="crumbs light" aria-label="Breadcrumb">
            <li><a href="{{ route('home') }}">Feed</a></li>
            <li>{{ $post->topic }}</li>
            <li aria-current="page">{{ \Illuminate\Support\Str::limit($post->title, 40) }}</li>
        </ol>

        <div class="row g-4">
            <div class="col-lg-8">
                <article class="post-card post-full">
                    <header class="post-head">
                        <span class="avatar avatar-icon" aria-hidden="true"><i class="bi bi-person-fill"></i></span>
                        <div class="post-by">
                            <span class="author">{{ $post->user->name }}</span>
                            <span class="post-time"><time datetime="{{ $post->created_at->toIso8601String() }}">{{ $post->created_at->format('F j, Y \a\t g:i A') }}</time></span>
                        </div>
                        <span class="topic">{{ $post->topic }}</span>
                        <div class="dropdown">
                            <button class="icon-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Post options"><i class="bi bi-three-dots"></i></button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                @can('update', $post)
                                    <li><a class="dropdown-item" href="{{ route('posts.edit', $post) }}"><i class="bi bi-pencil-square"></i>Edit post</a></li>
                                @endcan
                                <li><a class="dropdown-item" href="#" data-copy="{{ route('posts.show', $post) }}" data-copy-msg="Post link copied"><i class="bi bi-link-45deg"></i>Copy link</a></li>
                                <li><a class="dropdown-item text-danger" href="#" data-bs-toggle="modal" data-bs-target="#reportModal"><i class="bi bi-flag"></i>Report post</a></li>
                            </ul>
                        </div>
                    </header>

                    @if ($post->isPending())
                        <div class="callout mb-4" role="status">
                            <i class="bi bi-hourglass-split"></i>
                            <div>
                                <strong>Waiting for review</strong>
                                <p>Only the author and moderators can see this post until it's approved.</p>
                            </div>
                        </div>
                    @elseif ($post->isRejected())
                        <div class="callout error mb-4" role="status">
                            <i class="bi bi-x-circle"></i>
                            <div>
                                <strong>Not approved: {{ $post->rejection_reason }}</strong>
                                @if (filled($post->rejection_note))
                                    <p>{{ $post->rejection_note }}</p>
                                @endif
                                <p>Only the author and moderators can see this post. Edit it to fix the problem and it will be sent back for review.</p>
                            </div>
                        </div>
                    @endif

                    <h1>{{ $post->title }}</h1>

                    <div class="post-chips">
                        <span class="chip"><i class="bi bi-hash"></i>{{ $post->public_id }}</span>
                        @if ($images->isNotEmpty())
                            <span class="chip"><i class="bi bi-images"></i>{{ trans_choice('{1} :count photo|[2,*] :count photos', $images->count()) }}</span>
                        @endif
                        @if ($videos->isNotEmpty())
                            <span class="chip"><i class="bi bi-camera-video"></i>{{ trans_choice('{1} :count video|[2,*] :count videos', $videos->count()) }}</span>
                        @endif
                        @if ($post->isApproved())
                            <a href="#comments" class="chip"><i class="bi bi-chat"></i>{{ trans_choice('{0} No comments yet|{1} 1 comment|[2,*] :count comments', $post->comments->count()) }}</a>
                        @endif
                    </div>

                    @if (filled($post->body))
                        <div class="post-body">
                            @foreach (preg_split('/\R{2,}/', trim($post->body)) as $paragraph)
                                <p>{!! nl2br(e($paragraph)) !!}</p>
                            @endforeach
                        </div>
                    @endif

                    @if ($images->isNotEmpty())
                        @php
                            $firstImage = $images->first();
                        @endphp
                        <div class="gallery">
                            <div class="gallery-main">
                                <img src="{{ $firstImage->url() }}" alt="{{ $firstImage->alt_text ?: 'Photo 1 from '.$post->title }}" fetchpriority="high" data-media-fallback>
                                @if ($images->count() > 1)
                                    <button class="gallery-prev" type="button" aria-label="Previous photo"><i class="bi bi-chevron-left"></i></button>
                                    <button class="gallery-next" type="button" aria-label="Next photo"><i class="bi bi-chevron-right"></i></button>
                                    <span class="gallery-count">1 / {{ $images->count() }}</span>
                                @endif
                            </div>
                            @if ($images->count() > 1)
                                <div class="gallery-thumbs" aria-label="Post photos">
                                    @foreach ($images as $image)
                                        <button type="button" @class(['active' => $loop->first]) @if ($loop->first) aria-current="true" @endif data-full="{{ $image->url() }}"><img src="{{ $image->url() }}" width="176" height="132" alt="{{ $image->alt_text ?: 'Photo '.$loop->iteration.' from '.$post->title }}" loading="lazy" decoding="async" data-media-fallback></button>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endif

                    @foreach ($videos as $video)
                        <figure class="video-wrap">
                            <video controls playsinline preload="metadata" data-media-fallback>
                                <source src="{{ $video->url() }}" type="{{ $video->mime_type }}">
                                Your browser can't play this video. <a href="{{ $video->url() }}">Download it instead</a>.
                            </video>
                            <figcaption class="media-caption"><i class="bi bi-camera-video me-1"></i>Video {{ $loop->iteration }} of {{ $videos->count() }}</figcaption>
                        </figure>
                    @endforeach

                    <div class="post-actions">
                        @if ($post->isApproved())
                            @include('partials.like-button', ['post' => $post])
                        @endif
                        <button type="button" class="btn-soft" data-copy="{{ route('posts.show', $post) }}" data-copy-msg="Post link copied"><i class="bi bi-link-45deg"></i>Copy link</button>
                        @can('update', $post)
                            <a href="{{ route('posts.edit', $post) }}" class="btn-soft"><i class="bi bi-pencil-square"></i>Edit post</a>
                        @endcan
                        @can('delete', $post)
                            <form method="post" action="{{ route('posts.destroy', $post) }}" class="ms-auto" data-confirm="Delete this post and all of its photos and videos? This can't be undone.">
                                @csrf
                                @method('delete')
                                <button type="submit" class="btn-soft danger"><i class="bi bi-trash3"></i>Delete post</button>
                            </form>
                        @else
                            <button type="button" class="btn-soft danger ms-auto" data-bs-toggle="modal" data-bs-target="#reportModal"><i class="bi bi-flag"></i>Report</button>
                        @endcan
                    </div>
                </article>

                @if ($post->isApproved())
                    <section class="post-card comments" id="comments" aria-labelledby="commentsTitle">
                        <h2 class="comments-title" id="commentsTitle">
                            Comments <span class="comments-count">{{ $post->comments->count() }}</span>
                        </h2>

                        @auth
                            <form method="post" action="{{ route('posts.comments.store', $post) }}" class="comment-form">
                                @csrf
                                <span class="avatar comment-avatar tone-{{ auth()->id() % 5 }}" aria-hidden="true">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr(auth()->user()->name, 0, 1)) }}</span>
                                <div class="comment-form-body">
                                    <label for="commentBody" class="visually-hidden">Write a comment</label>
                                    <textarea @class(['form-control', 'has-error' => $errors->has('body')]) id="commentBody" name="body" rows="3" maxlength="{{ \App\Models\Comment::MAX_LENGTH }}" required placeholder="Write a friendly comment…" data-counter="#commentCount">{{ old('body') }}</textarea>
                                    @error('body')
                                        <p class="form-error" role="alert">{{ $message }}</p>
                                    @enderror
                                    <div class="comment-form-actions">
                                        <span class="char-count" id="commentCount">0 / {{ \App\Models\Comment::MAX_LENGTH }}</span>
                                        <button type="submit" class="btn-fill sm"><i class="bi bi-send"></i>Post comment</button>
                                    </div>
                                </div>
                            </form>
                        @else
                            <div class="comment-login">
                                <i class="bi bi-chat-heart"></i>
                                <p><a href="{{ route('login', ['redirect' => route('posts.show', $post, false).'#comments']) }}">Log in</a> or <a href="{{ route('register', ['redirect' => route('posts.show', $post, false).'#comments']) }}">sign up</a> to join the conversation.</p>
                            </div>
                        @endauth

                        @if ($post->comments->isEmpty())
                            <p class="comments-empty">No comments yet. Be the first to say something nice!</p>
                        @else
                            <ol class="comment-list">
                                @foreach ($post->comments as $comment)
                                    @php
                                        $isPostAuthor = $comment->user_id === $post->user_id;
                                    @endphp
                                    <li @class(['comment', 'is-author' => $isPostAuthor]) id="comment-{{ $comment->id }}">
                                        <span class="avatar comment-avatar tone-{{ $comment->user_id % 5 }}" aria-hidden="true">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($comment->user->name, 0, 1)) }}</span>
                                        <div class="comment-main">
                                            <div class="comment-head">
                                                <strong class="comment-author">{{ $comment->user->name }}</strong>
                                                @if ($isPostAuthor)
                                                    <span class="comment-badge"><i class="bi bi-patch-check-fill" aria-hidden="true"></i>Author</span>
                                                @endif
                                                <time datetime="{{ $comment->created_at->toIso8601String() }}" title="{{ $comment->created_at->format('M j, Y g:i A') }}">{{ $comment->created_at->diffForHumans() }}</time>
                                            </div>
                                            <p class="comment-body">{!! nl2br(e($comment->body)) !!}</p>
                                            @can('delete', $comment)
                                                <form method="post" action="{{ route('comments.destroy', $comment) }}" class="comment-delete" data-confirm="Delete this comment?">
                                                    @csrf
                                                    @method('delete')
                                                    <button type="submit" class="icon-btn" title="Delete comment" aria-label="Delete comment by {{ $comment->user->name }}"><i class="bi bi-trash3"></i></button>
                                                </form>
                                            @endcan
                                        </div>
                                    </li>
                                @endforeach
                            </ol>
                        @endif
                    </section>
                @endif
            </div>

            <aside class="col-lg-4">
                <div class="sticky-lg">
                    <div class="box">
                        <div class="box-body pt-4">
                            <div class="author-card">
                                <span class="avatar avatar-icon" aria-hidden="true"><i class="bi bi-person-fill"></i></span>
                                <div>
                                    <strong>{{ $post->user->name }}</strong>
                                    <span>Member since {{ $post->user->created_at->format('F Y') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="box">
                        <div class="box-head"><i class="bi bi-info-circle"></i>Post details</div>
                        <div class="box-body">
                            <ul class="info-list">
                                <li><span>Post ID</span><span>{{ $post->public_id }}</span></li>
                                <li><span>Published</span><span>{{ $post->created_at->format('M j, Y') }}</span></li>
                                <li><span>Topic</span><span>{{ $post->topic }}</span></li>
                                <li><span>Deleted on</span><span>{{ $post->expiresAt()->format('M j, Y') }}</span></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </main>

    @include('partials.report-modal')
@endsection
