        <aside class="admin-side" aria-label="Admin navigation">
            <a href="{{ route('admin.dashboard') }}" class="logo"><img src="{{ asset('assets/images/logo.png') }}" alt="Feedee Forums" width="150" height="40"></a>

            <div class="side-label">Overview</div>
            <ul class="side-nav">
                <li><a href="{{ route('admin.dashboard') }}"@if (request()->routeIs('admin.dashboard')) class="active" aria-current="page"@endif><i class="bi bi-grid-1x2"></i>Dashboard</a></li>
            </ul>

            <div class="side-label">Moderation</div>
            <ul class="side-nav">
                <li><a href="{{ route('admin.posts') }}"@if (request()->routeIs('admin.posts')) class="active" aria-current="page"@endif><i class="bi bi-file-earmark-richtext"></i>Posts<span class="count" data-pending-count title="Posts waiting for review" @if (! $pendingPostsCount) hidden @endif>{{ $pendingPostsCount }}</span></a></li>
                <li><a href="{{ route('admin.reports') }}"@if (request()->routeIs('admin.reports')) class="active" aria-current="page"@endif><i class="bi bi-flag"></i>Reports<span class="count hot">5</span></a></li>
            </ul>

            <div class="side-label">Help</div>
            <ul class="side-nav">
                <li><a href="{{ route('admin.docs') }}"@if (request()->routeIs('admin.docs')) class="active" aria-current="page"@endif><i class="bi bi-book"></i>Admin guide</a></li>
                <li><a href="{{ route('home') }}" target="_blank" rel="noopener"><i class="bi bi-box-arrow-up-right"></i>View site</a></li>
            </ul>

            <div class="side-foot">
                <div class="cron-mini">
                    <strong><span class="dot-live"></span>Scheduler running</strong>
                    Last cleanup today at 03:00<br>Next run in 3h 42m
                </div>
                <ul class="side-nav">
                    <li>
                        <form method="post" action="{{ route('logout') }}">
                            @csrf
                            <a href="{{ route('logout') }}" data-logout><i class="bi bi-box-arrow-left"></i>Log out</a>
                        </form>
                    </li>
                </ul>
            </div>
        </aside>
