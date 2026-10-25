@extends('layouts.admin')

@section('title', 'Posts - Feedee Forums Admin')
@section('heading', 'Posts')
@section('subheading', 'Approve, reject, feature or delete posts')

@section('content')
                <div class="setting-card">
                    <span class="stat-icon ic-amber"><i class="bi bi-shield-check"></i></span>
                    <div class="setting-text">
                        <label for="requireApproval">Require approval for new posts</label>
                        <p class="setting-hint">Off: new posts go live straight away and you can still reject, feature or delete them at any time.</p>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="requireApproval" data-approval-switch>
                    </div>
                </div>

                <section class="panel">
                    <div class="tabs-line" role="tablist" aria-label="Filter posts by status">
                        <button type="button" class="active" data-tab="pending" role="tab" aria-selected="true">Pending <span class="count">0</span></button>
                        <button type="button" data-tab="approved" role="tab" aria-selected="false">Approved <span class="count">0</span></button>
                        <button type="button" data-tab="featured" role="tab" aria-selected="false">Featured <span class="count">0</span></button>
                        <button type="button" data-tab="rejected" role="tab" aria-selected="false">Rejected <span class="count">0</span></button>
                        <button type="button" data-tab="all" role="tab" aria-selected="false">All <span class="count">0</span></button>
                    </div>

                    <div class="toolbar">
                        <div class="admin-search">
                            <i class="bi bi-search"></i>
                            <input type="search" class="table-search" placeholder="Filter by title, author or topic" aria-label="Filter posts">
                        </div>
                        <select class="form-select column-filter" data-column="1" aria-label="Filter by topic">
                            <option value="">All topics</option>
                            <option>Announcements</option>
                            <option>Recipes</option>
                            <option>The Lounge</option>
                            <option>Meetups</option>
                            <option>Art &amp; Stories</option>
                            <option>Outdoors</option>
                        </select>
                        <span class="spacer"></span>
                        <label class="page-length-wrap">Show
                            <select class="form-select page-length" aria-label="Posts per page">
                                <option>10</option>
                                <option>25</option>
                                <option>50</option>
                            </select>
                        </label>
                    </div>

                    <div class="bulk-bar" aria-live="polite">
                        <strong><span class="bulk-count">0</span> selected</strong>
                        <button type="button" class="btn-soft sm" data-bulk="approve"><i class="bi bi-check-lg"></i>Approve</button>
                        <button type="button" class="btn-soft sm" data-bulk="feature"><i class="bi bi-star"></i>Feature</button>
                        <button type="button" class="btn-soft sm" data-bulk="reject"><i class="bi bi-x-lg"></i>Reject</button>
                        <button type="button" class="btn-soft sm danger" data-bulk="delete"><i class="bi bi-trash3"></i>Delete</button>
                    </div>

                        <table class="admin-table" data-table data-noun="posts" data-empty="No posts here. Nice work!">
                            <thead>
                                <tr>
                                    <th scope="col" class="check-col no-sort"><input class="form-check-input check-all" type="checkbox" aria-label="Select all posts"></th>
                                    <th scope="col">Post</th>
                                    <th scope="col">Author</th>
                                    <th scope="col" class="no-sort hide-xl">Media</th>
                                    <th scope="col">Submitted</th>
                                    <th scope="col" class="hide-xl">Expires in</th>
                                    <th scope="col">Status</th>
                                    <th scope="col" class="text-end no-sort">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr data-status="pending" data-featured="0">
                                    <td class="check-col"><input class="form-check-input row-check" type="checkbox" aria-label="Select post"></td>
                                    <td><a href="{{ route('posts.show', 'Rt6uB2nQ') }}" class="cell-post"><img src="https://picsum.photos/seed/ff-q1/108/108" width="54" height="54" alt="Bowl of pumpkin soup" loading="lazy"><div><strong>Homemade pumpkin soup for the first cold evening</strong><small>Recipes &middot; #Rt6uB2nQ</small></div></a></td>
                                    <td><span class="cell-user"><img src="https://picsum.photos/seed/ff-u11/56/56" width="28" height="28" alt="" loading="lazy">autumn_eats</span></td>
                                    <td class="hide-xl"><span class="media-count"><span><i class="bi bi-image"></i>3</span></span></td>
                                    <td class="nowrap muted" data-order="12">12 min ago</td>
                                    <td class="nowrap muted hide-xl" data-order="999">&mdash;</td>
                                    <td class="nowrap"><span class="status st-pending">Pending</span></td>
                                    <td>
                                        <div class="row-actions">
                                            <button type="button" class="act approve" title="Approve" aria-label="Approve"><i class="bi bi-check-lg"></i></button>
                                            <button type="button" class="act reject" title="Reject" aria-label="Reject"><i class="bi bi-x-lg"></i></button>
                                            <button type="button" class="act feature" title="Feature" aria-label="Feature" aria-pressed="false"><i class="bi bi-star"></i></button>
                                            <button type="button" class="act delete" title="Delete" aria-label="Delete"><i class="bi bi-trash3"></i></button>
                                        </div>
                                    </td>
                                </tr>
                                <tr data-status="pending" data-featured="0">
                                    <td class="check-col"><input class="form-check-input row-check" type="checkbox" aria-label="Select post"></td>
                                    <td><a href="{{ route('posts.show', 'Bk2cL7wE') }}" class="cell-post"><span class="no-media"><i class="bi bi-text-paragraph"></i></span><div><strong>Our book club's next pick is here</strong><small>The Lounge &middot; #Bk2cL7wE</small></div></a></td>
                                    <td><span class="cell-user"><img src="https://picsum.photos/seed/ff-u12/56/56" width="28" height="28" alt="" loading="lazy">reading_nook</span></td>
                                    <td class="hide-xl"><span class="media-count"><span>Text only</span></span></td>
                                    <td class="nowrap muted" data-order="35">35 min ago</td>
                                    <td class="nowrap muted hide-xl" data-order="999">&mdash;</td>
                                    <td class="nowrap"><span class="status st-pending">Pending</span></td>
                                    <td>
                                        <div class="row-actions">
                                            <button type="button" class="act approve" title="Approve" aria-label="Approve"><i class="bi bi-check-lg"></i></button>
                                            <button type="button" class="act reject" title="Reject" aria-label="Reject"><i class="bi bi-x-lg"></i></button>
                                            <button type="button" class="act feature" title="Feature" aria-label="Feature" aria-pressed="false"><i class="bi bi-star"></i></button>
                                            <button type="button" class="act delete" title="Delete" aria-label="Delete"><i class="bi bi-trash3"></i></button>
                                        </div>
                                    </td>
                                </tr>
                                <tr data-status="pending" data-featured="0">
                                    <td class="check-col"><input class="form-check-input row-check" type="checkbox" aria-label="Select post"></td>
                                    <td><a href="{{ route('posts.show', 'Fm9pX3aZ') }}" class="cell-post"><img src="https://picsum.photos/seed/ff-q2/108/108" width="54" height="54" alt="Farmers market stall video thumbnail" loading="lazy"><div><strong>Quick clip from the farmers market this morning</strong><small>Outdoors &middot; #Fm9pX3aZ</small></div></a></td>
                                    <td><span class="cell-user"><img src="https://picsum.photos/seed/ff-u13/56/56" width="28" height="28" alt="" loading="lazy">tomas_g</span></td>
                                    <td class="hide-xl"><span class="media-count"><span><i class="bi bi-camera-video"></i>0:38</span></span></td>
                                    <td class="nowrap muted" data-order="60">1 hour ago</td>
                                    <td class="nowrap muted hide-xl" data-order="999">&mdash;</td>
                                    <td class="nowrap"><span class="status st-pending">Pending</span></td>
                                    <td>
                                        <div class="row-actions">
                                            <button type="button" class="act approve" title="Approve" aria-label="Approve"><i class="bi bi-check-lg"></i></button>
                                            <button type="button" class="act reject" title="Reject" aria-label="Reject"><i class="bi bi-x-lg"></i></button>
                                            <button type="button" class="act feature" title="Feature" aria-label="Feature" aria-pressed="false"><i class="bi bi-star"></i></button>
                                            <button type="button" class="act delete" title="Delete" aria-label="Delete"><i class="bi bi-trash3"></i></button>
                                        </div>
                                    </td>
                                </tr>
                                <tr data-status="pending" data-featured="0">
                                    <td class="check-col"><input class="form-check-input row-check" type="checkbox" aria-label="Select post"></td>
                                    <td><a href="{{ route('posts.show', 'Sh0pD34l') }}" class="cell-post"><img src="https://picsum.photos/seed/ff-q3/108/108" width="54" height="54" alt="Promotional sale banner" loading="lazy"><div><strong>Check out my shop, 50% off everything!!</strong><small>The Lounge &middot; #Sh0pD34l</small></div></a></td>
                                    <td><span class="cell-user"><img src="https://picsum.photos/seed/ff-u14/56/56" width="28" height="28" alt="" loading="lazy">deals4u_22</span></td>
                                    <td class="hide-xl"><span class="media-count"><span><i class="bi bi-image"></i>1</span></span></td>
                                    <td class="nowrap muted" data-order="120">2 hours ago</td>
                                    <td class="nowrap muted hide-xl" data-order="999">&mdash;</td>
                                    <td class="nowrap"><span class="status st-pending">Pending</span></td>
                                    <td>
                                        <div class="row-actions">
                                            <button type="button" class="act approve" title="Approve" aria-label="Approve"><i class="bi bi-check-lg"></i></button>
                                            <button type="button" class="act reject" title="Reject" aria-label="Reject"><i class="bi bi-x-lg"></i></button>
                                            <button type="button" class="act feature" title="Feature" aria-label="Feature" aria-pressed="false"><i class="bi bi-star"></i></button>
                                            <button type="button" class="act delete" title="Delete" aria-label="Delete"><i class="bi bi-trash3"></i></button>
                                        </div>
                                    </td>
                                </tr>
                                <tr data-status="pending" data-featured="0">
                                    <td class="check-col"><input class="form-check-input row-check" type="checkbox" aria-label="Select post"></td>
                                    <td><a href="{{ route('posts.show', 'Dg5sK1tY') }}" class="cell-post"><img src="https://picsum.photos/seed/ff-q4/108/108" width="54" height="54" alt="Charcoal sketch of a dog" loading="lazy"><div><strong>Charcoal sketch of my dog Biscuit</strong><small>Art &amp; Stories &middot; #Dg5sK1tY</small></div></a></td>
                                    <td><span class="cell-user"><img src="https://picsum.photos/seed/ff-u5/56/56" width="28" height="28" alt="" loading="lazy">priya_draws</span></td>
                                    <td class="hide-xl"><span class="media-count"><span><i class="bi bi-image"></i>2</span></span></td>
                                    <td class="nowrap muted" data-order="180">3 hours ago</td>
                                    <td class="nowrap muted hide-xl" data-order="999">&mdash;</td>
                                    <td class="nowrap"><span class="status st-pending">Pending</span></td>
                                    <td>
                                        <div class="row-actions">
                                            <button type="button" class="act approve" title="Approve" aria-label="Approve"><i class="bi bi-check-lg"></i></button>
                                            <button type="button" class="act reject" title="Reject" aria-label="Reject"><i class="bi bi-x-lg"></i></button>
                                            <button type="button" class="act feature" title="Feature" aria-label="Feature" aria-pressed="false"><i class="bi bi-star"></i></button>
                                            <button type="button" class="act delete" title="Delete" aria-label="Delete"><i class="bi bi-trash3"></i></button>
                                        </div>
                                    </td>
                                </tr>
                                <tr data-status="approved" data-featured="0">
                                    <td class="check-col"><input class="form-check-input row-check" type="checkbox" aria-label="Select post"></td>
                                    <td><a href="{{ route('posts.show', 'k7Xq92Lm') }}" class="cell-post"><img src="https://picsum.photos/seed/ff-p1/108/108" width="54" height="54" alt="Tray of cinnamon rolls" loading="lazy"><div><strong>Finally got my grandma's cinnamon roll recipe right</strong><small>Recipes &middot; #k7Xq92Lm</small></div></a></td>
                                    <td><span class="cell-user"><img src="https://picsum.photos/seed/ff-u2/56/56" width="28" height="28" alt="" loading="lazy">maya_bakes</span></td>
                                    <td class="hide-xl"><span class="media-count"><span><i class="bi bi-image"></i>4</span><span><i class="bi bi-camera-video"></i>0:18</span></span></td>
                                    <td class="nowrap muted" data-order="120">2 hours ago</td>
                                    <td class="nowrap muted hide-xl" data-order="30">30 days</td>
                                    <td class="nowrap"><span class="status st-approved">Approved</span></td>
                                    <td>
                                        <div class="row-actions">
                                            <button type="button" class="act approve" title="Approve" aria-label="Approve" disabled><i class="bi bi-check-lg"></i></button>
                                            <button type="button" class="act reject" title="Reject" aria-label="Reject"><i class="bi bi-x-lg"></i></button>
                                            <button type="button" class="act feature" title="Feature" aria-label="Feature" aria-pressed="false"><i class="bi bi-star"></i></button>
                                            <button type="button" class="act delete" title="Delete" aria-label="Delete"><i class="bi bi-trash3"></i></button>
                                        </div>
                                    </td>
                                </tr>
                                <tr data-status="approved" data-featured="0">
                                    <td class="check-col"><input class="form-check-input row-check" type="checkbox" aria-label="Select post"></td>
                                    <td><a href="{{ route('posts.show', 'Qw81rTzA') }}" class="cell-post"><img src="https://picsum.photos/seed/ff-v1/108/108" width="54" height="54" alt="Coastal road at sunset" loading="lazy"><div><strong>Sunset ride along the coast this evening</strong><small>Outdoors &middot; #Qw81rTzA</small></div></a></td>
                                    <td><span class="cell-user"><img src="https://picsum.photos/seed/ff-u7/56/56" width="28" height="28" alt="" loading="lazy">jonah_rides</span></td>
                                    <td class="hide-xl"><span class="media-count"><span><i class="bi bi-camera-video"></i>0:42</span></span></td>
                                    <td class="nowrap muted" data-order="240">4 hours ago</td>
                                    <td class="nowrap muted hide-xl" data-order="30">30 days</td>
                                    <td class="nowrap"><span class="status st-approved">Approved</span></td>
                                    <td>
                                        <div class="row-actions">
                                            <button type="button" class="act approve" title="Approve" aria-label="Approve" disabled><i class="bi bi-check-lg"></i></button>
                                            <button type="button" class="act reject" title="Reject" aria-label="Reject"><i class="bi bi-x-lg"></i></button>
                                            <button type="button" class="act feature" title="Feature" aria-label="Feature" aria-pressed="false"><i class="bi bi-star"></i></button>
                                            <button type="button" class="act delete" title="Delete" aria-label="Delete"><i class="bi bi-trash3"></i></button>
                                        </div>
                                    </td>
                                </tr>
                                <tr data-status="approved" data-featured="0">
                                    <td class="check-col"><input class="form-check-input row-check" type="checkbox" aria-label="Select post"></td>
                                    <td><a href="{{ route('posts.show', 'Hb7Ys0kD') }}" class="cell-post"><img src="https://picsum.photos/seed/ff-p2/108/108" width="54" height="54" alt="Watercolour painting of a cafe corner" loading="lazy"><div><strong>I painted my favourite cafe corner this weekend</strong><small>Art &amp; Stories &middot; #Hb7Ys0kD</small></div></a></td>
                                    <td><span class="cell-user"><img src="https://picsum.photos/seed/ff-u5/56/56" width="28" height="28" alt="" loading="lazy">priya_draws</span></td>
                                    <td class="hide-xl"><span class="media-count"><span><i class="bi bi-image"></i>2</span></span></td>
                                    <td class="nowrap muted" data-order="1440">Yesterday</td>
                                    <td class="nowrap muted hide-xl" data-order="29">29 days</td>
                                    <td class="nowrap"><span class="status st-approved">Approved</span></td>
                                    <td>
                                        <div class="row-actions">
                                            <button type="button" class="act approve" title="Approve" aria-label="Approve" disabled><i class="bi bi-check-lg"></i></button>
                                            <button type="button" class="act reject" title="Reject" aria-label="Reject"><i class="bi bi-x-lg"></i></button>
                                            <button type="button" class="act feature" title="Feature" aria-label="Feature" aria-pressed="false"><i class="bi bi-star"></i></button>
                                            <button type="button" class="act delete" title="Delete" aria-label="Delete"><i class="bi bi-trash3"></i></button>
                                        </div>
                                    </td>
                                </tr>
                                <tr data-status="approved" data-featured="1">
                                    <td class="check-col"><input class="form-check-input row-check" type="checkbox" aria-label="Select post"></td>
                                    <td><a href="{{ route('posts.show', 'aN4w8Qz1') }}" class="cell-post"><span class="no-media"><i class="bi bi-megaphone"></i></span><div><strong>Welcome to Feedee Forums - here's how posting works</strong><small>Announcements &middot; #aN4w8Qz1</small></div></a></td>
                                    <td><span class="cell-user"><img src="https://picsum.photos/seed/ff-team/56/56" width="28" height="28" alt="" loading="lazy">Feedee Team</span></td>
                                    <td class="hide-xl"><span class="media-count"><span>Text only</span></span></td>
                                    <td class="nowrap muted" data-order="2880">2 days ago</td>
                                    <td class="nowrap muted hide-xl" data-order="28">28 days</td>
                                    <td class="nowrap"><span class="status st-approved">Approved</span><i class="bi bi-star-fill feature-flag" title="Featured" aria-label="Featured"></i></td>
                                    <td>
                                        <div class="row-actions">
                                            <button type="button" class="act approve" title="Approve" aria-label="Approve" disabled><i class="bi bi-check-lg"></i></button>
                                            <button type="button" class="act reject" title="Reject" aria-label="Reject"><i class="bi bi-x-lg"></i></button>
                                            <button type="button" class="act feature on" title="Feature" aria-label="Feature" aria-pressed="true"><i class="bi bi-star-fill"></i></button>
                                            <button type="button" class="act delete" title="Delete" aria-label="Delete"><i class="bi bi-trash3"></i></button>
                                        </div>
                                    </td>
                                </tr>
                                <tr data-status="approved" data-featured="0">
                                    <td class="check-col"><input class="form-check-input row-check" type="checkbox" aria-label="Select post"></td>
                                    <td><a href="{{ route('posts.show', 'Ga4fR8uN') }}" class="cell-post"><img src="https://picsum.photos/seed/ff-p4/108/108" width="54" height="54" alt="Balcony garden with flowers" loading="lazy"><div><strong>My little balcony garden is finally blooming</strong><small>Outdoors &middot; #Ga4fR8uN</small></div></a></td>
                                    <td><span class="cell-user"><img src="https://picsum.photos/seed/ff-u9/56/56" width="28" height="28" alt="" loading="lazy">ellie_garden</span></td>
                                    <td class="hide-xl"><span class="media-count"><span><i class="bi bi-image"></i>3</span></span></td>
                                    <td class="nowrap muted" data-order="39600">Sep 26</td>
                                    <td class="nowrap hide-xl" data-order="2"><span class="text-danger fw-semibold">2 days</span></td>
                                    <td class="nowrap"><span class="status st-approved">Approved</span></td>
                                    <td>
                                        <div class="row-actions">
                                            <button type="button" class="act approve" title="Approve" aria-label="Approve" disabled><i class="bi bi-check-lg"></i></button>
                                            <button type="button" class="act reject" title="Reject" aria-label="Reject"><i class="bi bi-x-lg"></i></button>
                                            <button type="button" class="act feature" title="Feature" aria-label="Feature" aria-pressed="false"><i class="bi bi-star"></i></button>
                                            <button type="button" class="act delete" title="Delete" aria-label="Delete"><i class="bi bi-trash3"></i></button>
                                        </div>
                                    </td>
                                </tr>
                                <tr data-status="rejected" data-featured="0">
                                    <td class="check-col"><input class="form-check-input row-check" type="checkbox" aria-label="Select post"></td>
                                    <td><a href="{{ route('posts.show', 'Xx0bT8812') }}" class="cell-post"><img src="https://picsum.photos/seed/ff-q5/108/108" width="54" height="54" alt="Screenshot of a follower giveaway" loading="lazy"><div><strong>Free followers, click the link in my profile</strong><small>The Lounge &middot; #Xx0bT8812</small></div></a></td>
                                    <td><span class="cell-user"><img src="https://picsum.photos/seed/ff-u15/56/56" width="28" height="28" alt="" loading="lazy">bot_8812</span></td>
                                    <td class="hide-xl"><span class="media-count"><span><i class="bi bi-image"></i>1</span></span></td>
                                    <td class="nowrap muted" data-order="1440">Yesterday</td>
                                    <td class="nowrap muted hide-xl" data-order="999">&mdash;</td>
                                    <td class="nowrap"><span class="status st-rejected">Rejected</span></td>
                                    <td>
                                        <div class="row-actions">
                                            <button type="button" class="act approve" title="Approve" aria-label="Approve"><i class="bi bi-check-lg"></i></button>
                                            <button type="button" class="act reject" title="Reject" aria-label="Reject" disabled><i class="bi bi-x-lg"></i></button>
                                            <button type="button" class="act feature" title="Feature" aria-label="Feature" aria-pressed="false"><i class="bi bi-star"></i></button>
                                            <button type="button" class="act delete" title="Delete" aria-label="Delete"><i class="bi bi-trash3"></i></button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                </section>
@endsection

@push('modals')
    @include('admin.partials.reject-modal')

    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title" id="deleteTitle">Delete permanently?</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="callout">
                        <i class="bi bi-exclamation-triangle"></i>
                        <div>
                            <strong class="modal-post-title"></strong>
                            <p>The post, all of its photos, videos and generated thumbnails will be permanently removed from storage. This can't be undone.</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-soft" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn-fill" id="deleteConfirm" style="background: var(--danger); border-color: var(--danger);"><i class="bi bi-trash3"></i>Delete forever</button>
                </div>
            </div>
        </div>
    </div>
@endpush
