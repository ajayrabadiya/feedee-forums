@extends('layouts.admin')

@section('title', 'Reports - Feedee Forums Admin')
@section('heading', 'Reports')
@section('subheading', 'Review posts flagged by the community')

@section('content')
                <div class="row g-3 mb-4">
                    <div class="col-sm-6 col-xl-3">
                        <div class="stat-card">
                            <div class="stat-top"><span class="stat-icon ic-red"><i class="bi bi-flag"></i></span><span class="trend down">+2 today</span></div>
                            <strong>5</strong>
                            <span class="label">Open reports</span>
                        </div>
                    </div>
                    <div class="col-sm-6 col-xl-3">
                        <div class="stat-card">
                            <div class="stat-top"><span class="stat-icon ic-green"><i class="bi bi-check2-circle"></i></span><span class="trend flat">7 days</span></div>
                            <strong>48</strong>
                            <span class="label">Resolved this week</span>
                        </div>
                    </div>
                    <div class="col-sm-6 col-xl-3">
                        <div class="stat-card">
                            <div class="stat-top"><span class="stat-icon ic-blue"><i class="bi bi-stopwatch"></i></span><span class="trend up">-18%</span></div>
                            <strong>42 min</strong>
                            <span class="label">Average response time</span>
                        </div>
                    </div>
                    <div class="col-sm-6 col-xl-3">
                        <div class="stat-card">
                            <div class="stat-top"><span class="stat-icon ic-purple"><i class="bi bi-trash3"></i></span><span class="trend flat">7 days</span></div>
                            <strong>11</strong>
                            <span class="label">Posts removed after reports</span>
                        </div>
                    </div>
                </div>

                <section class="panel">
                    <div class="tabs-line" role="tablist" aria-label="Filter reports by status">
                        <button type="button" class="active" data-tab="open" role="tab" aria-selected="true">Open <span class="count">0</span></button>
                        <button type="button" data-tab="resolved" role="tab" aria-selected="false">Resolved <span class="count">0</span></button>
                        <button type="button" data-tab="all" role="tab" aria-selected="false">All <span class="count">0</span></button>
                    </div>

                    <div class="toolbar">
                        <div class="admin-search">
                            <i class="bi bi-search"></i>
                            <input type="search" class="table-search" placeholder="Filter by post, reason or user" aria-label="Filter reports">
                        </div>
                        <select class="form-select column-filter" data-column="2" aria-label="Filter by reason">
                            <option value="">All reasons</option>
                            <option>Spam or self-promotion</option>
                            <option>Harassment or hate</option>
                            <option>Private information</option>
                            <option>Inappropriate media</option>
                            <option>Something else</option>
                        </select>
                        <span class="spacer"></span>
                        <label class="page-length-wrap">Show
                            <select class="form-select page-length" aria-label="Reports per page">
                                <option>10</option>
                                <option>25</option>
                                <option>50</option>
                            </select>
                        </label>
                    </div>

                    <div class="bulk-bar" aria-live="polite">
                        <strong><span class="bulk-count">0</span> selected</strong>
                        <button type="button" class="btn-soft sm" data-bulk="dismiss"><i class="bi bi-check2"></i>Dismiss reports</button>
                    </div>

                        <table class="admin-table" data-table data-noun="reports" data-empty="No reports here. The community is behaving!" data-order-col="3">
                            <thead>
                                <tr>
                                    <th scope="col" class="check-col no-sort"><input class="form-check-input check-all" type="checkbox" aria-label="Select all reports"></th>
                                    <th scope="col">Reported post</th>
                                    <th scope="col">Top reason</th>
                                    <th scope="col">Reports</th>
                                    <th scope="col" class="hide-xl no-sort">Latest note</th>
                                    <th scope="col">Last reported</th>
                                    <th scope="col">Status</th>
                                    <th scope="col" class="text-end no-sort">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr data-status="open">
                                    <td class="check-col"><input class="form-check-input row-check" type="checkbox" aria-label="Select report"></td>
                                    <td><a href="{{ route('posts.show', 'Fr3eF0ll') }}" class="cell-post"><img src="https://picsum.photos/seed/ff-r1/108/108" width="54" height="54" alt="Follower giveaway graphic" loading="lazy"><div><strong>Free followers, click the link in my profile</strong><small>by spam_bot_02 &middot; #Fr3eF0ll</small></div></a></td>
                                    <td><span class="reason">Spam or self-promotion</span></td>
                                    <td><span class="report-count">7</span></td>
                                    <td class="muted hide-xl">"Same link posted in every topic."</td>
                                    <td class="nowrap muted" data-order="20">20 min ago</td>
                                    <td><span class="status st-open">Open</span></td>
                                    <td>
                                        <div class="row-actions">
                                            <a href="{{ route('posts.show', 'Fr3eF0ll') }}" class="act view" title="View post" aria-label="View post"><i class="bi bi-eye"></i></a>
                                            <button type="button" class="act dismiss" title="Dismiss report" aria-label="Dismiss report"><i class="bi bi-check2"></i></button>
                                            <button type="button" class="act remove" title="Remove post" aria-label="Remove post"><i class="bi bi-trash3"></i></button>
                                        </div>
                                    </td>
                                </tr>
                                <tr data-status="open">
                                    <td class="check-col"><input class="form-check-input row-check" type="checkbox" aria-label="Select report"></td>
                                    <td><a href="{{ route('posts.show', 'Nb7hR2qP') }}" class="cell-post"><span class="no-media"><i class="bi bi-text-paragraph"></i></span><div><strong>Can't believe what my neighbour did today</strong><small>by grumpy_gus &middot; #Nb7hR2qP</small></div></a></td>
                                    <td><span class="reason">Private information</span></td>
                                    <td><span class="report-count">3</span></td>
                                    <td class="muted hide-xl">"Includes their full home address."</td>
                                    <td class="nowrap muted" data-order="60">1 hour ago</td>
                                    <td><span class="status st-open">Open</span></td>
                                    <td>
                                        <div class="row-actions">
                                            <a href="{{ route('posts.show', 'Nb7hR2qP') }}" class="act view" title="View post" aria-label="View post"><i class="bi bi-eye"></i></a>
                                            <button type="button" class="act dismiss" title="Dismiss report" aria-label="Dismiss report"><i class="bi bi-check2"></i></button>
                                            <button type="button" class="act remove" title="Remove post" aria-label="Remove post"><i class="bi bi-trash3"></i></button>
                                        </div>
                                    </td>
                                </tr>
                                <tr data-status="open">
                                    <td class="check-col"><input class="form-check-input row-check" type="checkbox" aria-label="Select report"></td>
                                    <td><a href="{{ route('posts.show', 'Nt0uTcr3') }}" class="cell-post"><img src="https://picsum.photos/seed/ff-r3/108/108" width="54" height="54" alt="Group photo at night" loading="lazy"><div><strong>Night out with the crew</strong><small>by dj_kev &middot; #Nt0uTcr3</small></div></a></td>
                                    <td><span class="reason">Inappropriate media</span></td>
                                    <td><span class="report-count">2</span></td>
                                    <td class="muted hide-xl">"I'm in photo 3 and didn't agree to it."</td>
                                    <td class="nowrap muted" data-order="180">3 hours ago</td>
                                    <td><span class="status st-open">Open</span></td>
                                    <td>
                                        <div class="row-actions">
                                            <a href="{{ route('posts.show', 'Nt0uTcr3') }}" class="act view" title="View post" aria-label="View post"><i class="bi bi-eye"></i></a>
                                            <button type="button" class="act dismiss" title="Dismiss report" aria-label="Dismiss report"><i class="bi bi-check2"></i></button>
                                            <button type="button" class="act remove" title="Remove post" aria-label="Remove post"><i class="bi bi-trash3"></i></button>
                                        </div>
                                    </td>
                                </tr>
                                <tr data-status="open">
                                    <td class="check-col"><input class="form-check-input row-check" type="checkbox" aria-label="Select report"></td>
                                    <td><a href="{{ route('posts.show', 'Pz4aP1ne') }}" class="cell-post"><img src="https://picsum.photos/seed/ff-r4/108/108" width="54" height="54" alt="Pineapple pizza slice" loading="lazy"><div><strong>Unpopular opinion about pineapple pizza</strong><small>by slice_of_life &middot; #Pz4aP1ne</small></div></a></td>
                                    <td><span class="reason">Harassment or hate</span></td>
                                    <td><span class="report-count">1</span></td>
                                    <td class="muted hide-xl">No note</td>
                                    <td class="nowrap muted" data-order="300">5 hours ago</td>
                                    <td><span class="status st-open">Open</span></td>
                                    <td>
                                        <div class="row-actions">
                                            <a href="{{ route('posts.show', 'Pz4aP1ne') }}" class="act view" title="View post" aria-label="View post"><i class="bi bi-eye"></i></a>
                                            <button type="button" class="act dismiss" title="Dismiss report" aria-label="Dismiss report"><i class="bi bi-check2"></i></button>
                                            <button type="button" class="act remove" title="Remove post" aria-label="Remove post"><i class="bi bi-trash3"></i></button>
                                        </div>
                                    </td>
                                </tr>
                                <tr data-status="open">
                                    <td class="check-col"><input class="form-check-input row-check" type="checkbox" aria-label="Select report"></td>
                                    <td><a href="{{ route('posts.show', 'Gl0wS3rm') }}" class="cell-post"><img src="https://picsum.photos/seed/ff-r5/108/108" width="54" height="54" alt="Product photo of skincare bottles" loading="lazy"><div><strong>This serum changed my life, link below</strong><small>by glow_up_gina &middot; #Gl0wS3rm</small></div></a></td>
                                    <td><span class="reason">Spam or self-promotion</span></td>
                                    <td><span class="report-count">2</span></td>
                                    <td class="muted hide-xl">"Affiliate link in the text."</td>
                                    <td class="nowrap muted" data-order="1440">Yesterday</td>
                                    <td><span class="status st-open">Open</span></td>
                                    <td>
                                        <div class="row-actions">
                                            <a href="{{ route('posts.show', 'Gl0wS3rm') }}" class="act view" title="View post" aria-label="View post"><i class="bi bi-eye"></i></a>
                                            <button type="button" class="act dismiss" title="Dismiss report" aria-label="Dismiss report"><i class="bi bi-check2"></i></button>
                                            <button type="button" class="act remove" title="Remove post" aria-label="Remove post"><i class="bi bi-trash3"></i></button>
                                        </div>
                                    </td>
                                </tr>
                                <tr data-status="resolved">
                                    <td class="check-col"><input class="form-check-input row-check" type="checkbox" aria-label="Select report"></td>
                                    <td><a href="{{ route('posts.show', 'Hb7Ys0kD') }}" class="cell-post"><img src="https://picsum.photos/seed/ff-p2/108/108" width="54" height="54" alt="Watercolour painting of a cafe corner" loading="lazy"><div><strong>I painted my favourite cafe corner this weekend</strong><small>by priya_draws &middot; #Hb7Ys0kD</small></div></a></td>
                                    <td><span class="reason">Something else</span></td>
                                    <td><span class="report-count">1</span></td>
                                    <td class="muted hide-xl">"Looks traced?"</td>
                                    <td class="nowrap muted" data-order="1440">Yesterday</td>
                                    <td><span class="status st-resolved">Dismissed</span></td>
                                    <td>
                                        <div class="row-actions">
                                            <a href="{{ route('posts.show', 'Hb7Ys0kD') }}" class="act view" title="View post" aria-label="View post"><i class="bi bi-eye"></i></a>
                                            <button type="button" class="act dismiss" title="Dismiss report" aria-label="Dismiss report" disabled><i class="bi bi-check2"></i></button>
                                            <button type="button" class="act remove" title="Remove post" aria-label="Remove post" disabled><i class="bi bi-trash3"></i></button>
                                        </div>
                                    </td>
                                </tr>
                                <tr data-status="resolved">
                                    <td class="check-col"><input class="form-check-input row-check" type="checkbox" aria-label="Select report"></td>
                                    <td><a href="{{ route('posts.show', 'An9rYz11') }}" class="cell-post"><span class="no-media"><i class="bi bi-text-paragraph"></i></span><div><strong>You're all idiots if you think otherwise</strong><small>by angry_andy &middot; #An9rYz11</small></div></a></td>
                                    <td><span class="reason">Harassment or hate</span></td>
                                    <td><span class="report-count">9</span></td>
                                    <td class="muted hide-xl">"Insulting other members."</td>
                                    <td class="nowrap muted" data-order="2880">2 days ago</td>
                                    <td><span class="status st-resolved">Post removed</span></td>
                                    <td>
                                        <div class="row-actions">
                                            <a href="{{ route('posts.show', 'An9rYz11') }}" class="act view" title="View post" aria-label="View post"><i class="bi bi-eye"></i></a>
                                            <button type="button" class="act dismiss" title="Dismiss report" aria-label="Dismiss report" disabled><i class="bi bi-check2"></i></button>
                                            <button type="button" class="act remove" title="Remove post" aria-label="Remove post" disabled><i class="bi bi-trash3"></i></button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                    <div class="panel-foot">
                        <span><i class="bi bi-incognito me-1"></i>Reporter identities are never shown to post authors</span>
                    </div>
                </section>
@endsection

@push('modals')
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title" id="deleteTitle">Remove reported post?</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="callout">
                        <i class="bi bi-exclamation-triangle"></i>
                        <div>
                            <strong class="modal-post-title"></strong>
                            <p>The post, all of its photos, videos and thumbnails will be permanently deleted and every related report marked as resolved. The author is notified by email.</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-soft" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn-fill" id="deleteConfirm" style="background: var(--danger); border-color: var(--danger);"><i class="bi bi-trash3"></i>Remove post</button>
                </div>
            </div>
        </div>
    </div>
@endpush
