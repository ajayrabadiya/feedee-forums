<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * Checks a Cloudflare Turnstile token with Cloudflare's siteverify API.
 */
class Turnstile implements ValidationRule
{
    /**
     * Name of the form field the Turnstile widget fills in.
     */
    public const FIELD = 'cf-turnstile-response';

    private const VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    public function __construct(private ?string $ip = null) {}

    /**
     * The check only runs once both keys are configured.
     */
    public static function enabled(): bool
    {
        return filled(config('services.turnstile.site_key')) && filled(config('services.turnstile.secret_key'));
    }

    /**
     * Validation rules for the Turnstile field, or none while Turnstile is not configured.
     *
     * @return array<string, array<mixed>>
     */
    public static function rules(?string $ip): array
    {
        return self::enabled() ? [self::FIELD => ['required', 'string', 'max:2048', new self($ip)]] : [];
    }

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        try {
            $response = Http::asForm()->timeout(10)->post(self::VERIFY_URL, array_filter([
                'secret' => config('services.turnstile.secret_key'),
                'response' => $value,
                'remoteip' => $this->ip,
            ]));

            $passed = $response->successful() && $response->json('success') === true;
        } catch (ConnectionException $exception) {
            report($exception);
            $passed = false;
        }

        if (! $passed) {
            $fail('The security check failed. Please try again.');
        }
    }
}
