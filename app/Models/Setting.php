<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['key', 'value'])]
class Setting extends Model
{
    /**
     * When on, new posts wait in the admin Pending tab until approved.
     */
    public const REQUIRE_POST_APPROVAL = 'posts.require_approval';

    public static function get(string $key, ?string $default = null): ?string
    {
        return static::query()->where('key', $key)->value('value') ?? $default;
    }

    public static function set(string $key, ?string $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
    }

    public static function requiresPostApproval(): bool
    {
        return static::get(self::REQUIRE_POST_APPROVAL, '1') === '1';
    }
}
