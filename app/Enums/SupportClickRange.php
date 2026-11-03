<?php

namespace App\Enums;

use Illuminate\Support\Carbon;

enum SupportClickRange: string
{
    case Today = 'today';
    case Yesterday = 'yesterday';
    case Last7Days = 'last_7_days';
    case Last30Days = 'last_30_days';
    case ThisMonth = 'this_month';
    case LastMonth = 'last_month';
    case Custom = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::Today => 'Today',
            self::Yesterday => 'Yesterday',
            self::Last7Days => 'Last 7 days',
            self::Last30Days => 'Last 30 days',
            self::ThisMonth => 'This month',
            self::LastMonth => 'Last month',
            self::Custom => 'Custom range',
        };
    }

    /**
     * First and last moment of the range. Most presets end today; custom ranges use the given dates.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public function period(?Carbon $from = null, ?Carbon $to = null): array
    {
        $today = now()->startOfDay();

        [$start, $end] = match ($this) {
            self::Today => [$today->copy(), $today->copy()],
            self::Yesterday => [$today->copy()->subDay(), $today->copy()->subDay()],
            self::Last7Days => [$today->copy()->subDays(6), $today->copy()],
            self::Last30Days => [$today->copy()->subDays(29), $today->copy()],
            self::ThisMonth => [$today->copy()->startOfMonth(), $today->copy()],
            self::LastMonth => [$today->copy()->subMonthNoOverflow()->startOfMonth(), $today->copy()->subMonthNoOverflow()->endOfMonth()],
            self::Custom => [($from ?? $today)->copy(), ($to ?? $today)->copy()],
        };

        return [$start->startOfDay(), $end->endOfDay()];
    }
}
