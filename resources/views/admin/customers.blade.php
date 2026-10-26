@extends('layouts.admin')

@section('title', 'Customers - Feedee Forums Admin')
@section('heading', 'Customers')
@section('subheading', 'Everyone who has signed up to share on Feedee Forums')

@section('content')
                <div class="row g-3 mb-4">
                    <div class="col-sm-6 col-xl-3">
                        <div class="stat-card">
                            <div class="stat-top"><span class="stat-icon ic-blue"><i class="bi bi-people"></i></span></div>
                            <strong>{{ number_format($stats['total']) }}</strong>
                            <span class="label">Registered customers</span>
                        </div>
                    </div>
                    <div class="col-sm-6 col-xl-3">
                        <div class="stat-card">
                            <div class="stat-top"><span class="stat-icon ic-green"><i class="bi bi-person-plus"></i></span></div>
                            <strong>{{ number_format($stats['joinedThisWeek']) }}</strong>
                            <span class="label">Joined in the last 7 days</span>
                        </div>
                    </div>
                    <div class="col-sm-6 col-xl-3">
                        <div class="stat-card">
                            <div class="stat-top"><span class="stat-icon ic-purple"><i class="bi bi-activity"></i></span></div>
                            <strong>{{ number_format($stats['active']) }}</strong>
                            <span class="label">Posted in the last {{ \App\Http\Controllers\Admin\CustomerController::ACTIVE_DAYS }} days</span>
                        </div>
                    </div>
                    <div class="col-sm-6 col-xl-3">
                        <a href="{{ route('admin.posts') }}" class="stat-card d-block">
                            <div class="stat-top"><span class="stat-icon ic-amber"><i class="bi bi-hourglass-split"></i></span></div>
                            <strong>{{ number_format($stats['pendingPosts']) }}</strong>
                            <span class="label">Customer posts awaiting review</span>
                        </a>
                    </div>
                </div>

                <section class="panel">
                    <div class="tabs-line" role="tablist" aria-label="Filter customers by activity">
                        <button type="button" class="active" data-tab="all" role="tab" aria-selected="true">All <span class="count">0</span></button>
                        <button type="button" data-tab="active" role="tab" aria-selected="false">Posted recently <span class="count">0</span></button>
                        <button type="button" data-tab="inactive" role="tab" aria-selected="false">Not posting <span class="count">0</span></button>
                    </div>

                    <div class="toolbar">
                        <div class="admin-search">
                            <i class="bi bi-search"></i>
                            <input type="search" class="table-search" placeholder="Filter by name, username or email" aria-label="Filter customers">
                        </div>
                        <span class="spacer"></span>
                        <label class="page-length-wrap">Show
                            <select class="form-select page-length" aria-label="Customers per page">
                                <option>10</option>
                                <option>25</option>
                                <option>50</option>
                            </select>
                        </label>
                    </div>

                    <table class="admin-table" data-table data-noun="customers" data-empty="No customers match.">
                        <thead>
                            <tr>
                                <th scope="col">Customer</th>
                                <th scope="col" class="hide-xl">Email</th>
                                <th scope="col">Joined</th>
                                <th scope="col">Posts</th>
                                <th scope="col" class="hide-xl">Last post</th>
                                <th scope="col" class="text-end no-sort">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($customers as $customer)
                                @php
                                    $lastPostAt = $customer->posts_max_created_at ? \Illuminate\Support\Carbon::parse($customer->posts_max_created_at) : null;
                                    $isActive = $lastPostAt !== null && $lastPostAt->gte($activeSince);
                                @endphp
                                <tr data-status="{{ $isActive ? 'active' : 'inactive' }}" data-id="{{ $customer->id }}">
                                    <td>
                                        <span class="cell-user cell-customer">
                                            <span class="avatar avatar-icon" aria-hidden="true"><i class="bi bi-person-fill"></i></span>
                                            <span>
                                                <strong>{{ $customer->name }}</strong>
                                                <small>{{ $customer->username ? '@'.$customer->username : 'No username' }}</small>
                                            </span>
                                        </span>
                                    </td>
                                    <td class="hide-xl"><a href="mailto:{{ $customer->email }}" class="cell-email">{{ $customer->email }}</a></td>
                                    <td class="nowrap muted" data-order="{{ $customer->created_at->timestamp }}" title="{{ $customer->created_at->format('M j, Y g:i A') }}">{{ $customer->created_at->format('M j, Y') }}</td>
                                    <td class="nowrap" data-order="{{ $customer->posts_count }}">
                                        <strong>{{ $customer->posts_count }}</strong>
                                        @if ($customer->posts_count > 0)
                                            <span class="post-breakdown">
                                                <span class="text-success" title="Approved">{{ $customer->approved_posts_count }} approved</span>
                                                @if ($customer->pending_posts_count)
                                                    &middot; <span class="text-warning-emphasis" title="Waiting for review">{{ $customer->pending_posts_count }} pending</span>
                                                @endif
                                                @if ($customer->rejected_posts_count)
                                                    &middot; <span class="muted" title="Rejected">{{ $customer->rejected_posts_count }} rejected</span>
                                                @endif
                                            </span>
                                        @endif
                                    </td>
                                    <td class="nowrap muted hide-xl" data-order="{{ $lastPostAt?->timestamp ?? 0 }}">
                                        {{ $lastPostAt ? $lastPostAt->diffForHumans() : 'Never' }}
                                    </td>
                                    <td>
                                        <div class="row-actions">
                                            <a href="{{ route('admin.posts', ['q' => $customer->name]) }}" @class(['act', 'view', 'disabled-link' => $customer->posts_count === 0]) title="View posts" aria-label="View posts by {{ $customer->name }}" @if ($customer->posts_count === 0) aria-disabled="true" tabindex="-1" @endif><i class="bi bi-file-earmark-richtext"></i></a>
                                            <a href="mailto:{{ $customer->email }}" class="act view" title="Email customer" aria-label="Email {{ $customer->name }}"><i class="bi bi-envelope"></i></a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </section>
@endsection
