@extends('layouts.auth')

@section('title', 'Forgot Password - Feedee Forums')

@push('meta')
    <meta name="robots" content="noindex, nofollow">
@endpush

@section('side')
    <span class="eyebrow"><i class="bi bi-key"></i>Locked out?</span>
    <h2>We'll help you get back into your account.</h2>
    <ul class="auth-points">
        <li><i class="bi bi-envelope"></i>Enter the email you signed up with</li>
        <li><i class="bi bi-link-45deg"></i>Open the link we send you</li>
        <li><i class="bi bi-shield-lock"></i>Choose a new password</li>
    </ul>
@endsection

@section('content')
    <h1>Forgot password?</h1>
    <p class="lead-sm">Enter your email address and we'll send you a link to reset your password.</p>

    @if (session('resetLinkSent'))
        <div class="auth-notice mb-4" role="status">
            <i class="bi bi-envelope-check"></i>
            <div>
                <strong>Check your inbox</strong>
                If an account exists for {{ session('resetLinkSent') }}, we've sent a link to reset your password. It expires in {{ config('auth.passwords.'.config('auth.defaults.passwords').'.expire') }} minutes. Can't find it? Check your spam folder.
            </div>
        </div>
    @endif

    <form action="{{ route('password.email') }}" method="post">
        @csrf
        <div class="mb-3">
            <label class="form-label" for="email">Email address</label>
            <div class="input-icon">
                <i class="bi bi-envelope"></i>
                <input type="email" @class(['form-control', 'has-error' => $errors->has('email')]) id="email" name="email" value="{{ old('email', session('resetLinkSent')) }}" autocomplete="email" required maxlength="255" autofocus placeholder="you@example.com" @error('email') aria-invalid="true" aria-describedby="emailError" @enderror>
            </div>
            @error('email')
                <p class="form-error" id="emailError" role="alert">{{ $message }}</p>
            @enderror
        </div>

        @include('partials.turnstile')

        <button type="submit" class="btn-fill">{{ session('resetLinkSent') ? 'Send the link again' : 'Email me a reset link' }}</button>
    </form>

    <p class="auth-switch">Remembered it? <a href="{{ route('login') }}">Back to log in</a></p>
@endsection
