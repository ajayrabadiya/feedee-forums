    <header class="site-header">
        <div class="container header-inner">
            <a href="{{ route('home') }}" class="logo" aria-label="Feedee Forums home"><img src="{{ asset('assets/images/logo.png') }}" alt="Feedee Forums" width="160" height="40"></a>

            <nav class="main-nav" id="mainNav" aria-label="Main">
                <a href="{{ route('home') }}" @class(['active' => request()->routeIs('home')]) @if (request()->routeIs('home')) aria-current="page" @endif><i class="bi bi-house-door"></i>Feed</a>
                <a href="{{ route('rules') }}" @class(['active' => request()->routeIs('rules')]) @if (request()->routeIs('rules')) aria-current="page" @endif><i class="bi bi-journal-text"></i>Rules</a>
                <div class="dropdown nav-drop">
                    <button type="button" class="nav-drop-toggle" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false"><i class="bi bi-hourglass-split"></i>How it works<i class="bi bi-chevron-down caret"></i></button>
                    <div class="dropdown-menu how-panel">
                        <p class="how-panel-head">How posts work</p>
                        <ol class="lifecycle">
                            <li><span class="step-icon"><i class="bi bi-upload"></i></span><div><strong>You share</strong><span>Photos, a short video or a story</span></div></li>
                            <li><span class="step-icon"><i class="bi bi-shield-check"></i></span><div><strong>We keep it friendly</strong><span>Moderators look after the community</span></div></li>
                            <li><span class="step-icon"><i class="bi bi-broadcast"></i></span><div><strong>Live for 30 days</strong><span>Visible to the whole community</span></div></li>
                            <li><span class="step-icon"><i class="bi bi-trash3"></i></span><div><strong>Gone for good</strong><span>Post and media permanently deleted</span></div></li>
                        </ol>
                        <a href="{{ route('rules') }}#deletion" class="how-panel-link">Read about the 30-day policy<i class="bi bi-arrow-right"></i></a>
                    </div>
                </div>
                {{-- <a href="{{ route('support') }}" @class(['active' => request()->routeIs('support')])><i class="bi bi-heart"></i>Support</a> --}}
                <div class="dropdown nav-drop">
                    <button type="button" class="nav-drop-toggle" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false"><i class="bi bi-info-circle"></i>About<i class="bi bi-chevron-down caret"></i></button>
                    <div class="dropdown-menu how-panel about-panel">
                        <p class="how-panel-head">About Feedee Forums</p>
                        <p class="about-panel-text">Niche forum for belly stuffing and feedee content. You can only post photos or short videos (with a caption). Optional body text is allowed. Everything is moderated and permanently removed after 30 days.</p>
                        <div class="stats" data-site-stats hidden>
                            <div><strong data-stat="members"></strong><span>Members</span></div>
                            <div><strong data-stat="online"></strong><span>Online</span></div>
                        </div>
                        <a href="{{ route('posts.create') }}" class="how-panel-link">Create a post<i class="bi bi-arrow-right"></i></a>
                    </div>
                </div>
                <a href="{{ route('contact') }}" @class(['active' => request()->routeIs('contact')]) @if (request()->routeIs('contact')) aria-current="page" @endif><i class="bi bi-envelope"></i>Contact</a>
                <a href="{{ route('login') }}" class="d-sm-none"><i class="bi bi-box-arrow-in-right"></i>Log In</a>
            </nav>

            <div class="header-btns">
                <a href="{{ route('login') }}" class="btn-line">Log In</a>
                <a href="{{ route('register') }}" class="btn-fill">Sign Up</a>
            </div>

            <button class="nav-toggle" type="button" aria-label="Toggle menu" aria-controls="mainNav" aria-expanded="false"><i class="bi bi-list"></i></button>
        </div>
    </header>
