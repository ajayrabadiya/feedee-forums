<?php

namespace App\Enums;

use App\Models\Setting;

/**
 * Payment platforms offered by the floating Support button. To add a platform, add a case here,
 * give it "support.{value}_url" / "support.{value}_note" settings and a field on the settings page.
 */
enum SupportPlatform: string
{
    case YouPay = 'youpay';
    case CashApp = 'cashapp';

    public function label(): string
    {
        return match ($this) {
            self::YouPay => 'YouPay',
            self::CashApp => 'Cash App',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::YouPay => 'bi-gift-fill',
            self::CashApp => 'bi-currency-dollar',
        };
    }

    /**
     * Brand colour used for this platform in the admin charts.
     */
    public function color(): string
    {
        return match ($this) {
            self::YouPay => '#6a4cf5',
            self::CashApp => '#00b33e',
        };
    }

    public function urlSettingKey(): string
    {
        return "support.{$this->value}_url";
    }

    public function noteSettingKey(): string
    {
        return "support.{$this->value}_note";
    }

    /**
     * The administrator-configured link visitors are sent to, or null when it is missing or not https.
     */
    public function url(): ?string
    {
        $url = Setting::get($this->urlSettingKey());

        if (! is_string($url) || filter_var($url, FILTER_VALIDATE_URL) === false) {
            return null;
        }

        return strtolower((string) parse_url($url, PHP_URL_SCHEME)) === 'https' ? $url : null;
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
