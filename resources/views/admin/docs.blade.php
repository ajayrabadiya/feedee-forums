@extends('layouts.admin')

@section('title', 'Admin Guide - Feedee Forums Admin')
@section('heading', 'Admin guide')
@section('subheading', 'How to moderate Feedee Forums')

@section('content')
                <div class="row g-4">
                    <div class="col-xl-3 d-none d-xl-block">
                        <div class="panel sticky-lg">
                            <ul class="doc-nav">
                                <li><a href="#review">Reviewing posts</a></li>
                                <li><a href="#feature">Featuring posts</a></li>
                                <li><a href="#delete">Deleting posts</a></li>
                                <li><a href="#cleanup">30-day auto cleanup</a></li>
                                <li><a href="#tips">Good moderation</a></li>
                            </ul>
                        </div>
                    </div>

                    <div class="col-xl-9 doc">
                        <section class="panel-card prose" id="review">
                            <h2>Reviewing posts</h2>
                            <p>The <b>Require approval for new posts</b> switch at the top of the <a href="{{ route('admin.posts') }}">Posts</a> page controls how new posts are handled:</p>
                            <ul>
                                <li><b>Off</b> (recommended at launch): new posts go live in the feed straight away and their 30-day lifetime starts immediately.</li>
                                <li><b>On</b>: every new post starts as <span class="status st-pending">Pending</span> and stays hidden until approved. The 30-day lifetime starts only when you approve it.</li>
                            </ul>
                            <p>Whichever mode you use:</p>
                            <ul>
                                <li><b>Approve</b> <i class="bi bi-check-lg"></i> publishes a pending post and starts its 30-day timer from the approval time.</li>
                                <li><b>Reject</b> <i class="bi bi-x-lg"></i> asks for a reason, which is emailed to the author, and removes the post from the feed if it was already live. If the author later resubmits and you approve again, a <b>new</b> 30-day window starts from that re-approval.</li>
                                <li>Tick several posts to approve, reject, feature or delete them in one go.</li>
                                <li>Editing an already-approved post does <b>not</b> extend its lifetime.</li>
                            </ul>
                            <div class="callout info">
                                <i class="bi bi-info-circle"></i>
                                <div><strong>Check the media</strong><p>Click a post title to preview every photo and the video before approving.</p></div>
                            </div>
                        </section>

                        <section class="panel-card prose" id="feature">
                            <h2>Featuring posts</h2>
                            <p>Click the <i class="bi bi-star"></i> star to pin a post in the <b>Featured</b> section above the chronological feed. Featuring a pending post approves it automatically (and starts the 30-day timer). Click the star again to unfeature. Keep it to one or two posts at a time.</p>
                        </section>

                        <section class="panel-card prose" id="delete">
                            <h2>Deleting posts</h2>
                            <p>The <i class="bi bi-trash3"></i> delete action permanently removes the post, all original photos and videos, and every generated thumbnail from storage. It cannot be undone, so you'll always be asked to confirm.</p>
                        </section>

                        <section class="panel-card prose" id="cleanup">
                            <h2>30-day automatic cleanup</h2>
                            <p>Every <b>approved</b> post stays live for <b>30 days after approval</b> (not after the author first submitted it). When that window ends:</p>
                            <ul>
                                <li>The post disappears from the public feed, search, sitemap and direct URLs.</li>
                                <li>Likes and new comments are blocked.</li>
                                <li>It appears under <b>Posts &rarr; Expired</b> until the daily purge runs.</li>
                            </ul>
                            <p>Permanent deletion is performed by the Artisan command <code>php artisan posts:purge-expired</code>, scheduled daily at <b>03:00</b> (application timezone). It removes the database row, comments, likes, media records and the image/video files on disk. Pending and rejected posts are <b>not</b> deleted by this job.</p>
                            <p>On Hostinger, a single cron job must run the Laravel scheduler every minute:</p>
<pre><code>* * * * * cd /home/USER/domains/feedeeforums.com/laravel &amp;&amp; php artisan schedule:run &gt;&gt; /dev/null 2&gt;&amp;1</code></pre>
                            <p>You can also run <code>php artisan posts:purge-expired</code> manually over SSH if you need an immediate cleanup. There is no “Run now” button in the admin UI.</p>
                        </section>

                        <section class="panel-card prose" id="tips">
                            <h2>Good moderation</h2>
                            <ul>
                                <li>Aim to clear the pending queue at least twice a day.</li>
                                <li>Always choose an accurate rejection reason so authors can fix and resubmit.</li>
                                <li>When unsure, leave a post pending and ask another admin.</li>
                                <li>Never share reporter details or private member information.</li>
                            </ul>
                        </section>
                    </div>
                </div>
@endsection
