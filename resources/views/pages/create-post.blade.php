@php
    $editing = isset($post);
    $formatKb = fn (int $bytes) => \App\Http\Requests\StorePostRequest::formatKb((int) ceil($bytes / 1024));
    $removing = array_map('intval', array_filter((array) old('remove_media', []), 'is_numeric'));
@endphp

@extends('layouts.app')

@section('title', ($editing ? 'Edit post' : 'Create a Post').' - Feedee Forums')
@section('description', 'Share photos, a short video or a story with the Feedee Forums community.')

@push('meta')
    <meta name="robots" content="noindex, nofollow">
@endpush

@section('content')
    <main id="main" class="container page">
        <ol class="crumbs light" aria-label="Breadcrumb">
            <li><a href="{{ route('home') }}">Feed</a></li>
            @if ($editing)
                <li><a href="{{ route('posts.show', $post) }}">{{ \Illuminate\Support\Str::limit($post->title, 40) }}</a></li>
                <li aria-current="page">Edit</li>
            @else
                <li aria-current="page">Create a post</li>
            @endif
        </ol>

        <div class="row g-4">
            <div class="col-lg-8">
                <form class="panel-card" id="postForm" action="{{ $editing ? route('posts.update', $post) : route('posts.store') }}" method="post" enctype="multipart/form-data">
                    @csrf
                    @if ($editing)
                        @method('put')
                        <h1 class="section-title">Edit post</h1>
                        <p class="section-sub">Update your title, topic and text, remove photos or videos, or add new ones.</p>
                    @else
                        <h1 class="section-title">Create a post</h1>
                        <p class="section-sub">Share a story, a few photos or short videos. Keep it kind and follow the community rules.</p>
                    @endif

                    @if ($errors->any())
                        <div class="callout error mb-4" role="alert">
                            <i class="bi bi-exclamation-circle"></i>
                            <div>
                                <strong>{{ $errors->first('media') ?: 'Please fix the highlighted fields' }}</strong>
                                <p>{{ $editing ? "Your changes haven't been saved yet." : "Your post hasn't been published yet." }} For security, browsers don't keep selected files, so please attach any new photos and videos again.</p>
                            </div>
                        </div>
                    @endif

                    <div class="form-section">
                        <div class="mb-3">
                            <div class="label-row">
                                <label class="form-label" for="title">Title</label>
                                <span class="char-count" id="titleCount">0 / 150</span>
                            </div>
                            <input type="text" @class(['form-control', 'has-error' => $errors->has('title')]) id="title" name="title" value="{{ old('title', $editing ? $post->title : null) }}" maxlength="150" required placeholder="Give your post a clear, friendly title" data-counter="#titleCount">
                            @error('title')
                                <p class="form-error" role="alert">{{ $message }}</p>
                            @enderror
                        </div>

                        @php
                            $selectedTopic = old('topic', $editing ? $post->topic : null);
                        @endphp
                        <div class="mb-3">
                            <label class="form-label" for="topic">Topic</label>
                            <select @class(['form-select', 'has-error' => $errors->has('topic')]) id="topic" name="topic" required>
                                <option value="" @selected(! $selectedTopic) disabled>Choose a topic</option>
                                @foreach ($topics as $topic)
                                    <option value="{{ $topic }}" @selected($selectedTopic === $topic)>{{ $topic }}</option>
                                @endforeach
                            </select>
                            @error('topic')
                                <p class="form-error" role="alert">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <div class="label-row">
                                <label class="form-label" for="body">Your post <span class="opt">(optional)</span></label>
                                <span class="char-count" id="bodyCount">0 / 3000</span>
                            </div>
                            <textarea @class(['form-control', 'has-error' => $errors->has('body')]) id="body" name="body" maxlength="3000" placeholder="What would you like to share?" data-counter="#bodyCount">{{ old('body', $editing ? $post->body : null) }}</textarea>
                            @error('body')
                                <p class="form-error" role="alert">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    @php
                        $photoErrors = collect($errors->get('photos*'))->merge($errors->get('alt_text*'))->merge($errors->get('remove_media*'))->flatten()->unique();
                        $videoErrors = collect($errors->get('videos*'))->flatten()->unique();
                    @endphp

                    <div class="form-section">
                        <h2 class="form-section-title">Photos <span class="opt">(optional)</span></h2>
                        <p class="form-section-sub">Up to {{ $images['max_files'] }} photos. {{ $images['label'] }}, {{ \App\Http\Requests\StorePostRequest::formatKb($images['max_kb']) }} max each. Add a short description to each photo so everyone can enjoy it.</p>

                        @if ($editing && $existingImages->isNotEmpty())
                            <p class="existing-label">Current photos <span>Select <i class="bi bi-x-lg"></i> to remove a photo when you save.</span></p>
                            <div class="preview-grid existing-grid" id="existingPhotos">
                                @foreach ($existingImages as $media)
                                    @php
                                        $name = $media->original_name ?: 'Photo '.$loop->iteration;
                                    @endphp
                                    <div @class(['preview-item', 'existing-item', 'is-removed' => in_array($media->id, $removing, true)])>
                                        <img src="{{ $media->url() }}" alt="{{ $media->alt_text ?: $name }}" loading="lazy" decoding="async" data-media-fallback>
                                        <span class="preview-meta">{{ $formatKb($media->file_size) }}</span>
                                        <span class="preview-name" title="{{ $name }}">{{ $name }}</span>
                                        <label class="preview-remove" title="Remove this photo">
                                            <input type="checkbox" class="visually-hidden" name="remove_media[]" value="{{ $media->id }}" @checked(in_array($media->id, $removing, true)) data-remove-toggle>
                                            <i class="bi bi-x-lg" aria-hidden="true"></i>
                                            <span class="visually-hidden">Remove {{ $name }}</span>
                                        </label>
                                        <span class="removed-badge">Will be removed</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <label @class(['dropzone', 'has-error' => $photoErrors->isNotEmpty()]) data-kind="image" data-preview="#photoPreview" data-error="#photoError" data-existing="#existingPhotos" data-max-files="{{ $images['max_files'] }}" data-max-size="{{ $images['max_kb'] * 1024 }}" data-types="{{ implode(',', $images['mime_types']) }}" data-label="{{ $images['label'] }}">
                            <input type="file" name="photos[]" accept="{{ implode(',', $images['mime_types']) }}" multiple aria-describedby="photoError">
                            <span class="dz-icon"><i class="bi bi-images"></i></span>
                            <strong>{{ $editing ? 'Add more photos' : 'Drag photos here' }} or <span>browse</span></strong>
                            <small>Remove location data from photos before sharing them</small>
                        </label>
                        <div class="upload-error" id="photoError" aria-live="polite">@foreach ($photoErrors as $message)<div>{{ $message }}</div>@endforeach</div>
                        <div class="preview-grid" id="photoPreview"></div>
                    </div>

                    <div class="form-section">
                        <h2 class="form-section-title">Short videos <span class="opt">(optional)</span></h2>
                        <p class="form-section-sub">Up to {{ $videos['max_files'] }} videos, {{ $videos['max_seconds'] }} seconds or shorter each. {{ $videos['label'] }}, {{ \App\Http\Requests\StorePostRequest::formatKb($videos['max_kb']) }} max each.</p>

                        @if ($editing && $existingVideos->isNotEmpty())
                            <p class="existing-label">Current videos <span>Select <i class="bi bi-x-lg"></i> to remove a video when you save.</span></p>
                            <div class="preview-grid existing-grid" id="existingVideos">
                                @foreach ($existingVideos as $media)
                                    @php
                                        $name = $media->original_name ?: 'Video '.$loop->iteration;
                                    @endphp
                                    <div @class(['preview-item', 'existing-item', 'is-removed' => in_array($media->id, $removing, true)])>
                                        <video src="{{ $media->url() }}" muted playsinline preload="metadata" aria-label="{{ $name }}" data-media-fallback></video>
                                        <span class="preview-meta">{{ $formatKb($media->file_size) }}</span>
                                        <span class="preview-name" title="{{ $name }}">{{ $name }}</span>
                                        <label class="preview-remove" title="Remove this video">
                                            <input type="checkbox" class="visually-hidden" name="remove_media[]" value="{{ $media->id }}" @checked(in_array($media->id, $removing, true)) data-remove-toggle>
                                            <i class="bi bi-x-lg" aria-hidden="true"></i>
                                            <span class="visually-hidden">Remove {{ $name }}</span>
                                        </label>
                                        <span class="removed-badge">Will be removed</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <label @class(['dropzone', 'has-error' => $videoErrors->isNotEmpty()]) data-kind="video" data-preview="#videoPreview" data-error="#videoError" data-existing="#existingVideos" data-max-files="{{ $videos['max_files'] }}" data-max-size="{{ $videos['max_kb'] * 1024 }}" data-max-seconds="{{ $videos['max_seconds'] }}" data-types="{{ implode(',', $videos['mime_types']) }}" data-label="{{ $videos['label'] }}">
                            <input type="file" name="videos[]" accept="{{ implode(',', $videos['mime_types']) }}" multiple aria-describedby="videoError">
                            <span class="dz-icon"><i class="bi bi-camera-video"></i></span>
                            <strong>{{ $editing ? 'Add more videos' : 'Drag videos here' }} or <span>browse</span></strong>
                            <small>Videos play right inside your post</small>
                        </label>
                        <div class="upload-error" id="videoError" aria-live="polite">@foreach ($videoErrors as $message)<div>{{ $message }}</div>@endforeach</div>
                        <div class="preview-grid" id="videoPreview"></div>
                    </div>

                    <div class="form-section">
                        <div class="callout info mb-3">
                            <i class="bi bi-hourglass-split"></i>
                            <div>
                                <strong>Posts are temporary</strong>
                                @if ($editing)
                                    <p>Editing doesn't extend your post's life. It will still be permanently deleted on {{ $post->expiresAt()->format('F j, Y') }}, along with all of its photos and videos.</p>
                                @else
                                    <p>Your post and all of its photos and videos will be permanently deleted 30 days after publishing. This can't be undone.</p>
                                @endif
                            </div>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="agree" name="agree" value="1" @required(! $editing) @checked(old('agree'))>
                            <label class="form-check-label" for="agree">I've read the <a href="{{ route('rules') }}">community rules</a> and I own or have permission to share this media.@if ($editing) <span class="opt">(required when adding photos or videos)</span>@endif</label>
                            @error('agree')
                                <p class="form-error" role="alert">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="form-actions">
                        @if ($editing)
                            <a href="{{ route('posts.show', $post) }}" class="btn-soft">Cancel</a>
                            <button type="submit" class="btn-fill"><i class="bi bi-check-lg"></i>Save changes</button>
                        @else
                            <a href="{{ route('home') }}" class="btn-soft">Cancel</a>
                            <button type="submit" class="btn-fill"><i class="bi bi-send"></i>Publish post</button>
                        @endif
                    </div>
                </form>
            </div>

            <!-- Sidebar -->
            <aside class="col-lg-4">
                <div class="sticky-lg">
                    @if ($editing)
                        <div class="box">
                            <div class="box-head"><i class="bi bi-pencil-square"></i>Editing your post</div>
                            <ol class="lifecycle">
                                <li><span class="step-icon"><i class="bi bi-link-45deg"></i></span><div><strong>Same link</strong><span>Your post keeps its ID #{{ $post->public_id }}</span></div></li>
                                <li><span class="step-icon"><i class="bi bi-images"></i></span><div><strong>Media you keep stays</strong><span>Only photos and videos you remove are deleted</span></div></li>
                                <li><span class="step-icon"><i class="bi bi-calendar2-x"></i></span><div><strong>Same deletion date</strong><span>{{ $post->expiresAt()->format('M j, Y') }}</span></div></li>
                            </ol>
                        </div>
                    @else
                        <div class="box">
                            <div class="box-head"><i class="bi bi-signpost-split"></i>What happens next</div>
                            <ol class="lifecycle">
                                <li><span class="step-icon"><i class="bi bi-broadcast"></i></span><div><strong>Live in the feed</strong><span>Appears at the top of the latest posts</span></div></li>
                                <li><span class="step-icon"><i class="bi bi-shield-check"></i></span><div><strong>Moderators keep watch</strong><span>Posts that break the rules are removed</span></div></li>
                                <li><span class="step-icon"><i class="bi bi-calendar2-x"></i></span><div><strong>Deleted after 30 days</strong><span>Post and media removed permanently</span></div></li>
                            </ol>
                        </div>
                    @endif

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
