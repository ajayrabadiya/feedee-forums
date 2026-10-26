@extends('layouts.admin')

@section('title', 'Settings - Feedee Forums Admin')
@section('heading', 'Settings')
@section('subheading', 'Support button links and email sending')

@section('content')
    @php
        $smtpSelected = old('mail_mailer', $mail['mailer']) === 'smtp';
    @endphp

                <div class="row g-4">
                    <!-- Support button -->
                    <div class="col-xl-5">
                        <form class="panel settings-panel" method="post" action="{{ route('admin.settings.support') }}">
                            @csrf
                            @method('put')
                            <div class="panel-head">
                                <div>
                                    <h2><i class="bi bi-heart-fill text-danger me-1"></i>Support button</h2>
                                    <p>The floating "Support" button on every page of the site</p>
                                </div>
                            </div>
                            <div class="panel-body">
                                <div class="setting-row">
                                    <div>
                                        <label class="form-label mb-0" for="supportEnabled">Show the Support button</label>
                                        <p class="setting-hint">Hidden automatically if both links are empty.</p>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input type="hidden" name="support_enabled" value="0">
                                        <input class="form-check-input" type="checkbox" role="switch" id="supportEnabled" name="support_enabled" value="1" @checked(old('support_enabled', $support['enabled'] ? '1' : '0') === '1')>
                                    </div>
                                </div>

                                <fieldset class="settings-group">
                                    <legend><i class="bi bi-gift-fill"></i>YouPay</legend>
                                    <div class="mb-3">
                                        <label class="form-label" for="youpayUrl">Link</label>
                                        <input type="url" @class(['form-control', 'has-error' => $errors->has('youpay_url')]) id="youpayUrl" name="youpay_url" value="{{ old('youpay_url', $support['youpay_url']) }}" placeholder="https://youpay.me/yourname" maxlength="255">
                                        @error('youpay_url')<p class="form-error">{{ $message }}</p>@enderror
                                    </div>
                                    <div>
                                        <label class="form-label" for="youpayNote">Caption <span class="opt">(optional)</span></label>
                                        <input type="text" @class(['form-control', 'has-error' => $errors->has('youpay_note')]) id="youpayNote" name="youpay_note" value="{{ old('youpay_note', $support['youpay_note']) }}" placeholder="Gifts · worldwide" maxlength="60">
                                        @error('youpay_note')<p class="form-error">{{ $message }}</p>@enderror
                                    </div>
                                </fieldset>

                                <fieldset class="settings-group">
                                    <legend><i class="bi bi-currency-dollar"></i>Cash App</legend>
                                    <div class="mb-3">
                                        <label class="form-label" for="cashappUrl">Link</label>
                                        <input type="url" @class(['form-control', 'has-error' => $errors->has('cashapp_url')]) id="cashappUrl" name="cashapp_url" value="{{ old('cashapp_url', $support['cashapp_url']) }}" placeholder="https://cash.app/$yourname" maxlength="255">
                                        @error('cashapp_url')<p class="form-error">{{ $message }}</p>@enderror
                                    </div>
                                    <div>
                                        <label class="form-label" for="cashappNote">Caption <span class="opt">(optional)</span></label>
                                        <input type="text" @class(['form-control', 'has-error' => $errors->has('cashapp_note')]) id="cashappNote" name="cashapp_note" value="{{ old('cashapp_note', $support['cashapp_note']) }}" placeholder="$yourname · US only" maxlength="60">
                                        @error('cashapp_note')<p class="form-error">{{ $message }}</p>@enderror
                                    </div>
                                </fieldset>
                                <p class="setting-hint mb-0">Leave a link empty to hide that option from the button.</p>
                            </div>
                            <div class="panel-foot">
                                <a href="{{ route('home') }}" target="_blank" rel="noopener" class="link-more">Preview on the site <i class="bi bi-box-arrow-up-right"></i></a>
                                <button type="submit" class="btn-fill sm"><i class="bi bi-check-lg"></i>Save support button</button>
                            </div>
                        </form>
                    </div>

                    <!-- Email -->
                    <div class="col-xl-7">
                        <form class="panel settings-panel" method="post" action="{{ route('admin.settings.mail') }}" autocomplete="off">
                            @csrf
                            @method('put')
                            <div class="panel-head">
                                <div>
                                    <h2><i class="bi bi-envelope-at me-1"></i>Email sending</h2>
                                    <p>Used for contact-form alerts and rejection emails. {{ $mail['saved'] ? 'Saved here; overrides .env.' : 'Currently using the values from .env.' }}</p>
                                </div>
                            </div>
                            <div class="panel-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label" for="mailMailer">Send emails using</label>
                                        <select @class(['form-select', 'has-error' => $errors->has('mail_mailer')]) id="mailMailer" name="mail_mailer" data-smtp-toggle>
                                            <option value="log" @selected(! $smtpSelected)>Log only (don't send, write to log file)</option>
                                            <option value="smtp" @selected($smtpSelected)>SMTP server</option>
                                        </select>
                                        @error('mail_mailer')<p class="form-error">{{ $message }}</p>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="mailContactRecipient">Also send contact messages to <span class="opt">(optional)</span></label>
                                        <input type="email" @class(['form-control', 'has-error' => $errors->has('mail_contact_recipient')]) id="mailContactRecipient" name="mail_contact_recipient" value="{{ old('mail_contact_recipient', $mail['contact_recipient']) }}" placeholder="team@example.com" maxlength="255">
                                        @error('mail_contact_recipient')<p class="form-error">{{ $message }}</p>@enderror
                                        @unless ($errors->has('mail_contact_recipient'))<p class="setting-hint">Every admin account is always emailed too.</p>@endunless
                                    </div>

                                    <div class="col-12 smtp-fields" @if (! $smtpSelected) hidden @endif>
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label" for="mailHost">SMTP host</label>
                                                <input type="text" @class(['form-control', 'has-error' => $errors->has('mail_host')]) id="mailHost" name="mail_host" value="{{ old('mail_host', $mail['host']) }}" placeholder="smtp.gmail.com" maxlength="255">
                                                @error('mail_host')<p class="form-error">{{ $message }}</p>@enderror
                                            </div>
                                            <div class="col-6 col-md-3">
                                                <label class="form-label" for="mailPort">Port</label>
                                                <input type="number" @class(['form-control', 'has-error' => $errors->has('mail_port')]) id="mailPort" name="mail_port" value="{{ old('mail_port', $mail['port']) }}" min="1" max="65535" placeholder="587">
                                                @error('mail_port')<p class="form-error">{{ $message }}</p>@enderror
                                            </div>
                                            <div class="col-6 col-md-3">
                                                <label class="form-label" for="mailEncryption">Encryption</label>
                                                <select @class(['form-select', 'has-error' => $errors->has('mail_encryption')]) id="mailEncryption" name="mail_encryption">
                                                    @foreach (['tls' => 'TLS (587)', 'ssl' => 'SSL (465)', 'none' => 'None'] as $value => $label)
                                                        <option value="{{ $value }}" @selected(old('mail_encryption', $mail['encryption']) === $value)>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                                @error('mail_encryption')<p class="form-error">{{ $message }}</p>@enderror
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label" for="mailUsername">Username</label>
                                                <input type="text" @class(['form-control', 'has-error' => $errors->has('mail_username')]) id="mailUsername" name="mail_username" value="{{ old('mail_username', $mail['username']) }}" autocomplete="off" maxlength="255">
                                                @error('mail_username')<p class="form-error">{{ $message }}</p>@enderror
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label" for="mailPassword">Password</label>
                                                <input type="password" @class(['form-control', 'has-error' => $errors->has('mail_password')]) id="mailPassword" name="mail_password" autocomplete="new-password" maxlength="255" placeholder="{{ $mail['has_password'] ? '•••••••• (saved, leave blank to keep)' : 'App password' }}">
                                                @error('mail_password')<p class="form-error">{{ $message }}</p>@enderror
                                                @unless ($errors->has('mail_password'))<p class="setting-hint">Stored encrypted. For Gmail use an app password.</p>@endunless
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label" for="mailFromAddress">"From" email address</label>
                                        <input type="email" @class(['form-control', 'has-error' => $errors->has('mail_from_address')]) id="mailFromAddress" name="mail_from_address" value="{{ old('mail_from_address', $mail['from_address']) }}" required maxlength="255">
                                        @error('mail_from_address')<p class="form-error">{{ $message }}</p>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="mailFromName">"From" name</label>
                                        <input type="text" @class(['form-control', 'has-error' => $errors->has('mail_from_name')]) id="mailFromName" name="mail_from_name" value="{{ old('mail_from_name', $mail['from_name']) }}" required maxlength="100">
                                        @error('mail_from_name')<p class="form-error">{{ $message }}</p>@enderror
                                    </div>
                                </div>
                            </div>
                            <div class="panel-foot">
                                <span class="setting-hint">Save first, then send a test email below.</span>
                                <button type="submit" class="btn-fill sm"><i class="bi bi-check-lg"></i>Save email settings</button>
                            </div>
                        </form>

                        <form class="panel settings-panel mt-4" method="post" action="{{ route('admin.settings.mail.test') }}">
                            @csrf
                            <div class="panel-body">
                                <label class="form-label" for="testRecipient">Send a test email to</label>
                                <div class="test-mail-row">
                                    <input type="email" @class(['form-control', 'has-error' => $errors->mailTest->has('test_recipient')]) id="testRecipient" name="test_recipient" value="{{ old('test_recipient', $testRecipient) }}" required maxlength="255">
                                    <button type="submit" class="btn-soft sm"><i class="bi bi-send"></i>Send test</button>
                                </div>
                                @error('test_recipient', 'mailTest')<p class="form-error">{{ $message }}</p>@enderror
                            </div>
                        </form>
                    </div>
                </div>
@endsection
