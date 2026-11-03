<?php

namespace App\Models;

use App\Enums\SupportClickFilter;
use App\Enums\SupportClickRange;
use App\Enums\SupportPlatform;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Database\Factories\SupportClickFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * A click on the floating Support button (no platform) or on one of its payment links (with a platform).
 * Clicks record interest only: they never mean that a payment or donation was made.
 */
#[Fillable(['page_path'])]
class SupportClick extends Model
{
    /** @use HasFactory<SupportClickFactory> */
    use HasFactory, MassPrunable;

    public const CREATED_AT = 'clicked_at';

    public const UPDATED_AT = null;

    /**
     * Longest custom date range, in days, the analytics endpoints accept.
     */
    public const MAX_RANGE_DAYS = 366;

    /**
     * Ways the dashboard's Support button chart can bucket clicks.
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
            'platform' => SupportPlatform::class,
            'filtered_as' => SupportClickFilter::class,
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
     * Clicks that opened the Support button.
     *
     * @param  Builder<SupportClick>  $query
     */
    public function scopeButtonOpens(Builder $query): void
    {
        $query->whereNull('platform');
    }

    /**
     * Clicks on a payment link.
     *
     * @param  Builder<SupportClick>  $query
     */
    public function scopeLinkClicks(Builder $query): void
    {
        $query->whereIn('platform', SupportPlatform::values());
    }

    /**
     * Clicks that count: not marked as a bot or a duplicate.
     *
     * @param  Builder<SupportClick>  $query
     */
    public function scopeCounted(Builder $query): void
    {
        $query->whereNull('filtered_as');
    }

    /**
     * Clicks older than the configured retention period, deleted by `model:prune`.
     *
     * @return Builder<SupportClick>
     */
    public function prunable(): Builder
    {
        $days = (int) config('analytics.support_clicks.retention_days');

        return $days > 0
            ? static::query()->where('clicked_at', '<', now()->subDays($days))
            : static::query()->whereRaw('1 = 0');
    }

    /**
     * Store a click with the visitor's approximate country and a privacy-safe visitor hash.
     * The IP address and user agent are only used here and never stored.
     */
    public static function record(Request $request, ?SupportPlatform $platform = null, ?string $pagePath = null, ?string $supportUrl = null): self
    {
        $visitorHash = static::visitorHash($request);
        $countryCode = IpCountryRange::countryFor($request);

        $click = new self(['page_path' => $pagePath]);
        $click->platform = $platform;
        $click->support_url = $supportUrl;
        $click->user_id = $request->user()?->id;
        $click->country_code = $countryCode;
        $click->country_name = IpCountryRange::countryName($countryCode);
        $click->visitor_hash = $visitorHash;
        $click->filtered_as = static::filterFor($request, $platform, $visitorHash);
        $click->save();

        return $click;
    }

    /**
     * Keyed hash identifying a visitor without storing who they are: the member account when signed in,
     * otherwise the IP address and browser. Used for unique-visitor counts and duplicate detection.
     */
    public static function visitorHash(Request $request): string
    {
        $identity = $request->user() !== null
            ? 'member:'.$request->user()->id
            : 'guest:'.$request->ip().'|'.$request->userAgent();

        return hash_hmac('sha256', $identity, (string) config('app.key'));
    }

    /**
     * Short, non-reversible label for a visitor shown in the admin.
     */
    public function visitorLabel(): string
    {
        return strtoupper(substr((string) $this->visitor_hash, 0, 8));
    }

    /**
     * Bots are filtered out first; a repeat click on the same link within the duplicate window is
     * stored as a duplicate so double-clicks and quick back/forward navigation count once.
     */
    private static function filterFor(Request $request, ?SupportPlatform $platform, string $visitorHash): ?SupportClickFilter
    {
        $userAgent = (string) $request->userAgent();

        if ($userAgent === '' || preg_match(config('analytics.bot_user_agents'), $userAgent) === 1) {
            return SupportClickFilter::Bot;
        }

        $window = (int) config('analytics.support_clicks.duplicate_window');

        if ($platform !== null && $window > 0 && ! Cache::add("support-click:{$platform->value}:{$visitorHash}", true, $window)) {
            return SupportClickFilter::Duplicate;
        }

        return null;
    }

    /**
     * Everything the dashboard's Support analytics section shows for one date range.
     *
     * @return array{
     *     stats: array{total: int, today: int, last7Days: int, thisMonth: int},
     *     links: array{total: int, platforms: list<array{key: string, label: string, clicks: int}>},
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
            'links' => static::linkClicksToday(),
            'range' => [
                'key' => $range->value,
                'label' => $range->label(),
                'from' => $start->toDateString(),
                'to' => $end->toDateString(),
                'clicks' => array_sum($daily),
            ],
            'group' => $group,
            'chart' => array_map(fn (array $bucket): array => [
                'label' => $bucket['label'],
                'title' => $bucket['title'],
                'clicks' => array_sum(array_map(fn (string $day): int => $daily[$day] ?? 0, $bucket['days'])),
            ], static::buckets($start, $end, $group)),
            'topPages' => static::topPages($start, $end),
            'updatedAt' => now()->toIso8601String(),
        ];
    }

    /**
     * All-time, today, last 7 days (including today) and this month's Support button opens in a single query.
     *
     * @return array{total: int, today: int, last7Days: int, thisMonth: int}
     */
    public static function summary(): array
    {
        $today = now()->startOfDay();

        $counts = static::query()
            ->buttonOpens()
            ->counted()
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
     * Today's counted payment-link clicks per platform.
     *
     * @return array{total: int, platforms: list<array{key: string, label: string, clicks: int}>}
     */
    public static function linkClicksToday(): array
    {
        $counts = static::query()
            ->linkClicks()
            ->counted()
            ->where('clicked_at', '>=', now()->startOfDay())
            ->toBase()
            ->selectRaw('platform, COUNT(*) as clicks')
            ->groupBy('platform')
            ->pluck('clicks', 'platform');

        $platforms = array_map(fn (SupportPlatform $platform): array => [
            'key' => $platform->value,
            'label' => $platform->label(),
            'clicks' => (int) ($counts[$platform->value] ?? 0),
        ], SupportPlatform::cases());

        return ['total' => array_sum(array_column($platforms, 'clicks')), 'platforms' => $platforms];
    }

    /**
     * Calendar buckets covering the window: one per day, per Monday-to-Sunday week or per month,
     * clipped to the window at both ends.
     *
     * @return list<array{label: string, title: string, days: list<string>}>
     */
    public static function buckets(Carbon $start, Carbon $end, string $group): array
    {
        $isShortRange = $start->diffInDays($end) < 7;
        $buckets = [];

        foreach (CarbonPeriod::create($start, '1 day', $end) as $date) {
            $key = match ($group) {
                'week' => $date->copy()->startOfWeek(CarbonInterface::MONDAY)->toDateString(),
                'month' => $date->format('Y-m'),
                default => $date->toDateString(),
            };

            $buckets[$key] ??= ['first' => $date->copy(), 'days' => []];
            $buckets[$key]['last'] = $date->copy();
            $buckets[$key]['days'][] = $date->toDateString();
        }

        return array_values(array_map(function (array $bucket) use ($group, $isShortRange): array {
            /** @var CarbonInterface $first */
            $first = $bucket['first'];
            /** @var CarbonInterface $last */
            $last = $bucket['last'];

            $label = match ($group) {
                'week' => $first->format('M j'),
                'month' => $first->format('M Y'),
                default => $first->isToday() ? 'Today' : $first->format($isShortRange ? 'D' : 'M j'),
            };

            $title = match (true) {
                $first->isSameDay($last) => $first->format('D, M j, Y'),
                $group === 'month' && $first->day === 1 && $last->isLastOfMonth() => $first->format('F Y'),
                default => $first->format('M j').' – '.$last->format('M j, Y'),
            };

            return ['label' => $label, 'title' => $title, 'days' => $bucket['days']];
        }, $buckets));
    }

    /**
     * Support button opens per calendar day inside the window, keyed by Y-m-d. Days without clicks are missing.
     *
     * @return array<string, int>
     */
    private static function dailyCounts(Carbon $start, Carbon $end): array
    {
        return static::query()
            ->buttonOpens()
            ->counted()
            ->whereBetween('clicked_at', [$start, $end])
            ->toBase()
            ->selectRaw('DATE(clicked_at) as day, COUNT(*) as clicks')
            ->groupByRaw('DATE(clicked_at)')
            ->pluck('clicks', 'day')
            ->map(fn ($clicks): int => (int) $clicks)
            ->all();
    }

    /**
     * Pages where visitors opened the Support button most often in the window.
     *
     * @return list<array{path: string, clicks: int}>
     */
    private static function topPages(Carbon $start, Carbon $end): array
    {
        return static::query()
            ->buttonOpens()
            ->counted()
            ->whereBetween('clicked_at', [$start, $end])
            ->whereNotNull('page_path')
            ->toBase()
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
