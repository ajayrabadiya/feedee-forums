@extends('layouts.admin')

@section('title', 'Dashboard - Feedee Forums Admin')
@section('heading', 'Dashboard')
@section('subheading', now()->format('l, F j, Y'))

@section('content')
    @php
        $approvedDelta = $stats['approvedToday'] - $stats['approvedYesterday'];
    @endphp
                <!-- Stats -->
                <div class="row g-3 mb-4">
                    <div class="col-sm-6 col-xl-3">
                        <a href="{{ route('admin.posts') }}" class="stat-card d-block">
                            <div class="stat-top">
                                <span class="stat-icon ic-amber"><i class="bi bi-hourglass-split"></i></span>
                                <span @class(['trend', 'up' => $stats['pendingToday'] > 0, 'flat' => $stats['pendingToday'] === 0])>+{{ number_format($stats['pendingToday']) }} today</span>
                            </div>
                            <strong>{{ number_format($stats['pending']) }}</strong>
                            <span class="label">Posts awaiting review</span>
                        </a>
                    </div>
                    <div class="col-sm-6 col-xl-3">
                        <a href="{{ route('admin.contacts.index') }}" class="stat-card d-block">
                            <div class="stat-top">
                                <span class="stat-icon ic-red"><i class="bi bi-envelope"></i></span>
                                <span @class(['trend', 'down' => $stats['messagesToday'] > 0, 'flat' => $stats['messagesToday'] === 0])>+{{ number_format($stats['messagesToday']) }} today</span>
                            </div>
                            <strong>{{ number_format($stats['unreadMessages']) }}</strong>
                            <span class="label">Unread contact messages</span>
                        </a>
                    </div>
                    <div class="col-sm-6 col-xl-3">
                        <div class="stat-card">
                            <div class="stat-top">
                                <span class="stat-icon ic-green"><i class="bi bi-check2-circle"></i></span>
                                <span @class(['trend', 'up' => $approvedDelta > 0, 'down' => $approvedDelta < 0, 'flat' => $approvedDelta === 0]) title="{{ number_format($stats['approvedYesterday']) }} yesterday">{{ $approvedDelta >= 0 ? '+' : '−' }}{{ number_format(abs($approvedDelta)) }} vs yesterday</span>
                            </div>
                            <strong>{{ number_format($stats['approvedToday']) }}</strong>
                            <span class="label">Approved today</span>
                        </div>
                    </div>
                    <div class="col-sm-6 col-xl-3">
                        <a href="{{ route('admin.customers') }}" class="stat-card d-block">
                            <div class="stat-top">
                                <span class="stat-icon ic-blue"><i class="bi bi-people"></i></span>
                                <span @class(['trend', 'up' => $stats['membersThisWeek'] > 0, 'flat' => $stats['membersThisWeek'] === 0])>+{{ number_format($stats['membersThisWeek']) }} this week</span>
                            </div>
                            <strong>{{ number_format($stats['members']) }}</strong>
                            <span class="label">Registered members</span>
                        </a>
                    </div>
                </div>

                <div class="row g-4 mb-4">
                    <!-- Chart -->
                    <div class="col-12">
                        <section class="panel h-100">
                            <div class="panel-head">
                                <div>
                                    <h2>Posts this week</h2>
                                    <p>Submitted vs. approved, last 7 days &middot; {{ number_format($chart->sum('submitted')) }} submitted, {{ number_format($chart->sum('approved')) }} approved</p>
                                </div>
                                <div class="legend"><span><i></i>Submitted</span><span><i class="alt"></i>Approved</span></div>
                            </div>
                            <div class="panel-body fill">
                                <div class="bar-chart" role="img" aria-label="Bar chart of posts submitted and approved over the last 7 days: {{ $chart->map(fn ($day) => $day['label'].' '.$day['submitted'].' submitted, '.$day['approved'].' approved')->implode('; ') }}">
                                    @foreach ($chart as $day)
                                        <div class="bar" title="{{ $day['date'] }}: {{ $day['submitted'] }} submitted, {{ $day['approved'] }} approved">
                                            <div class="bar-track">
                                                <span class="bar-fill" style="height: {{ $day['submittedHeight'] }}%;" data-value="{{ $day['submitted'] }}"></span>
                                                <span class="bar-fill alt" style="height: {{ $day['approvedHeight'] }}%;"></span>
                                            </div>
                                            <small>{{ $day['label'] }}</small>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </section>
                    </div>
                </div>

                <div class="row g-4">
                    <!-- Pending -->
                    <div class="col-xl-8">
                        <section class="panel h-100">
                            <div class="panel-head">
                                <div>
                                    <h2>Waiting for review</h2>
                                    <p>Oldest first, so nobody waits too long &middot; {{ trans_choice('{0} nothing pending|{1} :count post pending|[2,*] :count posts pending', $stats['pending']) }}</p>
                                </div>
                                <a href="{{ route('admin.posts') }}" class="link-more">Open queue <i class="bi bi-arrow-right"></i></a>
                            </div>
                            <div class="table-wrap">
                                <table class="admin-table" data-table data-compact data-noun="posts" data-empty="All caught up!" data-moderate-url="{{ route('admin.posts.moderate') }}">
                                    <thead>
                                        <tr>
                                            <th scope="col">Post</th>
                                            <th scope="col" class="no-sort">Media</th>
                                            <th scope="col">Status</th>
                                            <th scope="col" class="text-end no-sort">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($pendingPosts as $post)
                                            @php
                                                $images = $post->media->filter->isImage();
                                                $videos = $post->media->filter->isVideo();
                                                $cover = $images->first();
                                            @endphp
                                            <tr data-id="{{ $post->id }}" data-status="pending" data-featured="0">
                                                <td>
                                                    <a href="{{ route('posts.show', $post) }}" class="cell-post" target="_blank" rel="noopener">
                                                        @if ($cover)
                                                            <img src="{{ $cover->url() }}" width="54" height="54" alt="" loading="lazy">
                                                        @else
                                                            <span class="no-media"><i class="bi {{ $videos->isNotEmpty() ? 'bi-camera-video' : 'bi-text-paragraph' }}"></i></span>
                                                        @endif
                                                        <div><strong>{{ $post->title }}</strong><small>{{ $post->user->name }} &middot; {{ $post->created_at->diffForHumans() }}</small></div>
                                                    </a>
                                                </td>
                                                <td>
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
                                                <td><span class="status st-pending">Pending</span></td>
                                                <td>
                                                    <div class="row-actions">
                                                        <button type="button" class="act approve" title="Approve" aria-label="Approve {{ $post->title }}"><i class="bi bi-check-lg"></i></button>
                                                        <button type="button" class="act reject" title="Reject" aria-label="Reject {{ $post->title }}"><i class="bi bi-x-lg"></i></button>
                                                        <a href="{{ route('posts.show', $post) }}" class="act view" title="Preview" aria-label="Preview {{ $post->title }}" target="_blank" rel="noopener"><i class="bi bi-eye"></i></a>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </section>
                    </div>

                    <!-- Contact messages -->
                    <div class="col-xl-4">
                        <section class="panel h-100">
                            <div class="panel-head">
                                <div>
                                    <h2>Latest contact messages</h2>
                                    <p>{{ number_format($stats['unreadMessages']) }} unread</p>
                                </div>
                                <a href="{{ route('admin.contacts.index') }}" class="link-more">View all <i class="bi bi-arrow-right"></i></a>
                            </div>
                            @if ($latestMessages->isEmpty())
                                <div class="panel-body text-center muted">No messages yet.</div>
                            @else
                                <ul class="report-list">
                                    @foreach ($latestMessages as $contact)
                                        <li @class(['is-unread' => ! $contact->isRead()])>
                                            <span @class(['stat-icon', 'ic-red' => ! $contact->isRead(), 'ic-blue' => $contact->isRead()])><i class="bi {{ $contact->isRead() ? 'bi-envelope-open' : 'bi-envelope' }}"></i></span>
                                            <div>
                                                <a href="{{ route('admin.contacts.show', $contact) }}"><strong>{{ $contact->subject }}</strong></a>
                                                <p>{{ $contact->name }} &middot; {{ $contact->created_at->diffForHumans() }} @if (! $contact->isRead()) &middot; <span class="text-danger fw-semibold">New</span>@endif</p>
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </section>
                    </div>
                </div>
@endsection

@push('modals')
    @include('admin.partials.reject-modal')
@endpush
