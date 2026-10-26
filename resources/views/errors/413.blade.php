@extends('layouts.app')

@section('title', 'Upload too large - Feedee Forums')

@push('meta')
    <meta name="robots" content="noindex, nofollow">
@endpush

@section('content')
    <main id="main" class="container error-page" aria-labelledby="errorTitle">
        <div class="error-code" aria-hidden="true">413</div>
        <h1 id="errorTitle">That upload was too large</h1>
        <p>Together, the files you attached were bigger than we can accept in one post. Try fewer photos or a shorter video, then post again.</p>
        <div class="d-flex flex-wrap gap-2 justify-content-center">
            <a href="{{ route('posts.create') }}" class="btn-fill"><i class="bi bi-arrow-repeat"></i>Try again</a>
            <a href="{{ route('home') }}" class="btn-soft"><i class="bi bi-house-door"></i>Go to Homepage</a>
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
