@extends('layouts.app')

@section('title', 'Your profile - Feedee Forums')

@push('meta')
    <meta name="robots" content="noindex, nofollow">
@endpush

@section('content')
    <main id="main" class="container page">
        <ol class="crumbs light" aria-label="Breadcrumb">
            <li><a href="{{ route('home') }}">Feed</a></li>
            <li><a href="{{ $user->dashboardUrl() }}">Dashboard</a></li>
            <li aria-current="page">Profile</li>
        </ol>

        <div class="row g-4">
            <div class="col-lg-8">
                <form class="panel-card" method="post" action="{{ route('profile.update') }}">
                    @csrf
                    @method('patch')
                    <h1 class="section-title">Your profile</h1>
                    <p class="section-sub">Update how your name appears and the email you use to log in.</p>

                    <div class="mb-3">
                        <label class="form-label" for="username">Username</label>
                        <input type="text" class="form-control" id="username" value="{{ $user->username }}" disabled>
                        <p class="form-hint">Usernames can't be changed.</p>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="name">Display name</label>
                        <input type="text" @class(['form-control', 'has-error' => $errors->has('name')]) id="name" name="name" value="{{ old('name', $user->name) }}" maxlength="50" required autocomplete="nickname">
                        @error('name')
                            <p class="form-error" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="form-label" for="email">Email address</label>
                        <input type="email" @class(['form-control', 'has-error' => $errors->has('email')]) id="email" name="email" value="{{ old('email', $user->email) }}" maxlength="255" required autocomplete="email">
                        @error('email')
                            <p class="form-error" role="alert">{{ $message }}</p>
                        @else
                            <p class="form-hint">Never shown publicly.</p>
                        @enderror
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-fill"><i class="bi bi-check-lg"></i>Save changes</button>
                    </div>
                </form>

                <form class="panel-card" method="post" action="{{ route('profile.password') }}">
                    @csrf
                    @method('put')
                    <h2 class="form-section-title">Change password</h2>
                    <p class="form-section-sub">Use at least 8 characters. You'll stay logged in on this device.</p>

                    <div class="mb-3">
                        <label class="form-label" for="current_password">Current password</label>
                        <div class="input-icon">
                            <i class="bi bi-lock"></i>
                            <input type="password" @class(['form-control', 'has-error' => $errors->updatePassword->has('current_password')]) id="current_password" name="current_password" autocomplete="current-password" required>
                            <button type="button" class="icon-btn toggle-pass" aria-label="Show password"><i class="bi bi-eye"></i></button>
                        </div>
                        @error('current_password', 'updatePassword')
                            <p class="form-error" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="new_password">New password</label>
                        <div class="input-icon">
                            <i class="bi bi-lock"></i>
                            <input type="password" @class(['form-control', 'has-error' => $errors->updatePassword->has('password')]) id="new_password" name="password" autocomplete="new-password" required minlength="8">
                            <button type="button" class="icon-btn toggle-pass" aria-label="Show password"><i class="bi bi-eye"></i></button>
                        </div>
                        @error('password', 'updatePassword')
                            <p class="form-error" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="form-label" for="new_password_confirmation">Confirm new password</label>
                        <div class="input-icon">
                            <i class="bi bi-lock"></i>
                            <input type="password" class="form-control" id="new_password_confirmation" name="password_confirmation" autocomplete="new-password" required data-match="#new_password">
                            <button type="button" class="icon-btn toggle-pass" aria-label="Show password"><i class="bi bi-eye"></i></button>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-fill"><i class="bi bi-shield-lock"></i>Update password</button>
                    </div>
                </form>
            </div>

            <aside class="col-lg-4">
                <div class="sticky-lg">
                    <div class="box">
                        <div class="box-head"><i class="bi bi-person-circle"></i>Account</div>
                        <div class="box-body">
                            <dl class="account-details">
                                <dt>Account type</dt>
                                <dd>{{ $user->role->label() }}</dd>
                                <dt>Member since</dt>
                                <dd>{{ $user->created_at->format('j M Y') }}</dd>
                            </dl>
                            <form method="post" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="btn-soft danger w-100"><i class="bi bi-box-arrow-right"></i>Log out</button>
                            </form>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </main>
@endsection
