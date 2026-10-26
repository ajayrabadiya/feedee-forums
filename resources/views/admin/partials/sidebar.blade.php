        <aside class="admin-side" aria-label="Admin navigation">
            <a href="{{ route('admin.dashboard') }}" class="logo"><img src="{{ asset('assets/images/logo.png') }}" alt="Feedee Forums" width="150" height="40"></a>

            <div class="side-label">Overview</div>
            <ul class="side-nav">
                <li><a href="{{ route('admin.dashboard') }}"@if (request()->routeIs('admin.dashboard')) class="active" aria-current="page"@endif><i class="bi bi-grid-1x2"></i>Dashboard</a></li>
            </ul>

            <div class="side-label">Moderation</div>
            <ul class="side-nav">
                <li><a href="{{ route('admin.posts') }}"@if (request()->routeIs('admin.posts')) class="active" aria-current="page"@endif><i class="bi bi-file-earmark-richtext"></i>Posts<span class="count" data-pending-count title="Posts waiting for review" @if (! $pendingPostsCount) hidden @endif>{{ $pendingPostsCount }}</span></a></li>
            </ul>

            <div class="side-label">Community</div>
            <ul class="side-nav">
                <li><a href="{{ route('admin.customers') }}"@if (request()->routeIs('admin.customers')) class="active" aria-current="page"@endif><i class="bi bi-people"></i>Customers</a></li>
                <li><a href="{{ route('admin.contacts.index') }}"@if (request()->routeIs('admin.contacts.*')) class="active" aria-current="page"@endif><i class="bi bi-envelope"></i>Contact messages @if ($unreadContactsCount)<span class="count hot" title="Unread messages">{{ $unreadContactsCount }}</span>@endif</a></li>
            </ul>

            <div class="side-label">System</div>
            <ul class="side-nav">
                <li><a href="{{ route('admin.settings') }}"@if (request()->routeIs('admin.settings')) class="active" aria-current="page"@endif><i class="bi bi-gear"></i>Settings</a></li>
            </ul>

            <div class="side-foot">
                <div class="cron-mini">
                    <strong><span class="dot-live"></span>Scheduler running</strong>
                    Last cleanup today at 03:00<br>Next run in 3h 42m
                </div>
            </div>
        </aside>
