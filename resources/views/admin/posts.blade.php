@extends('layouts.admin')

@section('title', 'Posts - Feedee Forums Admin')
@section('heading', 'Posts')
@section('subheading', 'Approve, reject, feature or delete posts')

@section('content')
    @php
        $pendingCount = $posts->filter->isPending()->count();
        $initialTab = $pendingCount > 0 ? 'pending' : 'all';
        $tabs = ['pending' => 'Pending', 'approved' => 'Approved', 'featured' => 'Featured', 'rejected' => 'Rejected', 'all' => 'All'];
    @endphp

                <div class="setting-card">
                    <span class="stat-icon ic-amber"><i class="bi bi-shield-check"></i></span>
                    <div class="setting-text">
                        <label for="requireApproval">Require approval for new posts</label>
                        <p class="setting-hint">{{ $requiresApproval
                            ? 'On: new posts wait in the Pending tab and only appear in the feed once you approve them.'
                            : 'Off: new posts go live straight away and you can still reject, feature or delete them at any time.' }}</p>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="requireApproval" data-approval-switch data-url="{{ route('admin.settings.post-approval') }}" @checked($requiresApproval)>
                    </div>
                </div>

                <section class="panel">
                    <div class="tabs-line" role="tablist" aria-label="Filter posts by status">
                        @foreach ($tabs as $tab => $label)
                            <button type="button" @class(['active' => $tab === $initialTab]) data-tab="{{ $tab }}" role="tab" aria-selected="{{ $tab === $initialTab ? 'true' : 'false' }}">{{ $label }} <span class="count">0</span></button>
                        @endforeach
                    </div>

                    <div class="toolbar">
                        <div class="admin-search">
                            <i class="bi bi-search"></i>
                            <input type="search" class="table-search" placeholder="Filter by title, author or topic" aria-label="Filter posts">
                        </div>
                        <select class="form-select column-filter" data-column="1" aria-label="Filter by topic">
                            <option value="">All topics</option>
                            @foreach ($topics as $topic)
                                <option value="{{ $topic }}">{{ $topic }}</option>
                            @endforeach
                        </select>
                        <span class="spacer"></span>
                        <label class="page-length-wrap">Show
                            <select class="form-select page-length" aria-label="Posts per page">
                                <option>10</option>
                                <option>25</option>
                                <option>50</option>
                            </select>
                        </label>
                    </div>

                    <div class="bulk-bar" aria-live="polite">
                        <strong><span class="bulk-count">0</span> selected</strong>
                        <button type="button" class="btn-soft sm" data-bulk="approve"><i class="bi bi-check-lg"></i>Approve</button>
                        <button type="button" class="btn-soft sm" data-bulk="feature"><i class="bi bi-star"></i>Feature</button>
                        <button type="button" class="btn-soft sm" data-bulk="reject"><i class="bi bi-x-lg"></i>Reject</button>
                        <button type="button" class="btn-soft sm danger" data-bulk="delete"><i class="bi bi-trash3"></i>Delete</button>
                    </div>

                        <table class="admin-table" data-table data-noun="posts" data-empty="No posts here. Nice work!" data-moderate-url="{{ route('admin.posts.moderate') }}">
                            <thead>
                                <tr>
                                    <th scope="col" class="check-col no-sort"><input class="form-check-input check-all" type="checkbox" aria-label="Select all posts"></th>
                                    <th scope="col">Post</th>
                                    <th scope="col">Author</th>
                                    <th scope="col" class="no-sort hide-xl">Media</th>
                                    <th scope="col">Submitted</th>
                                    <th scope="col" class="hide-xl">Expires in</th>
                                    <th scope="col">Status</th>
                                    <th scope="col" class="text-end no-sort">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($posts as $post)
                                    @php
                                        $images = $post->media->filter->isImage();
                                        $videos = $post->media->filter->isVideo();
                                        $cover = $images->first();
                                        $daysLeft = (int) ceil(now()->diffInDays($post->expiresAt(), false));
                                    @endphp
                                    <tr data-id="{{ $post->id }}" data-status="{{ $post->status->value }}" data-featured="{{ $post->is_featured ? '1' : '0' }}">
                                        <td class="check-col"><input class="form-check-input row-check" type="checkbox" aria-label="Select post: {{ $post->title }}"></td>
                                        <td>
                                            <a href="{{ route('posts.show', $post) }}" class="cell-post" target="_blank" rel="noopener">
                                                @if ($cover)
                                                    <img src="{{ $cover->url() }}" width="54" height="54" alt="" loading="lazy">
                                                @else
                                                    <span class="no-media"><i class="bi {{ $videos->isNotEmpty() ? 'bi-camera-video' : 'bi-text-paragraph' }}"></i></span>
                                                @endif
                                                <div><strong>{{ $post->title }}</strong><small>{{ $post->topic }} &middot; #{{ $post->public_id }}</small></div>
                                            </a>
                                        </td>
                                        <td><span class="cell-user"><span class="avatar avatar-icon" aria-hidden="true"><i class="bi bi-person-fill"></i></span>{{ $post->user->name }}</span></td>
                                        <td class="hide-xl">
                                            <span class="media-count">
                                                @if ($images->isEmpty() && $videos->isEmpty())
                                                    <span>Text only</span>
                                                @endif
                                                @if ($images->isNotEmpty())
                                                    <span><i class="bi bi-image"></i>{{ $images->count() }}</span>
                                                @endif
                                                @if ($videos->isNotEmpty())
                                                    <span><i class="bi bi-camera-video"></i>{{ $videos->count() }}</span>
                                                @endif
                                            </span>
                                        </td>
                                        <td class="nowrap muted" data-order="{{ $post->created_at->timestamp }}" title="{{ $post->created_at->format('M j, Y g:i A') }}">{{ $post->created_at->diffForHumans() }}</td>
                                        @if ($post->isRejected())
                                            <td class="nowrap muted hide-xl" data-order="9999">&mdash;</td>
                                        @elseif ($daysLeft <= 0)
                                            <td class="nowrap hide-xl" data-order="0"><span class="text-danger fw-semibold">Expired</span></td>
                                        @else
                                            <td @class(['nowrap', 'hide-xl', 'muted' => $daysLeft > 3]) data-order="{{ $daysLeft }}">
                                                @if ($daysLeft <= 3)
                                                    <span class="text-danger fw-semibold">{{ trans_choice('{1} 1 day|[2,*] :count days', $daysLeft) }}</span>
                                                @else
                                                    {{ trans_choice('{1} 1 day|[2,*] :count days', $daysLeft) }}
                                                @endif
                                            </td>
                                        @endif
                                        <td class="nowrap">
                                            <span class="status st-{{ $post->status->value }}" @if ($post->isRejected()) title="{{ $post->rejection_reason }}{{ $post->rejection_note ? ': '.$post->rejection_note : '' }}" @endif>{{ $post->status->label() }}</span>
                                            @if ($post->is_featured)
                                                <i class="bi bi-star-fill feature-flag" title="Featured" aria-label="Featured"></i>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="row-actions">
                                                <button type="button" class="act approve" title="Approve" aria-label="Approve" @disabled($post->isApproved())><i class="bi bi-check-lg"></i></button>
                                                <button type="button" class="act reject" title="Reject" aria-label="Reject" @disabled($post->isRejected())><i class="bi bi-x-lg"></i></button>
                                                <button type="button" @class(['act', 'feature', 'on' => $post->is_featured]) title="Feature" aria-label="Feature" aria-pressed="{{ $post->is_featured ? 'true' : 'false' }}"><i class="bi {{ $post->is_featured ? 'bi-star-fill' : 'bi-star' }}"></i></button>
                                                <button type="button" class="act delete" title="Delete" aria-label="Delete"><i class="bi bi-trash3"></i></button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                </section>
@endsection

@push('modals')
    @include('admin.partials.reject-modal')

    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title" id="deleteTitle">Delete permanently?</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="callout">
                        <i class="bi bi-exclamation-triangle"></i>
                        <div>
                            <strong class="modal-post-title"></strong>
                            <p>The post, all of its photos, videos and generated thumbnails will be permanently removed from storage. This can't be undone.</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-soft" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn-fill" id="deleteConfirm" style="background: var(--danger); border-color: var(--danger);"><i class="bi bi-trash3"></i>Delete forever</button>
                </div>
            </div>
        </div>
    </div>
@endpush
