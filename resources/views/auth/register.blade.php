@extends('layouts.auth')

@section('title', 'Sign Up - Feedee Forums')
@section('description', 'Create a free Feedee Forums account to post photos and stories and connect with other feedees and feeders.')

@push('meta')
    <link rel="canonical" href="{{ route('register') }}">
@endpush

@section('side')
    <span class="eyebrow"><i class="bi bi-stars"></i>Join the community</span>
    <h2>Where feedees and stuffers actually belong</h2>
    <p class="auth-support">Share progress, recipes, stories, and connect with people who get it.</p>
    <ul class="auth-points">
        <li><i class="bi bi-chat-heart"></i>Share stuffing progress, recipes &amp; real stories</li>
        <li><i class="bi bi-lock"></i>Your email stays completely private</li>
        <li><i class="bi bi-hourglass-split"></i>Nothing lingers &mdash; posts auto-delete after 30 days</li>
    </ul>
@endsection

@section('content')
    <h1>Create your account</h1>
    <p class="lead-sm">It's free and takes less than a minute.</p>

    <form action="{{ route('register.store') }}" method="post">
        @csrf
        <div class="mb-3">
            <label class="form-label" for="username">Username</label>
            <div class="input-icon">
                <i class="bi bi-at"></i>
                <input type="text" @class(['form-control', 'has-error' => $errors->has('username')]) id="username" name="username" value="{{ old('username') }}" autocomplete="username" required minlength="3" maxlength="20" pattern="[A-Za-z0-9_]+" placeholder="maya_bakes" @error('username') aria-invalid="true" aria-describedby="usernameError" @enderror>
            </div>
            @error('username')
                <p class="form-error" id="usernameError" role="alert">{{ $message }}</p>
            @else
                <p class="form-hint">3&ndash;20 characters. Letters, numbers and underscores only.</p>
            @enderror
        </div>

        <div class="mb-3">
            <label class="form-label" for="email">Email address</label>
            <div class="input-icon">
                <i class="bi bi-envelope"></i>
                <input type="email" @class(['form-control', 'has-error' => $errors->has('email')]) id="email" name="email" value="{{ old('email') }}" autocomplete="email" required maxlength="255" placeholder="you@example.com" @error('email') aria-invalid="true" aria-describedby="emailError" @enderror>
            </div>
            @error('email')
                <p class="form-error" id="emailError" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-3">
            <label class="form-label" for="password">Password</label>
            <div class="input-icon">
                <i class="bi bi-lock"></i>
                <input type="password" @class(['form-control', 'has-error' => $errors->has('password')]) id="password" name="password" autocomplete="new-password" required minlength="8" placeholder="At least 8 characters" data-strength="#strength" @error('password') aria-invalid="true" aria-describedby="passwordError" @enderror>
                <button type="button" class="icon-btn toggle-pass" aria-label="Show password"><i class="bi bi-eye"></i></button>
            </div>
            <div class="strength" id="strength" data-level="0" aria-hidden="true"><span></span><span></span><span></span><span></span></div>
            <div class="strength-text" aria-live="polite"></div>
            @error('password')
                <p class="form-error" id="passwordError" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-3">
            <label class="form-label" for="password_confirmation">Confirm password</label>
            <div class="input-icon">
                <i class="bi bi-lock"></i>
                <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required placeholder="Type it again" data-match="#password">
                <button type="button" class="icon-btn toggle-pass" aria-label="Show password"><i class="bi bi-eye"></i></button>
            </div>
        </div>

        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" id="terms" name="terms" value="1" required @checked(old('terms'))>
            <label class="form-check-label" for="terms">I'm 18 or older and agree to the <a href="{{ route('rules') }}">community rules</a>.</label>
            @error('terms')
                <p class="form-error" role="alert">{{ $message }}</p>
            @enderror
        </div>

        @include('partials.turnstile')

        <button type="submit" class="btn-fill">Create account</button>
    </form>

    <p class="auth-switch">Already have an account? <a href="{{ route('login') }}">Log in</a></p>
@endsection
