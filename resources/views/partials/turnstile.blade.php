@if (\App\Rules\Turnstile::enabled())
    @once
        @push('head')
            <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
        @endpush
    @endonce
    <div class="turnstile-field mb-4">
        <div class="cf-turnstile" data-sitekey="{{ config('services.turnstile.site_key') }}" data-theme="light" data-size="flexible" data-response-field-name="{{ \App\Rules\Turnstile::FIELD }}"></div>
        @error(\App\Rules\Turnstile::FIELD)
            <p class="form-error" role="alert">{{ $message }}</p>
        @enderror
    </div>
@endif
