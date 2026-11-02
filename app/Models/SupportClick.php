<?php

namespace App\Models;

use App\Enums\SupportClickRange;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Database\Factories\SupportClickFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

#[Fillable(['page_path'])]
class SupportClick extends Model
{
    /** @use HasFactory<SupportClickFactory> */
    use HasFactory;

    public const CREATED_AT = 'clicked_at';

    public const UPDATED_AT = null;

    /**
     * Longest custom date range, in days, the analytics endpoint accepts.
     */
    public const MAX_RANGE_DAYS = 366;

    /**
     * Ways the analytics chart can bucket clicks.
     *
     * @var list<string>
     */
    public const GROUPS = ['day', 'week'];

    /**
     * Seconds between live refreshes of the dashboard analytics.
     */
    public const POLL_SECONDS = 15;

    /**
     * Number of pages listed in "Where visitors click Support".
     */
    private const TOP_PAGES = 5;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'clicked_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Everything the Support analytics section shows for one date range.
     *
     * @return array{
     *     stats: array{total: int, today: int, last7Days: int, thisMonth: int},
     *     range: array{key: string, label: string, from: string, to: string, clicks: int},
     *     group: string,
     *     chart: list<array{label: string, title: string, clicks: int}>,
     *     topPages: list<array{path: string, clicks: int}>,
     *     updatedAt: string
     * }
     */
    public static function analytics(SupportClickRange $range, string $group = 'day', ?Carbon $from = null, ?Carbon $to = null): array
    {
        [$start, $end] = $range->period($from, $to);
        $daily = static::dailyCounts($start, $end);

        return [
            'stats' => static::summary(),
            'range' => [
                'key' => $range->value,
                'label' => $range->label(),
                'from' => $start->toDateString(),
                'to' => $end->toDateString(),
                'clicks' => array_sum($daily),
            ],
            'group' => $group,
            'chart' => $group === 'week' ? static::weeklyChart($start, $end, $daily) : static::dailyChart($start, $end, $daily),
            'topPages' => static::topPages($start, $end),
            'updatedAt' => now()->toIso8601String(),
        ];
    }

    /**
     * All-time, today, last 7 days (including today) and this month's clicks in a single query.
     *
     * @return array{total: int, today: int, last7Days: int, thisMonth: int}
     */
    public static function summary(): array
    {
        $today = now()->startOfDay();

        $counts = static::query()
            ->toBase()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN clicked_at >= ? THEN 1 ELSE 0 END) as today', [$today])
            ->selectRaw('SUM(CASE WHEN clicked_at >= ? THEN 1 ELSE 0 END) as last_7_days', [$today->copy()->subDays(6)])
            ->selectRaw('SUM(CASE WHEN clicked_at >= ? THEN 1 ELSE 0 END) as this_month', [$today->copy()->startOfMonth()])
            ->first();

        return [
            'total' => (int) $counts->total,
            'today' => (int) $counts->today,
            'last7Days' => (int) $counts->last_7_days,
            'thisMonth' => (int) $counts->this_month,
        ];
    }

    /**
     * Clicks per calendar day inside the window, keyed by Y-m-d. Days without clicks are missing.
     *
     * @return array<string, int>
     */
    private static function dailyCounts(Carbon $start, Carbon $end): array
    {
        return static::query()
            ->toBase()
            ->whereBetween('clicked_at', [$start, $end])
            ->selectRaw('DATE(clicked_at) as day, COUNT(*) as clicks')
            ->groupByRaw('DATE(clicked_at)')
            ->pluck('clicks', 'day')
            ->map(fn ($clicks): int => (int) $clicks)
            ->all();
    }

    /**
     * One bar per day of the window.
     *
     * @param  array<string, int>  $daily
     * @return list<array{label: string, title: string, clicks: int}>
     */
    private static function dailyChart(Carbon $start, Carbon $end, array $daily): array
    {
        $isShortRange = $start->diffInDays($end) < 7;
        $bars = [];

        foreach (CarbonPeriod::create($start, '1 day', $end) as $date) {
            $bars[] = [
                'label' => $date->isToday() ? 'Today' : $date->format($isShortRange ? 'D' : 'M j'),
                'title' => $date->format('D, M j, Y'),
                'clicks' => $daily[$date->toDateString()] ?? 0,
            ];
        }

        return $bars;
    }

    /**
     * One bar per Monday-to-Sunday week, clipped to the window at both ends.
     *
     * @param  array<string, int>  $daily
     * @return list<array{label: string, title: string, clicks: int}>
     */
    private static function weeklyChart(Carbon $start, Carbon $end, array $daily): array
    {
        $weeks = [];

        foreach (CarbonPeriod::create($start, '1 day', $end) as $date) {
            $week = $date->copy()->startOfWeek(CarbonInterface::MONDAY)->toDateString();

            $weeks[$week] ??= ['first' => $date->copy(), 'clicks' => 0];
            $weeks[$week]['last'] = $date->copy();
            $weeks[$week]['clicks'] += $daily[$date->toDateString()] ?? 0;
        }

        return array_values(array_map(fn (array $week): array => [
            'label' => $week['first']->format('M j'),
            'title' => $week['first']->isSameDay($week['last'])
                ? $week['first']->format('D, M j, Y')
                : $week['first']->format('M j').' – '.$week['last']->format('M j, Y'),
            'clicks' => $week['clicks'],
        ], $weeks));
    }

    /**
     * Pages where visitors clicked Support most often in the window.
     *
     * @return list<array{path: string, clicks: int}>
     */
    private static function topPages(Carbon $start, Carbon $end): array
    {
        return static::query()
            ->toBase()
            ->whereBetween('clicked_at', [$start, $end])
            ->whereNotNull('page_path')
            ->selectRaw('page_path, COUNT(*) as clicks')
            ->groupBy('page_path')
            ->orderByDesc('clicks')
            ->orderBy('page_path')
            ->limit(self::TOP_PAGES)
            ->get()
            ->map(fn (object $row): array => ['path' => $row->page_path, 'clicks' => (int) $row->clicks])
            ->all();
    }
}
