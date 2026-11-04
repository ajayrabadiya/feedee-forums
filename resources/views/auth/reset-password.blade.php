@extends('layouts.auth')

@section('title', 'Reset Password - Feedee Forums')

@push('meta')
    <meta name="robots" content="noindex, nofollow">
    <meta name="referrer" content="no-referrer">
@endpush

@section('side')
    <span class="eyebrow"><i class="bi bi-shield-lock"></i>Almost there</span>
    <h2>Choose a new password for your account.</h2>
    <ul class="auth-points">
        <li><i class="bi bi-key"></i>Use at least 8 characters</li>
        <li><i class="bi bi-shuffle"></i>Mix letters, numbers and symbols</li>
        <li><i class="bi bi-box-arrow-right"></i>You'll be signed out on your other devices</li>
    </ul>
@endsection

@section('content')
    <h1>Reset password</h1>
    <p class="lead-sm">Enter your email address and choose a new password.</p>

    <form action="{{ route('password.store') }}" method="post">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div class="mb-3">
            <label class="form-label" for="email">Email address</label>
            <div class="input-icon">
                <i class="bi bi-envelope"></i>
                <input type="email" @class(['form-control', 'has-error' => $errors->has('email') || $errors->has('token')]) id="email" name="email" value="{{ old('email', $email) }}" autocomplete="email" required maxlength="255" placeholder="you@example.com" @error('email') aria-invalid="true" aria-describedby="emailError" @enderror>
            </div>
            @error('email')
                <p class="form-error" id="emailError" role="alert">{{ $message }} <a href="{{ route('password.request') }}">Request a new link</a></p>
            @enderror
            @error('token')
                <p class="form-error" role="alert">{{ $message }} <a href="{{ route('password.request') }}">Request a new link</a></p>
            @enderror
        </div>

        <div class="mb-3">
            <label class="form-label" for="password">New password</label>
            <div class="input-icon">
                <i class="bi bi-lock"></i>
                <input type="password" @class(['form-control', 'has-error' => $errors->has('password')]) id="password" name="password" autocomplete="new-password" required minlength="8" autofocus placeholder="At least 8 characters" data-strength="#strength" @error('password') aria-invalid="true" aria-describedby="passwordError" @enderror>
                <button type="button" class="icon-btn toggle-pass" aria-label="Show password"><i class="bi bi-eye"></i></button>
            </div>
            <div class="strength" id="strength" data-level="0" aria-hidden="true"><span></span><span></span><span></span><span></span></div>
            <div class="strength-text" aria-live="polite"></div>
            @error('password')
                <p class="form-error" id="passwordError" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-4">
            <label class="form-label" for="password_confirmation">Confirm new password</label>
            <div class="input-icon">
                <i class="bi bi-lock"></i>
                <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required placeholder="Type it again" data-match="#password">
                <button type="button" class="icon-btn toggle-pass" aria-label="Show password"><i class="bi bi-eye"></i></button>
            </div>
        </div>

        <button type="submit" class="btn-fill">Reset password</button>
    </form>

    <p class="auth-switch">Remembered it? <a href="{{ route('login') }}">Back to log in</a></p>
@endsection
