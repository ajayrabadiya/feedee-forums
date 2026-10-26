@php
    $postUrl = route('posts.show', $post);
    $images = $post->media->filter->isImage()->values();
    $videos = $post->media->filter->isVideo()->values();
    $shownImages = $images->take(3);
    $hiddenImages = $images->count() - $shownImages->count();
    $postedAt = $post->created_at;
    $daysLeft = max(0, (int) ceil(now()->diffInDays($post->expiresAt(), false)));

    if ($postedAt->isToday()) {
        $postedLabel = $postedAt->diffForHumans();
    } elseif ($postedAt->isYesterday()) {
        $postedLabel = 'Yesterday at '.$postedAt->format('g:i A');
    } else {
        $postedLabel = $postedAt->format('M j \a\t g:i A');
    }
@endphp

<article class="post-card">
    <header class="post-head">
        <span class="avatar avatar-icon" aria-hidden="true"><i class="bi bi-person-fill"></i></span>
        <div class="post-by">
            <span class="author">{{ $post->user->name }}</span>
            <span class="post-time"><time datetime="{{ $postedAt->toIso8601String() }}">{{ $postedLabel }}</time></span>
        </div>
        <a href="{{ route('home', ['topic' => $post->topic]) }}" class="topic {{ $post->topicColor() }}">{{ $post->topic }}</a>
        <div class="dropdown">
            <button class="icon-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Post options"><i class="bi bi-three-dots"></i></button>
            <ul class="dropdown-menu dropdown-menu-end">
                @can('update', $post)
                    <li><a class="dropdown-item" href="{{ route('posts.edit', $post) }}"><i class="bi bi-pencil-square"></i>Edit post</a></li>
                @endcan
                <li><a class="dropdown-item" href="#" data-copy="{{ $postUrl }}" data-copy-msg="Post link copied"><i class="bi bi-link-45deg"></i>Copy link</a></li>
                <li><a class="dropdown-item text-danger" href="#" data-bs-toggle="modal" data-bs-target="#reportModal"><i class="bi bi-flag"></i>Report post</a></li>
            </ul>
        </div>
    </header>

    <h2 class="post-title"><a href="{{ $postUrl }}">{{ $post->title }}</a></h2>

    @if (filled($post->body))
        <p class="post-text">{{ \Illuminate\Support\Str::limit($post->body, 220) }}</p>
    @endif

    @if ($shownImages->isNotEmpty())
        <a href="{{ $postUrl }}" @class(['media-grid', 'count-'.$shownImages->count() => $shownImages->count() > 1]) aria-label="View {{ trans_choice('{1} :count photo|[2,*] :count photos', $images->count()) }}">
            @foreach ($shownImages as $image)
                <span class="media-item">
                    <img src="{{ $image->url() }}" alt="{{ $image->alt_text ?: 'Photo '.$loop->iteration.' from '.$post->title }}" loading="lazy" decoding="async" data-media-fallback>
                    @if ($loop->last && $hiddenImages > 0)
                        <span class="media-more">+{{ $hiddenImages }}</span>
                    @endif
                </span>
            @endforeach
        </a>
    @elseif ($videos->isNotEmpty())
        <a href="{{ $postUrl }}" class="media-grid" aria-label="Play video">
            <span class="media-item">
                <video muted playsinline preload="metadata" tabindex="-1" aria-hidden="true" data-media-fallback>
                    <source src="{{ $videos->first()->url() }}#t=0.1" type="{{ $videos->first()->mime_type }}">
                </video>
                <span class="media-play"><i class="bi bi-play-fill"></i></span>
                <span class="media-badge"><i class="bi bi-camera-video"></i>Video</span>
            </span>
        </a>
    @endif

    <footer class="post-foot">
        @if ($images->isNotEmpty())
            <span class="foot-media"><i class="bi {{ $images->count() === 1 ? 'bi-image' : 'bi-images' }}"></i>{{ trans_choice('{1} :count photo|[2,*] :count photos', $images->count()) }}</span>
        @endif
        @if ($videos->isNotEmpty())
            <span class="foot-media"><i class="bi bi-camera-video"></i>{{ trans_choice('{1} :count video|[2,*] :count videos', $videos->count()) }}</span>
        @endif
        <span><i class="bi bi-hourglass-split"></i>{{ trans_choice('{0} Expires today|{1} 1 day left|[2,*] :count days left', $daysLeft) }}</span>
        <a href="{{ $postUrl }}" class="read-more">View post <i class="bi bi-arrow-right"></i></a>
    </footer>
</article>
