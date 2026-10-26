@extends('layouts.admin')

@section('title', 'Contact messages - Feedee Forums Admin')
@section('heading', 'Contact messages')
@section('subheading', 'Messages sent through the contact form')

@section('content')
    @php
        $initialTab = $unreadCount > 0 ? 'unread' : 'all';
    @endphp

                <section class="panel">
                    <div class="tabs-line" role="tablist" aria-label="Filter messages">
                        @foreach (['unread' => 'Unread', 'read' => 'Read', 'all' => 'All'] as $tab => $label)
                            <button type="button" @class(['active' => $tab === $initialTab]) data-tab="{{ $tab }}" role="tab" aria-selected="{{ $tab === $initialTab ? 'true' : 'false' }}">{{ $label }} <span class="count">0</span></button>
                        @endforeach
                    </div>

                    <div class="toolbar">
                        <div class="admin-search">
                            <i class="bi bi-search"></i>
                            <input type="search" class="table-search" placeholder="Filter by name, email or message" aria-label="Filter messages">
                        </div>
                        <select class="form-select column-filter" data-column="1" aria-label="Filter by subject">
                            <option value="">All subjects</option>
                            @foreach ($subjects as $subject)
                                <option value="{{ $subject }}">{{ $subject }}</option>
                            @endforeach
                        </select>
                        <span class="spacer"></span>
                        <label class="page-length-wrap">Show
                            <select class="form-select page-length" aria-label="Messages per page">
                                <option>10</option>
                                <option>25</option>
                                <option>50</option>
                            </select>
                        </label>
                    </div>

                    <table class="admin-table" data-table data-noun="messages" data-empty="No messages here.">
                        <thead>
                            <tr>
                                <th scope="col">From</th>
                                <th scope="col">Message</th>
                                <th scope="col">Received</th>
                                <th scope="col">Status</th>
                                <th scope="col" class="text-end no-sort">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($messages as $contact)
                                <tr data-status="{{ $contact->isRead() ? 'read' : 'unread' }}" @class(['is-unread' => ! $contact->isRead()])>
                                    <td>
                                        <span class="cell-user cell-customer">
                                            <span class="avatar avatar-icon" aria-hidden="true"><i class="bi {{ $contact->user ? 'bi-person-check-fill' : 'bi-person-fill' }}"></i></span>
                                            <span>
                                                <strong>{{ $contact->name }}</strong>
                                                <small>{{ $contact->email }}</small>
                                            </span>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.contacts.show', $contact) }}" class="cell-message">
                                            <strong>{{ $contact->subject }}@if ($contact->post_reference) <span class="muted">&middot; #{{ $contact->post_reference }}</span>@endif</strong>
                                            <small>{{ \Illuminate\Support\Str::limit($contact->message, 110) }}</small>
                                        </a>
                                    </td>
                                    <td class="nowrap muted" data-order="{{ $contact->created_at->timestamp }}" title="{{ $contact->created_at->format('M j, Y g:i A') }}">{{ $contact->created_at->diffForHumans() }}</td>
                                    <td class="nowrap">
                                        <span @class(['status', 'st-pending' => ! $contact->isRead(), 'st-resolved' => $contact->isRead()])>{{ $contact->isRead() ? 'Read' : 'New' }}</span>
                                    </td>
                                    <td>
                                        <div class="row-actions">
                                            <a href="{{ route('admin.contacts.show', $contact) }}" class="act view" title="Open" aria-label="Open message from {{ $contact->name }}"><i class="bi bi-envelope-open"></i></a>
                                            <a href="mailto:{{ $contact->email }}?subject={{ rawurlencode('Re: '.$contact->subject) }}" class="act approve" title="Reply by email" aria-label="Reply to {{ $contact->name }}"><i class="bi bi-reply"></i></a>
                                            <form method="post" action="{{ route('admin.contacts.destroy', $contact) }}" data-confirm="Delete this message from {{ $contact->name }}? This can't be undone.">
                                                @csrf
                                                @method('delete')
                                                <button type="submit" class="act delete" title="Delete" aria-label="Delete message from {{ $contact->name }}"><i class="bi bi-trash3"></i></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </section>
@endsection
