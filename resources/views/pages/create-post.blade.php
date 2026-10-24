@extends('layouts.app')

@section('title', 'Create a Post - Feedee Forums')
@section('description', 'Share photos, a short video or a story with the Feedee Forums community.')

@push('meta')
    <meta name="robots" content="noindex, nofollow">
@endpush

@section('content')
    <main id="main" class="container page">
        <ol class="crumbs light" aria-label="Breadcrumb">
            <li><a href="{{ route('home') }}">Feed</a></li>
            <li aria-current="page">Create a post</li>
        </ol>

        <div class="row g-4">
            <div class="col-lg-8">
                <form class="panel-card" id="postForm" action="{{ route('posts.create') }}" method="post" enctype="multipart/form-data" data-demo="Thanks for sharing! Your post has been published." data-reset>
                    @csrf
                    <h1 class="section-title">Create a post</h1>
                    <p class="section-sub">Share a story, a few photos or a short video. Keep it kind and follow the community rules.</p>

                    <div class="form-section">
                        <div class="mb-3">
                            <div class="label-row">
                                <label class="form-label" for="title">Title</label>
                                <span class="char-count" id="titleCount">0 / 150</span>
                            </div>
                            <input type="text" class="form-control" id="title" name="title" value="{{ old('title') }}" maxlength="150" required placeholder="Give your post a clear, friendly title" data-counter="#titleCount">
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="topic">Topic</label>
                            <select class="form-select" id="topic" name="topic" required>
                                <option value="" selected disabled>Choose a topic</option>
                                <option>Recipes</option>
                                <option>The Lounge</option>
                                <option>Meetups</option>
                                <option>Art &amp; Stories</option>
                                <option>Outdoors</option>
                                <option>Introductions</option>
                            </select>
                        </div>

                        <div>
                            <div class="label-row">
                                <label class="form-label" for="body">Your post <span class="opt">(optional)</span></label>
                                <span class="char-count" id="bodyCount">0 / 3000</span>
                            </div>
                            <textarea class="form-control" id="body" name="body" maxlength="3000" placeholder="What would you like to share?" data-counter="#bodyCount">{{ old('body') }}</textarea>
                        </div>
                    </div>

                    <div class="form-section">
                        <h2 class="form-section-title">Photos</h2>
                        <p class="form-section-sub">Up to 10 photos. JPG, PNG or WebP, 8 MB max each. Add a short description to each photo so everyone can enjoy it.</p>

                        <label class="dropzone" data-kind="image" data-preview="#photoPreview" data-error="#photoError">
                            <input type="file" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple aria-describedby="photoError">
                            <span class="dz-icon"><i class="bi bi-images"></i></span>
                            <strong>Drag photos here or <span>browse</span></strong>
                            <small>Photos are resized and stripped of location data automatically</small>
                        </label>
                        <div class="upload-error" id="photoError" aria-live="polite"></div>
                        <div class="preview-grid" id="photoPreview"></div>
                    </div>

                    <div class="form-section">
                        <h2 class="form-section-title">Short video</h2>
                        <p class="form-section-sub">One video up to 60 seconds. MP4, WebM or MOV, 50 MB max.</p>

                        <label class="dropzone" data-kind="video" data-preview="#videoPreview" data-error="#videoError">
                            <input type="file" name="video" accept="video/mp4,video/webm,video/quicktime" aria-describedby="videoError">
                            <span class="dz-icon"><i class="bi bi-camera-video"></i></span>
                            <strong>Drag a video here or <span>browse</span></strong>
                            <small>A thumbnail is generated for the feed after upload</small>
                        </label>
                        <div class="upload-error" id="videoError" aria-live="polite"></div>
                        <div class="preview-grid" id="videoPreview"></div>
                    </div>

                    <div class="form-section">
                        <div class="callout info mb-3">
                            <i class="bi bi-hourglass-split"></i>
                            <div>
                                <strong>Posts are temporary</strong>
                                <p>Your post and all of its photos and videos will be permanently deleted 30 days after publishing. This can't be undone.</p>
                            </div>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="agree" required>
                            <label class="form-check-label" for="agree">I've read the <a href="{{ route('rules') }}">community rules</a> and I own or have permission to share this media.</label>
                        </div>
                    </div>

                    <div class="form-actions">
                        <a href="{{ route('home') }}" class="btn-soft">Cancel</a>
                        <button type="submit" class="btn-fill"><i class="bi bi-send"></i>Publish post</button>
                    </div>
                </form>
            </div>

            <!-- Sidebar -->
            <aside class="col-lg-4">
                <div class="sticky-lg">
                    <div class="box">
                        <div class="box-head"><i class="bi bi-signpost-split"></i>What happens next</div>
                        <ol class="lifecycle">
                            <li><span class="step-icon"><i class="bi bi-broadcast"></i></span><div><strong>Live in the feed</strong><span>Appears at the top of the latest posts</span></div></li>
                            <li><span class="step-icon"><i class="bi bi-shield-check"></i></span><div><strong>Moderators keep watch</strong><span>Posts that break the rules are removed</span></div></li>
                            <li><span class="step-icon"><i class="bi bi-calendar2-x"></i></span><div><strong>Deleted after 30 days</strong><span>Post and media removed permanently</span></div></li>
                        </ol>
                    </div>

                    <div class="box">
                        <div class="box-head"><i class="bi bi-lightbulb"></i>Posting tips</div>
                        <div class="box-body">
                            <ul class="check-list">
                                <li><i class="bi bi-check-circle-fill"></i>Pick the topic that fits best</li>
                                <li><i class="bi bi-check-circle-fill"></i>Describe each photo in a few words</li>
                                <li><i class="bi bi-check-circle-fill"></i>Keep videos short and steady</li>
                                <li><i class="bi bi-check-circle-fill"></i>Blur faces of people who didn't agree to be shown</li>
                            </ul>
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
