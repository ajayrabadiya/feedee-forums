@extends('layouts.app')

@section('title', 'Community Rules - Feedee Forums')
@section('description', 'The Feedee Forums community rules, media guidelines, moderation process and 30-day post deletion policy.')

@push('meta')
    <link rel="canonical" href="{{ route('rules') }}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="Community Rules - Feedee Forums">
    <meta property="og:description" content="How we keep Feedee Forums friendly, safe and welcoming for everyone.">
    <meta property="og:url" content="{{ route('rules') }}">
    <meta property="og:image" content="{{ asset('assets/images/logo.png') }}">
@endpush

@section('body_attributes')data-bs-spy="scroll" data-bs-target="#rulesToc" data-bs-offset="100"@endsection

@section('content')
    <section class="page-hero">
        <div class="container">
            <ol class="crumbs" aria-label="Breadcrumb">
                <li><a href="{{ route('home') }}">Feed</a></li>
                <li aria-current="page">Rules</li>
            </ol>
            <span class="eyebrow"><i class="bi bi-shield-check"></i>Updated October 2026</span>
            <h1>Community Rules</h1>
            <p>Feedee Forums works because members look out for each other. These rules keep the community friendly, safe and welcoming for everyone.</p>
        </div>
    </section>

    <main id="main" class="container page">
        <div class="row g-4">
            <aside class="col-lg-4 order-lg-2">
                <div class="sticky-lg">
                    <div class="box">
                        <div class="box-head"><i class="bi bi-list-ul"></i>On this page</div>
                        <nav id="rulesToc" aria-label="Rules sections">
                            <ul class="toc">
                                <li><a href="#rules"><i class="bi bi-1-circle"></i>The rules</a></li>
                                <li><a href="#media"><i class="bi bi-images"></i>Photos &amp; videos</a></li>
                                <li><a href="#deletion"><i class="bi bi-hourglass-split"></i>30-day deletion</a></li>
                                <li><a href="#moderation"><i class="bi bi-shield-check"></i>Moderation &amp; reports</a></li>
                                <li><a href="#privacy"><i class="bi bi-lock"></i>Privacy</a></li>
                            </ul>
                        </nav>
                    </div>
                    <div class="callout">
                        <i class="bi bi-flag"></i>
                        <div>
                            <strong>See something wrong?</strong>
                            <p>Use the <b>Report</b> option on any post, or <a class="link" href="{{ route('contact') }}">contact the team</a>.</p>
                        </div>
                    </div>
                </div>
            </aside>

            <div class="col-lg-8 order-lg-1">
                <section class="panel-card" id="rules">
                    <h2 class="section-title">The rules</h2>
                    <p class="section-sub">Breaking these may lead to removed posts, a temporary suspension or a permanent ban.</p>

                    <div class="rule-item">
                        <span class="rule-num">1</span>
                        <div>
                            <h3>Be kind and respectful</h3>
                            <p>Treat others the way you'd like to be treated. No harassment, bullying, hate speech or personal attacks, whether in posts, images or usernames.</p>
                        </div>
                    </div>
                    <div class="rule-item">
                        <span class="rule-num">2</span>
                        <div>
                            <h3>Stay on topic</h3>
                            <p>Choose the topic that best fits your post. Off-topic posts may be moved or removed by moderators.</p>
                        </div>
                    </div>
                    <div class="rule-item">
                        <span class="rule-num">3</span>
                        <div>
                            <h3>No spam or self-promotion</h3>
                            <p>No ads, referral links, repeated posts or schemes. Sharing your own creative work is welcome; selling it in every post is not.</p>
                        </div>
                    </div>
                    <div class="rule-item">
                        <span class="rule-num">4</span>
                        <div>
                            <h3>Respect everyone's privacy</h3>
                            <p>Never share someone's personal details, private messages or photos of them without their permission.</p>
                        </div>
                    </div>
                    <div class="rule-item">
                        <span class="rule-num">5</span>
                        <div>
                            <h3>Only post what you have the right to share</h3>
                            <p>Upload photos and videos you took yourself or have permission to use. Credit the original creator when sharing their work.</p>
                        </div>
                    </div>
                    <div class="rule-item">
                        <span class="rule-num">6</span>
                        <div>
                            <h3>Keep it legal and safe</h3>
                            <p>No illegal content, threats, dangerous activities or anything that puts someone at risk. Members must be 18 or older.</p>
                        </div>
                    </div>
                </section>

                <section class="panel-card prose" id="media">
                    <h2>Photos &amp; videos</h2>
                    <p>Media makes posts come alive. To keep things fast and safe for everyone, uploads must follow these limits:</p>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <ul class="check-list">
                                <li><i class="bi bi-check-circle-fill"></i>Up to 10 photos per post</li>
                                <li><i class="bi bi-check-circle-fill"></i>JPG, PNG or WebP, 8 MB max each</li>
                                <li><i class="bi bi-check-circle-fill"></i>One video up to 60 seconds</li>
                                <li><i class="bi bi-check-circle-fill"></i>MP4, WebM or MOV, 50 MB max</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <ul class="check-list no">
                                <li><i class="bi bi-x-circle-fill"></i>No nudity or graphic violence</li>
                                <li><i class="bi bi-x-circle-fill"></i>No watermarked or stolen media</li>
                                <li><i class="bi bi-x-circle-fill"></i>No screenshots of private chats</li>
                                <li><i class="bi bi-x-circle-fill"></i>No misleading or edited evidence</li>
                            </ul>
                        </div>
                    </div>
                    <p>Please add a short description (alt text) to every photo so members using screen readers can enjoy your post too. Location data is automatically removed from uploaded photos.</p>
                </section>

                <section class="panel-card prose" id="deletion">
                    <h2>30-day automatic deletion</h2>
                    <p>Feedee Forums is designed for the moment, not forever. Every post is automatically and permanently deleted 30 days after it's published.</p>
                    <ul>
                        <li>The post text, every photo, every video and all generated thumbnails are removed from our servers.</li>
                        <li>Deleted posts cannot be restored, by you or by our team.</li>
                        <li>Each post shows how many days it has left, so you can save anything you want to keep.</li>
                    </ul>
                    <div class="callout info mt-3">
                        <i class="bi bi-info-circle"></i>
                        <div>
                            <strong>Want to keep something?</strong>
                            <p>Download your own photos and videos before the post expires. We don't keep backups of deleted posts.</p>
                        </div>
                    </div>
                </section>

                <section class="panel-card prose" id="moderation">
                    <h2>Moderation &amp; reports</h2>
                    <p>Moderators keep an eye on everything shared on Feedee Forums. Some posts may be held for a quick check before they appear in the feed.</p>
                    <h3>When a post is reported</h3>
                    <ul>
                        <li>Reports are anonymous: the author never sees who reported them.</li>
                        <li>A moderator reviews the post and either dismisses the report or removes the post.</li>
                        <li>Repeated or serious violations can lead to account suspension.</li>
                    </ul>
                    <h3>If your post is rejected</h3>
                    <p>You'll receive a short reason by email. You're welcome to edit and resubmit, or <a class="link" href="{{ route('contact') }}">contact us</a> if you think we got it wrong.</p>
                </section>

                <section class="panel-card prose" id="privacy">
                    <h2>Privacy</h2>
                    <p>We only collect what we need to run the community: your username, email address and the posts you share. Your email is never shown publicly and we never sell your data.</p>
                    {{-- <p>Support payments made through YouPay or Cash App are processed entirely by those services. We never see or store your card details.</p> --}}
                </section>
            </div>
        </div>
    </main>
@endsection
