@extends('layouts.app')

@section('title', 'Contact Us - Feedee Forums')
@section('description', 'Get in touch with the Feedee Forums team for account help, moderation questions, feedback or partnerships.')

@push('meta')
    <link rel="canonical" href="{{ route('contact') }}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="Contact Us - Feedee Forums">
    <meta property="og:description" content="Questions, feedback or need a hand? The Feedee Forums team is here to help.">
    <meta property="og:url" content="{{ route('contact') }}">
    <meta property="og:image" content="{{ asset('assets/images/logo.png') }}">
@endpush

@section('content')
    <section class="page-hero">
        <div class="container">
            <ol class="crumbs" aria-label="Breadcrumb">
                <li><a href="{{ route('home') }}">Feed</a></li>
                <li aria-current="page">Contact</li>
            </ol>
            <span class="eyebrow"><i class="bi bi-envelope-open"></i>We usually reply within 24 hours</span>
            <h1>Get in touch</h1>
            <p>Questions about your account, a post or moderation? Send us a message and a real person from the team will get back to you.</p>
        </div>
    </section>

    <main id="main" class="container page">
        <div class="row g-4">
            <div class="col-lg-7">
                <form class="panel-card" action="{{ route('contact.store') }}" method="post">
                    @csrf
                    <h2 class="section-title">Send us a message</h2>
                    <p class="section-sub">All fields are required unless marked optional.</p>

                    @if ($errors->any())
                        <div class="callout error mb-4" role="alert">
                            <i class="bi bi-exclamation-circle"></i>
                            <div>
                                <strong>Please fix the highlighted fields</strong>
                                <p>Your message hasn't been sent yet.</p>
                            </div>
                        </div>
                    @endif

                    <div class="contact-trap" aria-hidden="true">
                        <label for="website">Leave this field empty</label>
                        <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
                    </div>

                    @php
                        $selectedSubject = old('subject', request()->query('subject'));
                    @endphp
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="name">Your name</label>
                            <input type="text" @class(['form-control', 'has-error' => $errors->has('name')]) id="name" name="name" value="{{ old('name', auth()->user()?->name) }}" autocomplete="name" required maxlength="80">
                            @error('name')
                                <p class="form-error" role="alert">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="email">Email address</label>
                            <input type="email" @class(['form-control', 'has-error' => $errors->has('email')]) id="email" name="email" value="{{ old('email', auth()->user()?->email) }}" autocomplete="email" required maxlength="255">
                            @error('email')
                                <p class="form-error" role="alert">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="subject">Subject</label>
                            <select @class(['form-select', 'has-error' => $errors->has('subject')]) id="subject" name="subject" required>
                                <option value="" @selected(! $selectedSubject) disabled>Choose a subject</option>
                                @foreach ($subjects as $subject)
                                    <option value="{{ $subject }}" @selected($selectedSubject === $subject)>{{ $subject }}</option>
                                @endforeach
                            </select>
                            @error('subject')
                                <p class="form-error" role="alert">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="postId">Post ID <span class="opt">(optional)</span></label>
                            <input type="text" @class(['form-control', 'has-error' => $errors->has('post_id')]) id="postId" name="post_id" value="{{ old('post_id', request()->query('post')) }}" maxlength="13" placeholder="e.g. k7Xq92Lm">
                            @error('post_id')
                                <p class="form-error" role="alert">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="col-12">
                            <div class="label-row">
                                <label class="form-label" for="message">Message</label>
                                <span class="char-count" id="msgCount">0 / 2000</span>
                            </div>
                            <textarea @class(['form-control', 'has-error' => $errors->has('message')]) id="message" name="message" required minlength="10" maxlength="2000" placeholder="How can we help?" data-counter="#msgCount">{{ old('message') }}</textarea>
                            @error('message')
                                <p class="form-error" role="alert">{{ $message }}</p>
                            @enderror
                            @error('website')
                                <p class="form-error" role="alert">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="col-12">
                            <div class="turnstile" aria-label="Security check">
                                <i class="bi bi-cloud-check-fill"></i>
                                <div><strong>Verifying you're human</strong>Protected by Cloudflare Turnstile</div>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-fill"><i class="bi bi-send"></i>Send message</button>
                    </div>
                </form>
            </div>

            <aside class="col-lg-5">
                <div class="info-card">
                    <span class="info-icon"><i class="bi bi-envelope"></i></span>
                    <div>
                        <strong>Email</strong>
                        <p><a href="mailto:hello@feedeeforums.com">hello@feedeeforums.com</a></p>
                    </div>
                </div>
                <div class="info-card">
                    <span class="info-icon"><i class="bi bi-clock"></i></span>
                    <div>
                        <strong>Response time</strong>
                        <p>Monday to Saturday, usually within 24 hours.</p>
                    </div>
                </div>
                <div class="info-card">
                    <span class="info-icon"><i class="bi bi-flag"></i></span>
                    <div>
                        <strong>Reporting a post?</strong>
                        <p>The fastest way is the <b>Report</b> button on the post itself. It goes straight to moderators.</p>
                    </div>
                </div>
                <div class="info-card">
                    <span class="info-icon"><i class="bi bi-heart"></i></span>
                    <div>
                        <strong>Want to support us?</strong>
                        <p>Visit our <a href="{{ route('support') }}">support page</a></p>
                    </div>
                </div>
            </aside>
        </div>

        <section class="mt-5" id="faq">
            <h2 class="section-title">Frequently asked questions</h2>
            <p class="section-sub">Quick answers to the things we're asked most.</p>

            <div class="accordion faq" id="faqList">
                <div class="accordion-item">
                    <h3 class="accordion-header">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1" aria-expanded="true" aria-controls="faq1">Why hasn't my post appeared yet?</button>
                    </h3>
                    <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqList">
                        <div class="accordion-body">Most posts appear in the feed straight away. Sometimes a post is held for a quick moderator check before it goes live. You'll get an email as soon as it's published or if there's a problem.</div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h3 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2" aria-expanded="false" aria-controls="faq2">Can I keep my post longer than 30 days?</button>
                    </h3>
                    <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqList">
                        <div class="accordion-body">No. All posts, photos and videos are permanently deleted 30 days after publishing. That's part of what makes Feedee Forums feel light and in the moment. Save anything you'd like to keep before it expires.</div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h3 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3" aria-expanded="false" aria-controls="faq3">What files can I upload?</button>
                    </h3>
                    <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqList">
                        <div class="accordion-body">Up to {{ config('media.images.max_files') }} photos per post ({{ config('media.images.label') }}, {{ \App\Http\Requests\StorePostRequest::formatKb(config('media.images.max_kb')) }} each) and up to {{ config('media.videos.max_files') }} short videos of {{ config('media.videos.max_seconds') }} seconds or less ({{ config('media.videos.label') }}, {{ \App\Http\Requests\StorePostRequest::formatKb(config('media.videos.max_kb')) }} each).</div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h3 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4" aria-expanded="false" aria-controls="faq4">How do I delete my account?</button>
                    </h3>
                    <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqList">
                        <div class="accordion-body">Send us a message from the email address on your account with the subject "Account help" and we'll remove your account and any live posts within 48 hours.</div>
                    </div>
                </div>
            </div>
        </section>
    </main>
@endsection
