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
                                <li><a href="#reports">Handling reports</a></li>
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
                                <li><b>Off</b> (recommended at launch): new posts go live in the feed straight away. Moderate them afterwards from <b>Posts &rarr; Approved</b> or when they're reported.</li>
                                <li><b>On</b>: every new post starts as <span class="status st-pending">Pending</span> and stays hidden until approved. Open <b>Posts &rarr; Pending</b> to see the queue, oldest first.</li>
                            </ul>
                            <p>Whichever mode you use:</p>
                            <ul>
                                <li><b>Approve</b> <i class="bi bi-check-lg"></i> publishes a pending post at the top of the feed and starts its 30-day timer.</li>
                                <li><b>Reject</b> <i class="bi bi-x-lg"></i> asks for a reason, which is emailed to the author, and removes the post from the feed if it was already live.</li>
                                <li>Tick several posts to approve, reject, feature or delete them in one go.</li>
                            </ul>
                            <div class="callout info">
                                <i class="bi bi-info-circle"></i>
                                <div><strong>Check the media</strong><p>Click a post title to preview every photo and the video before approving.</p></div>
                            </div>
                        </section>

                        <section class="panel-card prose" id="feature">
                            <h2>Featuring posts</h2>
                            <p>Click the <i class="bi bi-star"></i> star to pin a post in the <b>Featured</b> section above the chronological feed. Featuring a pending post approves it automatically. Click the star again to unfeature. Keep it to one or two posts at a time.</p>
                        </section>

                        <section class="panel-card prose" id="delete">
                            <h2>Deleting posts</h2>
                            <p>The <i class="bi bi-trash3"></i> delete action permanently removes the post, all original photos and videos, and every generated thumbnail from storage. It cannot be undone, so you'll always be asked to confirm.</p>
                        </section>

                        <section class="panel-card prose" id="reports">
                            <h2>Handling reports</h2>
                            <p>Members can report any live post. Reports for the same post are grouped, so the <b>Reports</b> column shows how many people flagged it.</p>
                            <ul>
                                <li><b>Dismiss</b> <i class="bi bi-check2"></i> when the post doesn't break the rules. It stays live.</li>
                                <li><b>Remove post</b> <i class="bi bi-trash3"></i> when it does. The post and media are deleted and the author is notified.</li>
                                <li>Reporter identities are visible to admins only, never to the post author.</li>
                            </ul>
                        </section>

                        <section class="panel-card prose" id="cleanup">
                            <h2>30-day auto cleanup</h2>
                            <p>Posts are deleted automatically 30 days after they go live by the Laravel Scheduler. The <a href="{{ route('admin.dashboard') }}">dashboard</a> shows when each job last ran. On Hostinger, a single cron job runs the scheduler every minute:</p>
<pre><code>* * * * * cd /home/USER/domains/feedeeforums.com/laravel &amp;&amp; php artisan schedule:run &gt;&gt; /dev/null 2&gt;&amp;1</code></pre>
                            <p>If the dashboard shows a job that hasn't run recently, check the cron entry in <b>hPanel &rarr; Advanced &rarr; Cron Jobs</b>. You can also use <b>Run now</b> on the dashboard to trigger cleanup manually.</p>
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
