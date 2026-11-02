<?php

namespace App\Enums;

use Illuminate\Support\Carbon;

enum SupportClickRange: string
{
    case Today = 'today';
    case Last7Days = 'last_7_days';
    case Last30Days = 'last_30_days';
    case ThisMonth = 'this_month';
    case Custom = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::Today => 'Today',
            self::Last7Days => 'Last 7 days',
            self::Last30Days => 'Last 30 days',
            self::ThisMonth => 'This month',
            self::Custom => 'Custom range',
        };
    }

    /**
     * First and last moment of the range. Preset ranges end today; custom ranges use the given dates.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public function period(?Carbon $from = null, ?Carbon $to = null): array
    {
        $today = now()->startOfDay();

        $start = match ($this) {
            self::Today => $today->copy(),
            self::Last7Days => $today->copy()->subDays(6),
            self::Last30Days => $today->copy()->subDays(29),
            self::ThisMonth => $today->copy()->startOfMonth(),
            self::Custom => ($from ?? $today)->copy()->startOfDay(),
        };

        $end = $this === self::Custom ? ($to ?? $today)->copy() : $today->copy();

        return [$start, $end->endOfDay()];
    }
}
