<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
@include('partials.head')
</head>
<body>

    <div class="auth">
        <aside class="auth-side">
            <a href="{{ route('home') }}" class="logo" aria-label="Feedee Forums home"><img src="{{ asset('assets/images/logo.png') }}" alt="Feedee Forums" width="160" height="40"></a>
            <div>
                @yield('side')
            </div>
            <p class="copy">&copy; <span class="year">{{ date('Y') }}</span> Feedee Forums</p>
        </aside>

        <main class="auth-main">
            <div class="auth-card">
                <a href="{{ route('home') }}" class="auth-mobile-logo" aria-label="Feedee Forums home"><img src="{{ asset('assets/images/logo.png') }}" alt="Feedee Forums" width="160" height="42"></a>
                <a href="{{ route('home') }}" class="back-home"><i class="bi bi-arrow-left"></i>Back to feed</a>

                @yield('content')
            </div>
        </main>
    </div>

@include('partials.support-fab')
@include('partials.scripts')
</body>
</html>
