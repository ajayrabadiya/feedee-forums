<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * One IP range of the local country database imported by `php artisan app:import-geoip`.
 */
#[Fillable(['ip_from', 'ip_to', 'country_code'])]
class IpCountryRange extends Model
{
    public $timestamps = false;

    public $incrementing = false;

    protected $primaryKey = 'ip_from';

    protected $keyType = 'string';

    /**
     * Approximate ISO country code of the visitor, or null when it cannot be determined. VPNs and
     * proxies report the country of their exit server. Never throws: a failed lookup is "unknown".
     */
    public static function countryFor(Request $request): ?string
    {
        try {
            $header = config('analytics.geoip.header');

            if (filled($header)) {
                $code = strtoupper(trim((string) $request->header($header)));

                if (static::isKnownCountry($code)) {
                    return $code;
                }
            }

            $ip = (string) $request->ip();

            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
                return null;
            }

            $code = Cache::remember(
                'geoip:'.hash_hmac('sha256', $ip, (string) config('app.key')),
                config('analytics.geoip.cache_seconds'),
                fn (): string => static::lookup($ip) ?? '',
            );

            return $code !== '' ? $code : null;
        } catch (Throwable $exception) {
            report($exception);

            return null;
        }
    }

    /**
     * Country code of the imported range that contains the address.
     */
    public static function lookup(string $ip): ?string
    {
        $address = static::toHex($ip);

        if ($address === null) {
            return null;
        }

        $range = static::query()
            ->where('ip_from', '<=', $address)
            ->orderByDesc('ip_from')
            ->first(['ip_to', 'country_code']);

        return $range !== null && strcmp($range->ip_to, $address) >= 0 && static::isKnownCountry($range->country_code)
            ? $range->country_code
            : null;
    }

    /**
     * The address as 32 hex characters of its 16-byte IPv6 form (IPv4 becomes ::ffff:a.b.c.d).
     */
    public static function toHex(string $ip): ?string
    {
        $packed = filter_var($ip, FILTER_VALIDATE_IP) !== false ? inet_pton($ip) : false;

        if ($packed === false) {
            return null;
        }

        if (strlen($packed) === 4) {
            $packed = str_repeat("\0", 10)."\xff\xff".$packed;
        }

        return bin2hex($packed);
    }

    public static function isKnownCountry(?string $code): bool
    {
        return $code !== null && array_key_exists($code, config('countries'));
    }

    /**
     * English name for a country code, or "Unknown".
     */
    public static function countryName(?string $code): string
    {
        return static::isKnownCountry($code) ? config('countries')[$code] : 'Unknown';
    }
}
