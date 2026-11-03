<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SupportClickFilter;
use App\Enums\SupportClickRange;
use App\Enums\SupportPlatform;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SupportLinkAnalyticsRequest;
use App\Models\IpCountryRange;
use App\Models\SupportClick;
use Carbon\CarbonInterface;
use Generator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Clicks on the Support button's payment links (YouPay, Cash App, ...). These figures show interest
 * only: a click is never a completed payment or donation.
 */
class SupportLinkAnalyticsController extends Controller
{
    /**
     * Rows per page of the recent activity table.
     */
    private const ACTIVITY_PER_PAGE = 20;

    public function index(SupportLinkAnalyticsRequest $request): View
    {
        [$from, $to] = $request->customDates();

        return view('admin.support-links', [
            'report' => $this->report($request->range(), $request->group(), $from, $to),
            'ranges' => SupportClickRange::cases(),
            'geoipInstalled' => IpCountryRange::query()->exists(),
            'geoipHeader' => config('analytics.geoip.header'),
            'retentionDays' => (int) config('analytics.support_clicks.retention_days'),
            'duplicateWindow' => (int) config('analytics.support_clicks.duplicate_window'),
        ]);
    }

    public function data(SupportLinkAnalyticsRequest $request): JsonResponse
    {
        [$from, $to] = $request->customDates();

        return response()->json($this->report($request->range(), $request->group(), $from, $to));
    }

    /**
     * Most recent link clicks in the range, including the ones left out of the counts.
     */
    public function activity(SupportLinkAnalyticsRequest $request): JsonResponse
    {
        [$from, $to] = $request->customDates();
        [$start, $end] = $request->range()->period($from, $to);

        $clicks = SupportClick::query()
            ->linkClicks()
            ->whereBetween('clicked_at', [$start, $end])
            ->latest('clicked_at')
            ->latest('id')
            ->paginate(self::ACTIVITY_PER_PAGE, ['id', 'platform', 'country_code', 'page_path', 'user_id', 'visitor_hash', 'filtered_as', 'clicked_at'], 'page', $request->integer('page', 1));

        return response()->json([
            'data' => array_map(fn (SupportClick $click): array => [
                'time' => $click->clicked_at->format('M j, Y g:i:s A'),
                'country' => ['code' => $click->country_code, 'name' => IpCountryRange::countryName($click->country_code)],
                'platform' => $click->platform->value,
                'page' => $click->page_path,
                'visitorType' => $click->user_id !== null ? 'Member' : 'Guest',
                'visitor' => $click->visitorLabel(),
                'filtered' => $click->filtered_as?->label(),
            ], $clicks->items()),
            'meta' => [
                'page' => $clicks->currentPage(),
                'lastPage' => $clicks->lastPage(),
                'total' => $clicks->total(),
                'from' => $clicks->firstItem(),
                'to' => $clicks->lastItem(),
            ],
        ]);
    }

    /**
     * Download the country report, daily statistics or every click of the selected range as CSV.
     */
    public function export(SupportLinkAnalyticsRequest $request): StreamedResponse
    {
        [$from, $to] = $request->customDates();
        [$start, $end] = $request->range()->period($from, $to);
        $report = $request->report();

        return response()->streamDownload(function () use ($report, $start, $end): void {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");

            foreach ($this->exportRows($report, $start, $end) as $row) {
                fputcsv($output, array_map($this->csvCell(...), $row), ',', '"', '');
            }

            fclose($output);
        }, sprintf('support-links-%s_%s_to_%s.csv', $report, $start->toDateString(), $end->toDateString()), [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, private',
        ]);
    }

    /**
     * Everything the page shows for one range.
     *
     * @return array<string, mixed>
     */
    private function report(SupportClickRange $range, string $group, ?Carbon $from, ?Carbon $to): array
    {
        [$start, $end] = $range->period($from, $to);
        $days = (int) round($start->diffInDays($end));
        $previousStart = $start->copy()->subDays($days);
        $previousEnd = $start->copy()->subDay()->endOfDay();

        $current = $this->platformCounts($start, $end);
        $previous = $this->platformCounts($previousStart, $previousEnd);
        $totalClicks = array_sum(array_column($current, 'clicks'));

        return [
            'range' => [
                'key' => $range->value,
                'label' => $range->label(),
                'from' => $start->toDateString(),
                'to' => $end->toDateString(),
                'compareLabel' => $days === 1 ? 'the previous day' : "the previous {$days} days",
                'timezone' => config('app.timezone'),
            ],
            'group' => $group,
            'totals' => [
                'clicks' => $totalClicks,
                'visitors' => $this->uniqueVisitors($start, $end),
                'previousClicks' => array_sum(array_column($previous, 'clicks')),
                'previousVisitors' => $this->uniqueVisitors($previousStart, $previousEnd),
            ],
            'platforms' => array_map(fn (SupportPlatform $platform): array => [
                'key' => $platform->value,
                'label' => $platform->label(),
                'color' => $platform->color(),
                'icon' => $platform->icon(),
                'clicks' => $current[$platform->value]['clicks'] ?? 0,
                'visitors' => $current[$platform->value]['visitors'] ?? 0,
                'previousClicks' => $previous[$platform->value]['clicks'] ?? 0,
                'share' => $this->share($current[$platform->value]['clicks'] ?? 0, $totalClicks),
            ], SupportPlatform::cases()),
            'periods' => $this->periods(),
            'filtered' => $this->filtered($start, $end),
            'chart' => $this->chart($start, $end, $group),
            'countries' => $this->countries($start, $end),
            'updatedAt' => now()->toIso8601String(),
        ];
    }

    /**
     * Counted link clicks inside the window.
     *
     * @return Builder<SupportClick>
     */
    private function clicks(Carbon $start, Carbon $end): Builder
    {
        return SupportClick::query()->linkClicks()->counted()->whereBetween('clicked_at', [$start, $end]);
    }

    /**
     * @return array<string, array{clicks: int, visitors: int}>
     */
    private function platformCounts(Carbon $start, Carbon $end): array
    {
        return $this->clicks($start, $end)
            ->toBase()
            ->selectRaw('platform, COUNT(*) as clicks, COUNT(DISTINCT visitor_hash) as visitors')
            ->groupBy('platform')
            ->get()
            ->mapWithKeys(fn (object $row): array => [$row->platform => ['clicks' => (int) $row->clicks, 'visitors' => (int) $row->visitors]])
            ->all();
    }

    private function uniqueVisitors(Carbon $start, Carbon $end): int
    {
        return $this->clicks($start, $end)->toBase()->distinct()->count('visitor_hash');
    }

    /**
     * Today, this week (from Monday) and this month per platform, in one query.
     *
     * @return array<string, array{label: string, since: string, total: int, platforms: array<string, int>}>
     */
    private function periods(): array
    {
        $starts = [
            'today' => ['Today', now()->startOfDay()],
            'week' => ['This week', now()->startOfWeek(CarbonInterface::MONDAY)],
            'month' => ['This month', now()->startOfMonth()],
        ];

        $query = SupportClick::query()
            ->linkClicks()
            ->counted()
            ->where('clicked_at', '>=', $starts['week'][1]->lt($starts['month'][1]) ? $starts['week'][1] : $starts['month'][1])
            ->toBase()
            ->select('platform')
            ->groupBy('platform');

        foreach ($starts as $key => [, $since]) {
            $query->selectRaw("SUM(CASE WHEN clicked_at >= ? THEN 1 ELSE 0 END) as {$key}", [$since]);
        }

        $rows = $query->get()->keyBy('platform');
        $periods = [];

        foreach ($starts as $key => [$label, $since]) {
            $platforms = $this->perPlatform(fn (SupportPlatform $platform): int => (int) ($rows[$platform->value]->{$key} ?? 0));

            $periods[$key] = [
                'label' => $label,
                'since' => $key === 'today' ? $since->format('D, M j') : 'Since '.$since->format('D, M j'),
                'total' => array_sum($platforms),
                'platforms' => $platforms,
            ];
        }

        return $periods;
    }

    /**
     * Link clicks in the window left out of the counts, per reason.
     *
     * @return array{total: int, reasons: list<array{key: string, label: string, clicks: int}>}
     */
    private function filtered(Carbon $start, Carbon $end): array
    {
        $counts = SupportClick::query()
            ->linkClicks()
            ->whereNotNull('filtered_as')
            ->whereBetween('clicked_at', [$start, $end])
            ->toBase()
            ->selectRaw('filtered_as, COUNT(*) as clicks')
            ->groupBy('filtered_as')
            ->pluck('clicks', 'filtered_as');

        $reasons = array_map(fn (SupportClickFilter $filter): array => [
            'key' => $filter->value,
            'label' => $filter->label(),
            'clicks' => (int) ($counts[$filter->value] ?? 0),
        ], SupportClickFilter::cases());

        return ['total' => array_sum(array_column($reasons, 'clicks')), 'reasons' => $reasons];
    }

    /**
     * Clicks per platform for every day, week or month of the window.
     *
     * @return list<array{label: string, title: string, platforms: array<string, int>, total: int}>
     */
    private function chart(Carbon $start, Carbon $end, string $group): array
    {
        $daily = $this->dailyCounts($start, $end);

        return array_map(function (array $bucket) use ($daily): array {
            $platforms = $this->perPlatform(fn (SupportPlatform $platform): int => array_sum(array_map(
                fn (string $day): int => $daily[$day][$platform->value] ?? 0,
                $bucket['days'],
            )));

            return ['label' => $bucket['label'], 'title' => $bucket['title'], 'platforms' => $platforms, 'total' => array_sum($platforms)];
        }, SupportClick::buckets($start, $end, $group));
    }

    /**
     * Clicks keyed by Y-m-d and platform. Days without clicks are missing.
     *
     * @return array<string, array<string, int>>
     */
    private function dailyCounts(Carbon $start, Carbon $end): array
    {
        $daily = [];

        $rows = $this->clicks($start, $end)
            ->toBase()
            ->selectRaw('DATE(clicked_at) as day, platform, COUNT(*) as clicks')
            ->groupByRaw('DATE(clicked_at), platform')
            ->get();

        foreach ($rows as $row) {
            $daily[$row->day][$row->platform] = (int) $row->clicks;
        }

        return $daily;
    }

    /**
     * Clicks per country and platform, busiest country first. Clicks without a country are "Unknown".
     *
     * @return list<array{code: ?string, name: string, platforms: array<string, int>, total: int, share: float}>
     */
    private function countries(Carbon $start, Carbon $end): array
    {
        $rows = $this->clicks($start, $end)
            ->toBase()
            ->selectRaw('country_code, platform, COUNT(*) as clicks')
            ->groupBy('country_code', 'platform')
            ->get();

        $countries = [];

        foreach ($rows as $row) {
            $code = IpCountryRange::isKnownCountry($row->country_code) ? $row->country_code : null;
            $countries[$code ?? ''] ??= ['code' => $code, 'name' => IpCountryRange::countryName($code), 'platforms' => $this->perPlatform(fn (): int => 0)];
            $countries[$code ?? '']['platforms'][$row->platform] += (int) $row->clicks;
        }

        $grandTotal = (int) $rows->sum(fn (object $row): int => (int) $row->clicks);

        $countries = array_map(function (array $country) use ($grandTotal): array {
            $total = array_sum($country['platforms']);

            return [...$country, 'total' => $total, 'share' => $this->share($total, $grandTotal)];
        }, array_values($countries));

        usort($countries, fn (array $a, array $b): int => [$b['total'], $a['name']] <=> [$a['total'], $b['name']]);

        return $countries;
    }

    /**
     * @param  callable(SupportPlatform): int  $value
     * @return array<string, int>
     */
    private function perPlatform(callable $value): array
    {
        $values = [];

        foreach (SupportPlatform::cases() as $platform) {
            $values[$platform->value] = $value($platform);
        }

        return $values;
    }

    private function share(int $part, int $total): float
    {
        return $total > 0 ? round($part / $total * 100, 1) : 0.0;
    }

    /**
     * @return Generator<int, list<string|int|float|null>>
     */
    private function exportRows(string $report, Carbon $start, Carbon $end): Generator
    {
        $labels = array_map(fn (SupportPlatform $platform): string => $platform->label().' clicks', SupportPlatform::cases());

        if ($report === 'daily') {
            $daily = $this->dailyCounts($start, $end);
            $visitors = $this->clicks($start, $end)
                ->toBase()
                ->selectRaw('DATE(clicked_at) as day, COUNT(DISTINCT visitor_hash) as visitors')
                ->groupByRaw('DATE(clicked_at)')
                ->pluck('visitors', 'day');

            yield ['Date', ...$labels, 'Total clicks', 'Unique visitors'];

            $totals = $this->perPlatform(fn (): int => 0);

            foreach (SupportClick::buckets($start, $end, 'day') as $bucket) {
                $day = $bucket['days'][0];
                $counts = $this->perPlatform(fn (SupportPlatform $platform): int => $daily[$day][$platform->value] ?? 0);

                foreach ($counts as $key => $count) {
                    $totals[$key] += $count;
                }

                yield [$day, ...array_values($counts), array_sum($counts), (int) ($visitors[$day] ?? 0)];
            }

            yield ['Total '.$start->toDateString().' to '.$end->toDateString(), ...array_values($totals), array_sum($totals), $this->uniqueVisitors($start, $end)];

            return;
        }

        if ($report === 'clicks') {
            yield ['Clicked at ('.config('app.timezone').')', 'Platform', 'Country', 'Country code', 'Referrer page', 'Visitor type', 'Visitor ID', 'Counted', 'Not counted because'];

            $clicks = SupportClick::query()
                ->linkClicks()
                ->whereBetween('clicked_at', [$start, $end])
                ->select(['id', 'platform', 'country_code', 'page_path', 'user_id', 'visitor_hash', 'filtered_as', 'clicked_at'])
                ->lazyById(1000);

            foreach ($clicks as $click) {
                yield [
                    $click->clicked_at->toDateTimeString(),
                    $click->platform->label(),
                    IpCountryRange::countryName($click->country_code),
                    $click->country_code,
                    $click->page_path,
                    $click->user_id !== null ? 'Member' : 'Guest',
                    $click->visitorLabel(),
                    $click->filtered_as === null ? 'Yes' : 'No',
                    $click->filtered_as?->label(),
                ];
            }

            return;
        }

        yield ['Country', 'Country code', ...$labels, 'Total clicks', 'Share of clicks (%)', 'From', 'To'];

        foreach ($this->countries($start, $end) as $country) {
            yield [$country['name'], $country['code'], ...array_values($country['platforms']), $country['total'], $country['share'], $start->toDateString(), $end->toDateString()];
        }
    }

    /**
     * Keep spreadsheet apps from running cell values as formulas.
     */
    private function csvCell(string|int|float|null $value): string|int|float
    {
        if (is_string($value) && $value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'".$value;
        }

        return $value ?? '';
    }
}
