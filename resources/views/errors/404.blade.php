@extends('layouts.app')

@section('title', 'Page not found - Feedee Forums')

@push('meta')
    <meta name="robots" content="noindex">
@endpush

@section('content')
    <main id="main" class="container error-page">
        <div class="error-code">404</div>
        <h1>This post has floated away</h1>
        <p>The page you're looking for doesn't exist. Posts on Feedee Forums are deleted after 30 days, so it may have expired.</p>
        <div class="d-flex flex-wrap gap-2 justify-content-center">
            <a href="{{ route('home') }}" class="btn-fill"><i class="bi bi-house-door"></i>Back to the feed</a>
            <a href="{{ route('contact') }}" class="btn-soft">Contact us</a>
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
