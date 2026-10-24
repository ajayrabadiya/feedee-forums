@extends('layouts.auth')

@section('title', 'Sign Up - Feedee Forums')
@section('description', 'Create a free Feedee Forums account and start sharing stories, recipes and moments with the community.')

@push('meta')
    <link rel="canonical" href="{{ route('register') }}">
@endpush

@section('side')
    <span class="eyebrow"><i class="bi bi-stars"></i>Join the community</span>
    <h2>A kinder corner of the internet starts here.</h2>
    <ul class="auth-points">
        <li><i class="bi bi-chat-heart"></i>Share recipes, art, meetups and stories</li>
        <li><i class="bi bi-lock"></i>Your email is never shown publicly</li>
        <li><i class="bi bi-hourglass-split"></i>Nothing lingers: posts are deleted after 30 days</li>
    </ul>
@endsection

@section('content')
    <h1>Create your account</h1>
    <p class="lead-sm">It's free and takes less than a minute.</p>

    <form action="{{ route('register') }}" method="post" data-demo="Account created! Check your inbox to verify your email.">
        @csrf
        <div class="mb-3">
            <label class="form-label" for="username">Username</label>
            <div class="input-icon">
                <i class="bi bi-at"></i>
                <input type="text" class="form-control" id="username" name="username" value="{{ old('username') }}" autocomplete="username" required minlength="3" maxlength="20" pattern="[A-Za-z0-9_]+" placeholder="maya_bakes">
            </div>
            <p class="form-hint">3&ndash;20 characters. Letters, numbers and underscores only.</p>
        </div>

        <div class="mb-3">
            <label class="form-label" for="email">Email address</label>
            <div class="input-icon">
                <i class="bi bi-envelope"></i>
                <input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}" autocomplete="email" required placeholder="you@example.com">
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label" for="password">Password</label>
            <div class="input-icon">
                <i class="bi bi-lock"></i>
                <input type="password" class="form-control" id="password" name="password" autocomplete="new-password" required minlength="8" placeholder="At least 8 characters" data-strength="#strength">
                <button type="button" class="icon-btn toggle-pass" aria-label="Show password"><i class="bi bi-eye"></i></button>
            </div>
            <div class="strength" id="strength" data-level="0" aria-hidden="true"><span></span><span></span><span></span><span></span></div>
            <div class="strength-text" aria-live="polite"></div>
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
            <input class="form-check-input" type="checkbox" id="terms" name="terms" required>
            <label class="form-check-label" for="terms">I'm 18 or older and agree to the <a href="{{ route('rules') }}">community rules</a>.</label>
        </div>

        <div class="turnstile mb-4" aria-label="Security check">
            <i class="bi bi-cloud-check-fill"></i>
            <div><strong>Verifying you're human</strong>Protected by Cloudflare Turnstile</div>
        </div>

        <button type="submit" class="btn-fill">Create account</button>
    </form>

    <p class="auth-switch">Already have an account? <a href="{{ route('login') }}">Log in</a></p>
@endsection
