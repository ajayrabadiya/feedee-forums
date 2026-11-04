    <footer class="site-footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-brand">
                    <img src="{{ asset('assets/images/logo.png') }}" alt="Feedee Forums" width="160" height="40" loading="lazy">
                    <p>A space for feedee, belly stuffing, belly expansion, and weight gain content. Post photos, stories, and connect with other feedees and feeders.</p>
                </div>
                <div>
                    <h4>Community</h4>
                    <ul class="footer-links">
                        <li><a href="{{ route('home') }}">Latest posts</a></li>
                        <li><a href="{{ route('posts.create') }}">Create a post</a></li>
                        <li><a href="{{ route('rules') }}">Community rules</a></li>
                    </ul>
                </div>
                <div>
                    <h4>Help</h4>
                    <ul class="footer-links">
                        {{-- <li><a href="{{ route('support') }}">Support us</a></li> --}}
                        <li><a href="{{ route('contact') }}">Contact</a></li>
                        <li><a href="{{ route('contact') }}#faq">FAQ</a></li>
                    </ul>
                </div>
                <div>
                    <h4>Account</h4>
                    <ul class="footer-links">
                        @auth
                            <li><a href="{{ auth()->user()->dashboardUrl() }}">Dashboard</a></li>
                            <li><a href="{{ route('profile.edit') }}">Profile</a></li>
                        @else
                            <li><a href="{{ route('login', ['redirect' => request()->getRequestUri()]) }}">Log in</a></li>
                            <li><a href="{{ route('register', ['redirect' => request()->getRequestUri()]) }}">Sign up</a></li>
                        @endauth
                        <li><a href="{{ route('sitemap') }}">Sitemap</a></li>
                    </ul>
                </div>
            </div>
            @include('partials.footer-bottom')
        </div>
    </footer>
