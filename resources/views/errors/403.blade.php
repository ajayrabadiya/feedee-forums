@extends('layouts.app')

@section('title', 'Access denied - Feedee Forums')

@push('meta')
    <meta name="robots" content="noindex, nofollow">
@endpush

@section('content')
    @php
        $homeUrl = route('home');
        $previousUrl = url()->previous();
        $isSafeBackUrl = parse_url($previousUrl, PHP_URL_HOST) === request()->getHost()
            && $previousUrl !== request()->fullUrl()
            && $previousUrl !== $homeUrl;
    @endphp

    <main id="main" class="container error-page" aria-labelledby="errorTitle">
        <div class="error-code" aria-hidden="true">403</div>
        <h1 id="errorTitle">Access Denied</h1>
        <p>You don't have permission to access this page.</p>
        <div class="d-flex flex-wrap gap-2 justify-content-center">
            <a href="{{ $homeUrl }}" class="btn-fill"><i class="bi bi-house-door"></i>Go to Homepage</a>
            @if ($isSafeBackUrl)
                <a href="{{ $previousUrl }}" class="btn-soft"><i class="bi bi-arrow-left"></i>Go Back</a>
            @endif
            @guest
                <a href="{{ route('login', ['redirect' => request()->getRequestUri()]) }}" class="btn-soft"><i class="bi bi-box-arrow-in-right"></i>Log In</a>
            @endguest
        </div>
    </main>
@endsection

@section('footer')
    <footer class="site-footer">
        <div class="container">
            @include('partials.footer-bottom')
        </div>
    </footer>
@endsection
