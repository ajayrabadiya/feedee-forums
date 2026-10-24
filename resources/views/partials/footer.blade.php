    <footer class="site-footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-brand">
                    <img src="{{ asset('assets/images/logo.png') }}" alt="Feedee Forums" width="160" height="40" loading="lazy">
                    <p>A warm, friendly place to share stories, recipes, art and everyday moments. Every post is kept for 30 days, then permanently deleted.</p>
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
                        <li><a href="{{ route('login') }}">Log in</a></li>
                        <li><a href="{{ route('register') }}">Sign up</a></li>
                        <li><a href="{{ asset('sitemap.xml') }}">Sitemap</a></li>
                    </ul>
                </div>
            </div>
            @include('partials.footer-bottom')
        </div>
    </footer>
