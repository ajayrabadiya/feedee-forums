@extends('layouts.admin')

@section('title', 'Message from '.$contactMessage->name.' - Feedee Forums Admin')
@section('heading', 'Contact message')
@section('subheading', 'Received '.$contactMessage->created_at->format('M j, Y \a\t g:i A'))

@section('content')
                <a href="{{ route('admin.contacts.index') }}" class="back-link"><i class="bi bi-arrow-left"></i>All messages</a>

                <div class="row g-4">
                    <div class="col-xl-8">
                        <section class="panel">
                            <div class="panel-head">
                                <div>
                                    <h2>{{ $contactMessage->subject }}</h2>
                                    <p>From {{ $contactMessage->name }} &lt;{{ $contactMessage->email }}&gt;</p>
                                </div>
                            </div>
                            <div class="panel-body">
                                <div class="contact-body">{!! nl2br(e($contactMessage->message)) !!}</div>
                            </div>
                            <div class="panel-foot contact-actions">
                                <a href="mailto:{{ $contactMessage->email }}?subject={{ rawurlencode('Re: '.$contactMessage->subject) }}" class="btn-fill sm"><i class="bi bi-reply"></i>Reply by email</a>
                                <form method="post" action="{{ route('admin.contacts.update', $contactMessage) }}">
                                    @csrf
                                    @method('patch')
                                    <input type="hidden" name="read" value="0">
                                    <button type="submit" class="btn-soft sm"><i class="bi bi-envelope"></i>Mark as unread</button>
                                </form>
                                <form method="post" action="{{ route('admin.contacts.destroy', $contactMessage) }}" data-confirm="Delete this message? This can't be undone.">
                                    @csrf
                                    @method('delete')
                                    <button type="submit" class="btn-soft sm danger"><i class="bi bi-trash3"></i>Delete</button>
                                </form>
                            </div>
                        </section>
                    </div>

                    <div class="col-xl-4">
                        <section class="panel">
                            <div class="panel-head">
                                <div>
                                    <h2>Details</h2>
                                </div>
                            </div>
                            <div class="panel-body">
                                <dl class="contact-details">
                                    <dt>Name</dt>
                                    <dd>{{ $contactMessage->name }}</dd>
                                    <dt>Email</dt>
                                    <dd><a href="mailto:{{ $contactMessage->email }}">{{ $contactMessage->email }}</a></dd>
                                    <dt>Account</dt>
                                    <dd>
                                        @if ($contactMessage->user)
                                            {{ $contactMessage->user->name }}
                                            @if ($contactMessage->user->username)
                                                ({{ '@'.$contactMessage->user->username }})
                                            @endif
                                        @else
                                            Not logged in
                                        @endif
                                    </dd>
                                    <dt>Post ID</dt>
                                    <dd>
                                        @if ($referencedPost)
                                            <a href="{{ route('posts.show', $referencedPost) }}" target="_blank" rel="noopener">#{{ $referencedPost->public_id }} &middot; {{ $referencedPost->title }}</a>
                                        @elseif ($contactMessage->post_reference)
                                            #{{ $contactMessage->post_reference }} <span class="muted">(not found)</span>
                                        @else
                                            &mdash;
                                        @endif
                                    </dd>
                                    <dt>Received</dt>
                                    <dd>{{ $contactMessage->created_at->format('M j, Y g:i A') }}</dd>
                                    <dt>IP address</dt>
                                    <dd>{{ $contactMessage->ip_address ?? '—' }}</dd>
                                </dl>
                            </div>
                        </section>
                    </div>
                </div>
@endsection
