@extends('layouts.auth')

@section('title', 'Log In - Feedee Forums')
@section('description', 'Log in to Feedee Forums to share posts with the community.')

@push('meta')
    <meta name="robots" content="noindex, follow">
    <link rel="canonical" href="{{ route('login') }}">
@endpush

@section('side')
    <span class="eyebrow"><i class="bi bi-heart-fill"></i>Good to see you again</span>
    <h2>Pick up where you left off with the community.</h2>
    <ul class="auth-points">
        <li><i class="bi bi-images"></i>Share photos and short videos in seconds</li>
        <li><i class="bi bi-shield-check"></i>Real moderators look after the community</li>
        <li><i class="bi bi-hourglass-split"></i>Posts disappear for good after 30 days</li>
    </ul>
@endsection

@section('content')
    <h1>Log in</h1>
    <p class="lead-sm">Welcome back! Enter your details to continue.</p>

    <form action="{{ route('login.store') }}" method="post">
        @csrf
        <div class="mb-3">
            <label class="form-label" for="email">Email or username</label>
            <div class="input-icon">
                <i class="bi bi-person"></i>
                <input type="text" @class(['form-control', 'has-error' => $errors->has('login')]) id="email" name="login" value="{{ old('login') }}" autocomplete="username" required autofocus placeholder="you@example.com" @error('login') aria-invalid="true" aria-describedby="loginError" @enderror>
            </div>
            @error('login')
                <p class="form-error" id="loginError" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-3">
            <div class="label-row">
                <label class="form-label" for="password">Password</label>
                <a href="#" class="link small">Forgot password?</a>
            </div>
            <div class="input-icon">
                <i class="bi bi-lock"></i>
                <input type="password" @class(['form-control', 'has-error' => $errors->has('password')]) id="password" name="password" autocomplete="current-password" required placeholder="Enter your password" @error('password') aria-invalid="true" aria-describedby="passwordError" @enderror>
                <button type="button" class="icon-btn toggle-pass" aria-label="Show password"><i class="bi bi-eye"></i></button>
            </div>
            @error('password')
                <p class="form-error" id="passwordError" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" id="remember" name="remember" value="1" @checked(old('remember'))>
            <label class="form-check-label" for="remember">Keep me logged in</label>
        </div>

        <div class="turnstile mb-4" aria-label="Security check">
            <i class="bi bi-cloud-check-fill"></i>
            <div><strong>Verifying you're human</strong>Protected by Cloudflare Turnstile</div>
        </div>

        <button type="submit" class="btn-fill">Log in</button>
    </form>

    <p class="auth-switch">New to Feedee Forums? <a href="{{ route('register') }}">Create an account</a></p>
@endsection
