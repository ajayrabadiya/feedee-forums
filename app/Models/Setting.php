<?php

namespace App\Models;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Throwable;

#[Fillable(['key', 'value'])]
class Setting extends Model
{
    /**
     * When on, new posts wait in the admin Pending tab until approved.
     */
    public const REQUIRE_POST_APPROVAL = 'posts.require_approval';

    /**
     * Values used until an administrator saves their own.
     *
     * @var array<string, string>
     */
    public const DEFAULTS = [
        'support.enabled' => '1',
        'support.youpay_url' => 'https://youpay.me/zaiyaxx',
        'support.youpay_note' => 'Gifts · worldwide',
        'support.cashapp_url' => 'https://cash.app/$zaiyaxx',
        'support.cashapp_note' => '$zaiyaxx · US only',
    ];

    /**
     * Setting keys whose values are stored encrypted.
     *
     * @var list<string>
     */
    public const ENCRYPTED = ['mail.password'];

    /**
     * Container key used to cache all settings for the current request.
     */
    private const CACHE_KEY = 'settings.values';

    /**
     * All stored settings, loaded once per request.
     *
     * @return array<string, string|null>
     */
    public static function values(): array
    {
        $app = app();

        if (! $app->bound(self::CACHE_KEY)) {
            $app->instance(self::CACHE_KEY, static::query()->pluck('value', 'key')->all());
        }

        return $app->make(self::CACHE_KEY);
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        $value = static::values()[$key] ?? null;

        if ($value !== null && in_array($key, self::ENCRYPTED, true)) {
            try {
                $value = Crypt::decryptString($value);
            } catch (DecryptException) {
                $value = null;
            }
        }

        return $value ?? $default ?? (self::DEFAULTS[$key] ?? null);
    }

    public static function set(string $key, ?string $value): void
    {
        if ($value !== null && in_array($key, self::ENCRYPTED, true)) {
            $value = Crypt::encryptString($value);
        }

        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        app()->forgetInstance(self::CACHE_KEY);
    }

    /**
     * @param  array<string, string|null>  $values
     */
    public static function setMany(array $values): void
    {
        foreach ($values as $key => $value) {
            static::set($key, $value);
        }
    }

    public static function requiresPostApproval(): bool
    {
        return static::get(self::REQUIRE_POST_APPROVAL, '1') === '1';
    }

    /**
     * Links shown in the floating Support button.
     *
     * @return array{enabled: bool, links: list<array{key: string, label: string, url: string, note: ?string, icon: string}>}
     */
    public static function supportButton(): array
    {
        $links = [];

        foreach (['youpay' => ['YouPay', 'bi-gift-fill'], 'cashapp' => ['Cash App', 'bi-currency-dollar']] as $key => [$label, $icon]) {
            $url = static::get("support.{$key}_url");

            if (filled($url)) {
                $links[] = ['key' => $key, 'label' => $label, 'url' => $url, 'note' => static::get("support.{$key}_note"), 'icon' => $icon];
            }
        }

        return [
            'enabled' => static::get('support.enabled') === '1' && $links !== [],
            'links' => $links,
        ];
    }

    /**
     * Point Laravel's mailer at the SMTP settings saved in the admin panel.
     * Without saved settings the values from .env are used unchanged.
     */
    public static function applyMailConfig(): void
    {
        try {
            $mailer = static::get('mail.mailer');
        } catch (Throwable) {
            return;
        }

        if (! in_array($mailer, ['log', 'smtp'], true)) {
            return;
        }

        $encryption = static::get('mail.encryption');

        config([
            'mail.default' => $mailer,
            'mail.mailers.smtp.host' => static::get('mail.host'),
            'mail.mailers.smtp.port' => (int) static::get('mail.port', '587'),
            'mail.mailers.smtp.scheme' => $encryption === 'ssl' ? 'smtps' : 'smtp',
            'mail.mailers.smtp.username' => static::get('mail.username'),
            'mail.mailers.smtp.password' => static::get('mail.password'),
            'mail.mailers.smtp.timeout' => 15,
            'mail.from.address' => static::get('mail.from_address', config('mail.from.address')),
            'mail.from.name' => static::get('mail.from_name', config('mail.from.name')),
        ]);

        if (app()->resolved('mail.manager')) {
            Mail::purge('smtp');
            Mail::purge('log');
        }
    }
}
