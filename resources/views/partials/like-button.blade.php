@php
    $liked = (bool) ($post->liked_by_viewer ?? false);
    $likeCount = (int) ($post->likes_count ?? 0);
    $likeLabel = trans_choice('{0} No likes yet|{1} 1 like|[2,*] :count likes', $likeCount);
@endphp
@auth
    <form method="post" action="{{ route('posts.like', $post) }}" class="like-form">
        @csrf
        <button type="submit" @class(['like-btn', $size ?? '', 'is-liked' => $liked]) aria-pressed="{{ $liked ? 'true' : 'false' }}" aria-label="{{ $liked ? 'Unlike' : 'Like' }} this post ({{ $likeLabel }})" title="{{ $liked ? 'Unlike' : 'Like' }}">
            <i class="bi {{ $liked ? 'bi-heart-fill' : 'bi-heart' }}" aria-hidden="true"></i><span class="like-count">{{ $likeCount }}</span>
        </button>
    </form>
@else
    <a href="{{ route('login', ['redirect' => route('posts.show', $post, false)]) }}" @class(['like-btn', $size ?? '']) aria-label="Log in to like this post ({{ $likeLabel }})" title="Log in to like">
        <i class="bi bi-heart" aria-hidden="true"></i><span class="like-count">{{ $likeCount }}</span>
    </a>
@endauth
