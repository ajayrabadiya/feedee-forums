@extends('layouts.admin')

@section('title', 'Dashboard - Feedee Forums Admin')
@section('heading', 'Dashboard')
@section('subheading', now()->format('l, F j, Y'))

@section('content')
                <!-- Stats -->
                <div class="row g-3 mb-4">
                    <div class="col-sm-6 col-xl-3">
                        <div class="stat-card">
                            <div class="stat-top"><span class="stat-icon ic-amber"><i class="bi bi-hourglass-split"></i></span><span class="trend up">+5 today</span></div>
                            <strong>14</strong>
                            <span class="label">Posts awaiting review</span>
                        </div>
                    </div>
                    <div class="col-sm-6 col-xl-3">
                        <div class="stat-card">
                            <div class="stat-top"><span class="stat-icon ic-red"><i class="bi bi-flag"></i></span><span class="trend down">+2 today</span></div>
                            <strong>5</strong>
                            <span class="label">Open reports</span>
                        </div>
                    </div>
                    <div class="col-sm-6 col-xl-3">
                        <div class="stat-card">
                            <div class="stat-top"><span class="stat-icon ic-green"><i class="bi bi-check2-circle"></i></span><span class="trend up">+12%</span></div>
                            <strong>37</strong>
                            <span class="label">Approved today</span>
                        </div>
                    </div>
                    <div class="col-sm-6 col-xl-3">
                        <div class="stat-card">
                            <div class="stat-top"><span class="stat-icon ic-blue"><i class="bi bi-people"></i></span><span class="trend up">+318 this week</span></div>
                            <strong>128,402</strong>
                            <span class="label">Registered members</span>
                        </div>
                    </div>
                </div>

                <div class="row g-4 mb-4">
                    <!-- Chart -->
                    <div class="col-12">
                        <section class="panel h-100">
                            <div class="panel-head">
                                <div>
                                    <h2>Posts this week</h2>
                                    <p>Submitted vs. approved, last 7 days</p>
                                </div>
                                <div class="legend"><span><i></i>Submitted</span><span><i class="alt"></i>Approved</span></div>
                            </div>
                            <div class="panel-body fill">
                                <div class="bar-chart" role="img" aria-label="Bar chart of posts submitted and approved over the last 7 days">
                                    <div class="bar"><div class="bar-track"><span class="bar-fill" style="height: 52%;" data-value="31"></span><span class="bar-fill alt" style="height: 45%;"></span></div><small>Sat</small></div>
                                    <div class="bar"><div class="bar-track"><span class="bar-fill" style="height: 68%;" data-value="41"></span><span class="bar-fill alt" style="height: 60%;"></span></div><small>Sun</small></div>
                                    <div class="bar"><div class="bar-track"><span class="bar-fill" style="height: 44%;" data-value="26"></span><span class="bar-fill alt" style="height: 40%;"></span></div><small>Mon</small></div>
                                    <div class="bar"><div class="bar-track"><span class="bar-fill" style="height: 58%;" data-value="35"></span><span class="bar-fill alt" style="height: 51%;"></span></div><small>Tue</small></div>
                                    <div class="bar"><div class="bar-track"><span class="bar-fill" style="height: 75%;" data-value="45"></span><span class="bar-fill alt" style="height: 66%;"></span></div><small>Wed</small></div>
                                    <div class="bar"><div class="bar-track"><span class="bar-fill" style="height: 90%;" data-value="54"></span><span class="bar-fill alt" style="height: 79%;"></span></div><small>Thu</small></div>
                                    <div class="bar"><div class="bar-track"><span class="bar-fill" style="height: 62%;" data-value="37"></span><span class="bar-fill alt" style="height: 38%;"></span></div><small>Fri</small></div>
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
                                    <p>Oldest first, so nobody waits too long</p>
                                </div>
                                <a href="{{ route('admin.posts') }}" class="link-more">Open queue <i class="bi bi-arrow-right"></i></a>
                            </div>
                            <div class="table-wrap">
                                <table class="admin-table" data-table data-compact data-noun="posts" data-empty="All caught up!">
                                    <thead>
                                        <tr>
                                            <th scope="col">Post</th>
                                            <th scope="col" class="no-sort">Media</th>
                                            <th scope="col">Status</th>
                                            <th scope="col" class="text-end no-sort">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr data-status="pending" data-featured="0">
                                            <td>
                                                <div class="cell-post">
                                                    <img src="https://picsum.photos/seed/ff-q4/108/108" width="54" height="54" alt="Charcoal sketch of a dog" loading="lazy">
                                                    <div><strong>Charcoal sketch of my dog Biscuit</strong><small>priya_draws &middot; 3 hours ago</small></div>
                                                </div>
                                            </td>
                                            <td><span class="media-count"><span><i class="bi bi-image"></i>2</span></span></td>
                                            <td><span class="status st-pending">Pending</span></td>
                                            <td>
                                                <div class="row-actions">
                                                    <button type="button" class="act approve" title="Approve" aria-label="Approve"><i class="bi bi-check-lg"></i></button>
                                                    <button type="button" class="act reject" title="Reject" aria-label="Reject"><i class="bi bi-x-lg"></i></button>
                                                    <a href="{{ route('posts.show', 'Dg5sK1tY') }}" class="act view" title="Preview" aria-label="Preview"><i class="bi bi-eye"></i></a>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr data-status="pending" data-featured="0">
                                            <td>
                                                <div class="cell-post">
                                                    <img src="https://picsum.photos/seed/ff-q3/108/108" width="54" height="54" alt="Promotional sale banner" loading="lazy">
                                                    <div><strong>Check out my shop, 50% off everything!!</strong><small>deals4u_22 &middot; 2 hours ago</small></div>
                                                </div>
                                            </td>
                                            <td><span class="media-count"><span><i class="bi bi-image"></i>1</span></span></td>
                                            <td><span class="status st-pending">Pending</span></td>
                                            <td>
                                                <div class="row-actions">
                                                    <button type="button" class="act approve" title="Approve" aria-label="Approve"><i class="bi bi-check-lg"></i></button>
                                                    <button type="button" class="act reject" title="Reject" aria-label="Reject"><i class="bi bi-x-lg"></i></button>
                                                    <a href="{{ route('posts.show', 'Sh0pD34l') }}" class="act view" title="Preview" aria-label="Preview"><i class="bi bi-eye"></i></a>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr data-status="pending" data-featured="0">
                                            <td>
                                                <div class="cell-post">
                                                    <img src="https://picsum.photos/seed/ff-q2/108/108" width="54" height="54" alt="Farmers market stall video thumbnail" loading="lazy">
                                                    <div><strong>Quick clip from the farmers market this morning</strong><small>tomas_g &middot; 1 hour ago</small></div>
                                                </div>
                                            </td>
                                            <td><span class="media-count"><span><i class="bi bi-camera-video"></i>0:38</span></span></td>
                                            <td><span class="status st-pending">Pending</span></td>
                                            <td>
                                                <div class="row-actions">
                                                    <button type="button" class="act approve" title="Approve" aria-label="Approve"><i class="bi bi-check-lg"></i></button>
                                                    <button type="button" class="act reject" title="Reject" aria-label="Reject"><i class="bi bi-x-lg"></i></button>
                                                    <a href="{{ route('posts.show', 'Fm9pX3aZ') }}" class="act view" title="Preview" aria-label="Preview"><i class="bi bi-eye"></i></a>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr data-status="pending" data-featured="0">
                                            <td>
                                                <div class="cell-post">
                                                    <img src="https://picsum.photos/seed/ff-q1/108/108" width="54" height="54" alt="Bowl of pumpkin soup" loading="lazy">
                                                    <div><strong>Homemade pumpkin soup for the first cold evening</strong><small>autumn_eats &middot; 12 minutes ago</small></div>
                                                </div>
                                            </td>
                                            <td><span class="media-count"><span><i class="bi bi-image"></i>3</span></span></td>
                                            <td><span class="status st-pending">Pending</span></td>
                                            <td>
                                                <div class="row-actions">
                                                    <button type="button" class="act approve" title="Approve" aria-label="Approve"><i class="bi bi-check-lg"></i></button>
                                                    <button type="button" class="act reject" title="Reject" aria-label="Reject"><i class="bi bi-x-lg"></i></button>
                                                    <a href="{{ route('posts.show', 'Rt6uB2nQ') }}" class="act view" title="Preview" aria-label="Preview"><i class="bi bi-eye"></i></a>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </section>
                    </div>

                    <!-- Reports -->
                    <div class="col-xl-4">
                        <section class="panel h-100">
                            <div class="panel-head">
                                <div>
                                    <h2>Latest reports</h2>
                                    <p>5 open</p>
                                </div>
                                <a href="{{ route('admin.reports') }}" class="link-more">View all <i class="bi bi-arrow-right"></i></a>
                            </div>
                            <ul class="report-list">
                                <li>
                                    <span class="stat-icon ic-red"><i class="bi bi-megaphone"></i></span>
                                    <div><strong>"Free followers, click the link in my profile"</strong><p>Spam &middot; 7 reports &middot; 20 min ago</p></div>
                                </li>
                                <li>
                                    <span class="stat-icon ic-amber"><i class="bi bi-person-x"></i></span>
                                    <div><strong>"Can't believe what my neighbour did today"</strong><p>Private information &middot; 3 reports &middot; 1 hour ago</p></div>
                                </li>
                                <li>
                                    <span class="stat-icon ic-purple"><i class="bi bi-image"></i></span>
                                    <div><strong>"Night out with the crew"</strong><p>Inappropriate media &middot; 2 reports &middot; 3 hours ago</p></div>
                                </li>
                                <li>
                                    <span class="stat-icon ic-blue"><i class="bi bi-chat-left-dots"></i></span>
                                    <div><strong>"Unpopular opinion about pineapple pizza"</strong><p>Harassment &middot; 1 report &middot; 5 hours ago</p></div>
                                </li>
                            </ul>
                        </section>
                    </div>
                </div>
@endsection

@push('modals')
    @include('admin.partials.reject-modal')
@endpush
