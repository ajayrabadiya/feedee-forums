@extends('layouts.app')

@section('title', 'Support Feedee Forums - YouPay & Cash App')
@section('description', 'Help keep Feedee Forums online and ad-free. Support the community through YouPay or Cash App.')

@push('meta')
    <link rel="canonical" href="{{ route('support') }}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="Support Feedee Forums">
    <meta property="og:description" content="Help keep Feedee Forums online and ad-free through YouPay or Cash App.">
    <meta property="og:url" content="{{ route('support') }}">
    <meta property="og:image" content="{{ asset('assets/images/logo.png') }}">
@endpush

@section('content')
    <section class="page-hero">
        <div class="container">
            <ol class="crumbs" aria-label="Breadcrumb">
                <li><a href="{{ route('home') }}">Feed</a></li>
                <li aria-current="page">Support</li>
            </ol>
            <span class="eyebrow"><i class="bi bi-heart-fill"></i>Community funded</span>
            <h1>Support Feedee Forums</h1>
            <p>No ads, no data selling. Feedee Forums runs on the kindness of its members. Every contribution, big or small, helps keep the lights on.</p>
        </div>
    </section>

    <main id="main" class="container page">
        {{--
        <div class="row g-4 justify-content-center">
            <div class="col-md-6 col-xl-5">
                <div class="pay-card pay-youpay">
                    <span class="pay-logo"><i class="bi bi-gift-fill"></i></span>
                    <h2>YouPay</h2>
                    <p>Send a gift or contribute towards something on our community wishlist, like new hosting or moderation tools.</p>

                    <div class="pay-handle">
                        <div><small>YouPay page</small>youpay.me/feedeeforums</div>
                        <button type="button" class="icon-btn" data-copy="https://youpay.me/feedeeforums" data-copy-msg="YouPay link copied" aria-label="Copy YouPay link"><i class="bi bi-copy"></i></button>
                    </div>

                    <ul class="check-list mb-4">
                        <li><i class="bi bi-check-circle-fill"></i>Pay with card, Apple Pay or Google Pay</li>
                        <li><i class="bi bi-check-circle-fill"></i>Choose a wishlist item or any amount</li>
                    </ul>

                    <a href="https://youpay.me/feedeeforums" class="btn-fill" target="_blank" rel="noopener nofollow">Open YouPay<i class="bi bi-box-arrow-up-right"></i></a>
                </div>
            </div>

            <div class="col-md-6 col-xl-5">
                <div class="pay-card pay-cashapp">
                    <span class="pay-logo"><i class="bi bi-currency-dollar"></i></span>
                    <h2>Cash App</h2>
                    <p>Send a quick contribution straight from your Cash App. Pick an amount below or enter your own in the app.</p>

                    <div class="pay-handle">
                        <div><small>$Cashtag</small>$FeedeeForums</div>
                        <button type="button" class="icon-btn" data-copy="$FeedeeForums" data-copy-msg="Cashtag copied" aria-label="Copy Cashtag"><i class="bi bi-copy"></i></button>
                    </div>

                    <div class="amount-chips" role="group" aria-label="Choose an amount">
                        <button type="button" data-amount="5" aria-pressed="false">$5</button>
                        <button type="button" data-amount="10" class="active" aria-pressed="true">$10</button>
                        <button type="button" data-amount="25" aria-pressed="false">$25</button>
                        <button type="button" data-amount="50" aria-pressed="false">$50</button>
                    </div>

                    <a href="https://cash.app/$FeedeeForums/10" data-pay-base="https://cash.app/$FeedeeForums/" class="btn-fill" target="_blank" rel="noopener nofollow">Open Cash App<i class="bi bi-box-arrow-up-right"></i></a>
                </div>
            </div>
        </div>

        <p class="secure-note"><i class="bi bi-shield-lock-fill"></i>Payments are handled securely by YouPay and Cash App. We never see or store your payment details.</p>
        --}}

        <section class="mt-5">
            <h2 class="section-title text-center">Where your support goes</h2>
            <p class="section-sub text-center">Every contribution goes straight back into the community.</p>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="use-card">
                        <span class="info-icon"><i class="bi bi-hdd-network"></i></span>
                        <h3>Hosting &amp; storage</h3>
                        <p>Fast servers, secure storage for photos and videos, and Cloudflare protection.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="use-card">
                        <span class="info-icon"><i class="bi bi-shield-check"></i></span>
                        <h3>Moderation</h3>
                        <p>Tools and time that help moderators keep the community safe and friendly.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="use-card">
                        <span class="info-icon"><i class="bi bi-rocket-takeoff"></i></span>
                        <h3>New features</h3>
                        <p>Improvements the community asks for, built without adding ads or tracking.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="mt-5">
            <h2 class="section-title">Support questions</h2>
            <p class="section-sub">Need something else? <a class="link" href="{{ route('contact') }}">Contact the team</a>.</p>

            <div class="accordion faq" id="supportFaq">
                <div class="accordion-item">
                    <h3 class="accordion-header">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#sfaq1" aria-expanded="true" aria-controls="sfaq1">Is supporting Feedee Forums required?</button>
                    </h3>
                    <div id="sfaq1" class="accordion-collapse collapse show" data-bs-parent="#supportFaq">
                        <div class="accordion-body">Not at all. Feedee Forums is free for everyone. Support is completely optional and doesn't unlock any special treatment in moderation.</div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h3 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sfaq2" aria-expanded="false" aria-controls="sfaq2">Can I get a refund?</button>
                    </h3>
                    <div id="sfaq2" class="accordion-collapse collapse" data-bs-parent="#supportFaq">
                        <div class="accordion-body">Payments are processed by YouPay and Cash App, so refunds follow their policies. If you sent something by mistake, <a class="link" href="{{ route('contact') }}">message us</a> and we'll do our best to help.</div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h3 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sfaq3" aria-expanded="false" aria-controls="sfaq3">Is my payment information safe?</button>
                    </h3>
                    <div id="sfaq3" class="accordion-collapse collapse" data-bs-parent="#supportFaq">
                        <div class="accordion-body">Yes. You pay on YouPay's or Cash App's own secure pages. Feedee Forums never receives or stores your card or bank details.</div>
                    </div>
                </div>
            </div>
        </section>
    </main>
@endsection
