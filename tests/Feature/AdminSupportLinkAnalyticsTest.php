<?php

namespace Tests\Feature;

use App\Enums\SupportClickFilter;
use App\Enums\SupportPlatform;
use App\Models\IpCountryRange;
use App\Models\SupportClick;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminSupportLinkAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // Wednesday 18 November 2026: this week started on Monday 16th, this month on the 1st.
        $this->travelTo(Carbon::parse('2026-11-18 12:00:00'));
        $this->admin = User::factory()->admin()->create();
    }

    /**
     * Counted link clicks:
     *  - today: Cash App ×3 from the US (visitors a, a, b), YouPay ×1 from the UK (visitor c)
     *  - yesterday: YouPay ×2 from the US (visitor d)
     *  - Sunday 15th: Cash App ×1, country unknown (visitor e)
     *  - Sunday 8th: Cash App ×1 from Canada
     *  - Tuesday 20 October: YouPay ×1 and Cash App ×1 from the US
     * Plus two bot clicks and a duplicate today, and five Support button opens that must be ignored.
     */
    private function seedClicks(): void
    {
        $cashApp = SupportClick::factory()->link(SupportPlatform::CashApp);
        $youPay = SupportClick::factory()->link(SupportPlatform::YouPay);

        $cashApp->fromCountry('US')->byVisitor('a')->count(2)->create(['clicked_at' => now()->subHours(2)]);
        $cashApp->fromCountry('US')->byVisitor('b')->create(['clicked_at' => now()->subHour()]);
        $youPay->fromCountry('GB')->byVisitor('c')->create(['clicked_at' => now()->subMinutes(30)]);
        $youPay->fromCountry('US')->byVisitor('d')->count(2)->create(['clicked_at' => now()->subDay()]);
        $cashApp->fromCountry(null)->byVisitor('e')->create(['clicked_at' => now()->subDays(3)]);
        $cashApp->fromCountry('CA')->create(['clicked_at' => now()->subDays(10)]);
        $youPay->fromCountry('US')->create(['clicked_at' => Carbon::parse('2026-10-20 09:00:00')]);
        $cashApp->fromCountry('US')->create(['clicked_at' => Carbon::parse('2026-10-20 18:00:00')]);

        $cashApp->fromCountry('US')->filtered(SupportClickFilter::Bot)->count(2)->create(['clicked_at' => now()->subMinutes(10)]);
        $cashApp->fromCountry('US')->filtered(SupportClickFilter::Duplicate)->byVisitor('a')->create(['clicked_at' => now()->subHours(2)]);
        SupportClick::factory()->count(5)->create(['clicked_at' => now()->subMinutes(5)]);
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    private function report(array $query = []): array
    {
        return $this->actingAs($this->admin)->getJson(route('admin.support-links.data', $query))->assertOk()->json();
    }

    /**
     * @param  array<string, mixed>  $report
     * @return array<string, mixed>
     */
    private function platform(array $report, string $key): array
    {
        return collect($report['platforms'])->firstWhere('key', $key);
    }

    /**
     * @return list<list<string>>
     */
    private function csv(string $content): array
    {
        $lines = preg_split('/\r?\n/', trim(str_replace("\xEF\xBB\xBF", '', $content)));

        return array_map(fn (string $line): array => str_getcsv($line, ',', '"', ''), $lines);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function endpointProvider(): array
    {
        return [
            'page' => ['admin.support-links'],
            'data' => ['admin.support-links.data'],
            'activity' => ['admin.support-links.activity'],
            'export' => ['admin.support-links.export'],
        ];
    }

    #[DataProvider('endpointProvider')]
    public function test_guests_and_customers_cannot_view_link_analytics(string $routeName): void
    {
        $this->get(route($routeName))->assertRedirect(route('login'));
        $this->getJson(route($routeName))->assertUnauthorized();
        $this->actingAs(User::factory()->create())->get(route($routeName))->assertForbidden();
    }

    public function test_page_renders_with_real_figures_and_the_clicks_are_not_payments_notice(): void
    {
        $this->seedClicks();

        $response = $this->actingAs($this->admin)->get(route('admin.support-links'));

        $response->assertOk();
        $response->assertSee('Support link analytics');
        $response->assertSee('Clicks, not payments.');
        $response->assertSee('A click does not mean that a payment or donation was made.');
        $response->assertSee('href="'.route('admin.support-links').'" class="active" aria-current="page"', false);
        $response->assertSee('data-stat="clicks">10<', false);
        $response->assertSee('data-platform-clicks="cashapp">6<', false);
        $response->assertSee('data-platform-clicks="youpay">4<', false);
        $response->assertSee('IP geolocation by');

        foreach (['today', 'yesterday', 'last_7_days', 'last_30_days', 'this_month', 'last_month', 'custom'] as $range) {
            $response->assertSee('data-range="'.$range.'"', false);
        }
    }

    public function test_page_explains_how_to_set_up_country_detection_until_it_is_installed(): void
    {
        $this->actingAs($this->admin)->get(route('admin.support-links'))->assertSee('Country detection is not set up yet');

        IpCountryRange::create(['ip_from' => IpCountryRange::toHex('8.8.8.0'), 'ip_to' => IpCountryRange::toHex('8.8.8.255'), 'country_code' => 'US']);

        $this->actingAs($this->admin)->get(route('admin.support-links'))->assertDontSee('Country detection is not set up yet');
    }

    public function test_totals_count_each_platform_separately_and_ignore_filtered_clicks_and_button_opens(): void
    {
        $this->seedClicks();

        $report = $this->report(['range' => 'last_7_days']);

        $this->assertSame(['key' => 'last_7_days', 'label' => 'Last 7 days', 'from' => '2026-11-12', 'to' => '2026-11-18', 'compareLabel' => 'the previous 7 days', 'timezone' => 'UTC'], $report['range']);
        $this->assertSame(['clicks' => 7, 'visitors' => 5, 'previousClicks' => 1, 'previousVisitors' => 1], $report['totals']);
        $this->assertSame(['clicks' => 4, 'visitors' => 3, 'previousClicks' => 1, 'share' => 57.1], collect($this->platform($report, 'cashapp'))->only(['clicks', 'visitors', 'previousClicks', 'share'])->all());
        $this->assertSame(['clicks' => 3, 'visitors' => 2, 'previousClicks' => 0, 'share' => 42.9], collect($this->platform($report, 'youpay'))->only(['clicks', 'visitors', 'previousClicks', 'share'])->all());
        $this->assertSame(['label' => 'Cash App', 'color' => '#00b33e'], collect($this->platform($report, 'cashapp'))->only(['label', 'color'])->all());
    }

    public function test_today_this_week_and_this_month_are_broken_down_by_platform(): void
    {
        $this->seedClicks();

        $periods = $this->report(['range' => 'last_month'])['periods'];

        $this->assertSame(['label' => 'Today', 'since' => 'Wed, Nov 18', 'total' => 4, 'platforms' => ['youpay' => 1, 'cashapp' => 3]], $periods['today']);
        $this->assertSame(['label' => 'This week', 'since' => 'Since Mon, Nov 16', 'total' => 6, 'platforms' => ['youpay' => 3, 'cashapp' => 3]], $periods['week']);
        $this->assertSame(['label' => 'This month', 'since' => 'Since Sun, Nov 1', 'total' => 8, 'platforms' => ['youpay' => 3, 'cashapp' => 5]], $periods['month']);
    }

    /**
     * @return array<string, array{string, string, string, int, int, int}>
     */
    public static function rangeProvider(): array
    {
        return [
            'today' => ['today', '2026-11-18', '2026-11-18', 4, 1, 3],
            'yesterday' => ['yesterday', '2026-11-17', '2026-11-17', 2, 2, 0],
            'last 7 days' => ['last_7_days', '2026-11-12', '2026-11-18', 7, 3, 4],
            'last 30 days' => ['last_30_days', '2026-10-20', '2026-11-18', 10, 4, 6],
            'this month' => ['this_month', '2026-11-01', '2026-11-18', 8, 3, 5],
            'last month' => ['last_month', '2026-10-01', '2026-10-31', 2, 1, 1],
        ];
    }

    #[DataProvider('rangeProvider')]
    public function test_every_reporting_period_uses_the_right_dates(string $range, string $from, string $to, int $total, int $youPay, int $cashApp): void
    {
        $this->seedClicks();

        $report = $this->report(['range' => $range]);

        $this->assertSame([$from, $to], [$report['range']['from'], $report['range']['to']]);
        $this->assertSame($total, $report['totals']['clicks']);
        $this->assertSame($youPay, $this->platform($report, 'youpay')['clicks']);
        $this->assertSame($cashApp, $this->platform($report, 'cashapp')['clicks']);
        $this->assertSame($total, collect($report['chart'])->sum('total'));
    }

    public function test_custom_date_range(): void
    {
        $this->seedClicks();

        $report = $this->report(['range' => 'custom', 'from' => '2026-11-15', 'to' => '2026-11-17']);

        $this->assertSame(['2026-11-15', '2026-11-17', 'Custom range'], [$report['range']['from'], $report['range']['to'], $report['range']['label']]);
        $this->assertSame(3, $report['totals']['clicks']);
        $this->assertCount(3, $report['chart']);
        $this->assertSame('the previous 3 days', $report['range']['compareLabel']);
    }

    public function test_daily_chart_shows_each_platform_and_the_total(): void
    {
        $this->seedClicks();

        $chart = $this->report(['range' => 'last_7_days', 'group' => 'day'])['chart'];

        $this->assertCount(7, $chart);
        $this->assertSame(['label' => 'Today', 'title' => 'Wed, Nov 18, 2026', 'platforms' => ['youpay' => 1, 'cashapp' => 3], 'total' => 4], $chart[6]);
        $this->assertSame(['label' => 'Tue', 'title' => 'Tue, Nov 17, 2026', 'platforms' => ['youpay' => 2, 'cashapp' => 0], 'total' => 2], $chart[5]);
        $this->assertSame(1, $chart[3]['platforms']['cashapp']);
    }

    public function test_weekly_and_monthly_trends(): void
    {
        $this->seedClicks();

        $weeks = $this->report(['range' => 'this_month', 'group' => 'week'])['chart'];
        $this->assertSame(['Sun, Nov 1, 2026', 'Nov 2 – Nov 8, 2026', 'Nov 9 – Nov 15, 2026', 'Nov 16 – Nov 18, 2026'], array_column($weeks, 'title'));
        $this->assertSame([0, 1, 1, 6], array_column($weeks, 'total'));

        $months = $this->report(['range' => 'last_30_days', 'group' => 'month'])['chart'];
        $this->assertSame(['Oct 2026', 'Nov 2026'], array_column($months, 'label'));
        $this->assertSame(['Oct 20 – Oct 31, 2026', 'Nov 1 – Nov 18, 2026'], array_column($months, 'title'));
        $this->assertSame([['youpay' => 1, 'cashapp' => 1], ['youpay' => 3, 'cashapp' => 5]], array_column($months, 'platforms'));

        $this->assertSame('October 2026', $this->report(['range' => 'last_month', 'group' => 'month'])['chart'][0]['title']);
    }

    public function test_country_report_lists_platform_preference_per_country_with_unknown(): void
    {
        $this->seedClicks();

        $countries = $this->report(['range' => 'last_30_days'])['countries'];

        $this->assertSame([
            ['code' => 'US', 'name' => 'United States', 'platforms' => ['youpay' => 3, 'cashapp' => 4], 'total' => 7, 'share' => 70],
            ['code' => 'CA', 'name' => 'Canada', 'platforms' => ['youpay' => 0, 'cashapp' => 1], 'total' => 1, 'share' => 10],
            ['code' => 'GB', 'name' => 'United Kingdom', 'platforms' => ['youpay' => 1, 'cashapp' => 0], 'total' => 1, 'share' => 10],
            ['code' => null, 'name' => 'Unknown', 'platforms' => ['youpay' => 0, 'cashapp' => 1], 'total' => 1, 'share' => 10],
        ], $countries);
    }

    public function test_country_report_follows_the_date_filter(): void
    {
        $this->seedClicks();

        $this->assertSame(['US', 'GB'], array_column($this->report(['range' => 'today'])['countries'], 'code'));
        $this->assertSame([], $this->report(['range' => 'custom', 'from' => '2026-09-01', 'to' => '2026-09-30'])['countries']);
    }

    public function test_unique_visitors_count_repeat_visitors_once(): void
    {
        SupportClick::factory()->link(SupportPlatform::CashApp)->byVisitor('same')->count(4)->create();
        SupportClick::factory()->link(SupportPlatform::YouPay)->byVisitor('same')->create();
        SupportClick::factory()->link(SupportPlatform::YouPay)->byVisitor('other')->create();

        $report = $this->report(['range' => 'today']);

        $this->assertSame(6, $report['totals']['clicks']);
        $this->assertSame(2, $report['totals']['visitors']);
        $this->assertSame(1, $this->platform($report, 'cashapp')['visitors']);
        $this->assertSame(2, $this->platform($report, 'youpay')['visitors']);
    }

    public function test_filtered_clicks_are_reported_separately(): void
    {
        $this->seedClicks();

        $filtered = $this->report(['range' => 'today'])['filtered'];

        $this->assertSame(3, $filtered['total']);
        $this->assertSame([
            ['key' => 'bot', 'label' => 'Bot or automated', 'clicks' => 2],
            ['key' => 'duplicate', 'label' => 'Repeat within seconds', 'clicks' => 1],
        ], $filtered['reasons']);
    }

    public function test_empty_report_renders_without_errors(): void
    {
        $this->actingAs($this->admin)->get(route('admin.support-links'))->assertOk()->assertSee('data-stat="clicks">0<', false);

        $report = $this->report();

        $this->assertSame('last_30_days', $report['range']['key']);
        $this->assertSame(0, $report['totals']['clicks']);
        $this->assertSame(0.0, (float) $this->platform($report, 'cashapp')['share']);
        $this->assertSame([], $report['countries']);
        $this->assertCount(30, $report['chart']);
    }

    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    public static function invalidFilterProvider(): array
    {
        return [
            'unknown range' => [['range' => 'forever'], 'range'],
            'unknown group' => [['group' => 'year'], 'group'],
            'custom without dates' => [['range' => 'custom'], 'from'],
            'end before start' => [['range' => 'custom', 'from' => '2026-11-10', 'to' => '2026-11-01'], 'to'],
            'future end date' => [['range' => 'custom', 'from' => '2026-11-10', 'to' => '2026-11-19'], 'to'],
            'range longer than a year' => [['range' => 'custom', 'from' => '2025-01-01', 'to' => '2026-11-18'], 'to'],
            'page zero' => [['page' => 0], 'page'],
            'unknown report' => [['report' => 'revenue'], 'report'],
        ];
    }

    /**
     * @param  array<string, mixed>  $query
     */
    #[DataProvider('invalidFilterProvider')]
    public function test_invalid_filters_are_rejected(array $query, string $field): void
    {
        $this->actingAs($this->admin)
            ->getJson(route('admin.support-links.data', $query))
            ->assertUnprocessable()
            ->assertJsonValidationErrors($field);
    }

    public function test_recent_activity_is_paginated_newest_first_without_sensitive_data(): void
    {
        $member = User::factory()->create();
        SupportClick::factory()->link(SupportPlatform::YouPay)->fromCountry('GB')->count(22)->create(['clicked_at' => now()->subHours(3), 'page_path' => '/rules']);
        SupportClick::factory()->link(SupportPlatform::CashApp)->fromCountry('US')->byMember($member)->byVisitor('member')->create(['clicked_at' => now()->subMinute(), 'page_path' => '/p/abc123']);
        SupportClick::factory()->link(SupportPlatform::CashApp)->filtered(SupportClickFilter::Bot)->create(['clicked_at' => now()->subMinutes(2)]);
        SupportClick::factory()->count(3)->create();

        $first = $this->actingAs($this->admin)->getJson(route('admin.support-links.activity', ['range' => 'today']))->assertOk();

        $first->assertJsonPath('meta', ['page' => 1, 'lastPage' => 2, 'total' => 24, 'from' => 1, 'to' => 20]);
        $first->assertJsonCount(20, 'data');
        $first->assertJsonPath('data.0', [
            'time' => 'Nov 18, 2026 11:59:00 AM',
            'country' => ['code' => 'US', 'name' => 'United States'],
            'platform' => 'cashapp',
            'page' => '/p/abc123',
            'visitorType' => 'Member',
            'visitor' => strtoupper(substr(hash('sha256', 'member'), 0, 8)),
            'filtered' => null,
        ]);
        $first->assertJsonPath('data.1.filtered', 'Bot or automated');
        $first->assertJsonPath('data.2.visitorType', 'Guest');
        $this->assertStringNotContainsString(hash('sha256', 'member'), $first->getContent());
        $this->assertStringNotContainsString('127.0.0.1', $first->getContent());

        $this->actingAs($this->admin)->getJson(route('admin.support-links.activity', ['range' => 'today', 'page' => 2]))
            ->assertJsonPath('meta.from', 21)
            ->assertJsonCount(4, 'data');

        $this->actingAs($this->admin)->getJson(route('admin.support-links.activity', ['range' => 'yesterday']))
            ->assertJsonPath('meta.total', 0);
    }

    public function test_country_csv_export_follows_the_selected_filters(): void
    {
        $this->seedClicks();

        $response = $this->actingAs($this->admin)->get(route('admin.support-links.export', ['report' => 'countries', 'range' => 'last_30_days']));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->assertDownload('support-links-countries_2026-10-20_to_2026-11-18.csv');

        $rows = $this->csv($response->streamedContent());

        $this->assertStringStartsWith("\xEF\xBB\xBF", $response->streamedContent());
        $this->assertSame(['Country', 'Country code', 'YouPay clicks', 'Cash App clicks', 'Total clicks', 'Share of clicks (%)', 'From', 'To'], $rows[0]);
        $this->assertSame(['United States', 'US', '3', '4', '7', '70', '2026-10-20', '2026-11-18'], $rows[1]);
        $this->assertSame(['Unknown', '', '0', '1', '1', '10', '2026-10-20', '2026-11-18'], $rows[4]);
        $this->assertCount(5, $rows);
    }

    public function test_daily_csv_export_has_every_day_and_a_total(): void
    {
        $this->seedClicks();

        $response = $this->actingAs($this->admin)->get(route('admin.support-links.export', ['report' => 'daily', 'range' => 'last_7_days']));
        $rows = $this->csv($response->streamedContent());

        $this->assertSame(['Date', 'YouPay clicks', 'Cash App clicks', 'Total clicks', 'Unique visitors'], $rows[0]);
        $this->assertCount(9, $rows);
        $this->assertSame(['2026-11-12', '0', '0', '0', '0'], $rows[1]);
        $this->assertSame(['2026-11-18', '1', '3', '4', '3'], $rows[7]);
        $this->assertSame(['Total 2026-11-12 to 2026-11-18', '3', '4', '7', '5'], $rows[8]);
    }

    public function test_click_csv_export_lists_every_click_and_neutralises_formulas(): void
    {
        SupportClick::factory()->link(SupportPlatform::CashApp)->fromCountry('US')->create(['clicked_at' => Carbon::parse('2026-11-18 09:15:00'), 'page_path' => '=HYPERLINK("https://evil.example")']);
        SupportClick::factory()->link(SupportPlatform::YouPay)->filtered(SupportClickFilter::Duplicate)->create(['clicked_at' => Carbon::parse('2026-11-18 09:16:00'), 'page_path' => '/']);
        SupportClick::factory()->create();

        $response = $this->actingAs($this->admin)->get(route('admin.support-links.export', ['report' => 'clicks', 'range' => 'today']));
        $rows = $this->csv($response->streamedContent());

        $this->assertSame(['Clicked at (UTC)', 'Platform', 'Country', 'Country code', 'Referrer page', 'Visitor type', 'Visitor ID', 'Counted', 'Not counted because'], $rows[0]);
        $this->assertCount(3, $rows);
        $this->assertSame(['2026-11-18 09:15:00', 'Cash App', 'United States', 'US', '\'=HYPERLINK("https://evil.example")', 'Guest'], array_slice($rows[1], 0, 6));
        $this->assertSame(['Yes', ''], array_slice($rows[1], 7));
        $this->assertSame(['No', 'Repeat within seconds'], array_slice($rows[2], 7));
    }

    public function test_report_runs_a_fixed_number_of_queries_however_many_clicks_exist(): void
    {
        $this->seedClicks();
        SupportClick::factory()->link()->fromCountry('DE')->count(150)->create(['clicked_at' => now()->subDays(2)]);

        DB::enableQueryLog();
        $this->report(['range' => 'last_30_days', 'group' => 'week']);
        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertLessThanOrEqual(12, $queries);
    }

    public function test_dashboard_shows_todays_link_clicks_per_platform(): void
    {
        $this->seedClicks();

        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));
        $links = $response->viewData('supportAnalytics')['links'];

        $this->assertSame(4, $links['total']);
        $this->assertSame([['key' => 'youpay', 'label' => 'YouPay', 'clicks' => 1], ['key' => 'cashapp', 'label' => 'Cash App', 'clicks' => 3]], $links['platforms']);
        $response->assertSee('Payment link clicks today');
        $response->assertSee('href="'.route('admin.support-links').'"', false);
        $this->assertSame(5, $response->viewData('supportAnalytics')['stats']['today']);
    }

    public function test_old_clicks_are_pruned_after_the_retention_period(): void
    {
        config(['analytics.support_clicks.retention_days' => 30]);
        SupportClick::factory()->link()->create(['clicked_at' => now()->subDays(31)]);
        SupportClick::factory()->create(['clicked_at' => now()->subDays(40)]);
        $recent = SupportClick::factory()->link()->create(['clicked_at' => now()->subDays(29)]);

        $this->artisan('model:prune', ['--model' => [SupportClick::class]])->assertSuccessful();

        $this->assertSame([$recent->id], SupportClick::pluck('id')->all());
    }

    public function test_retention_of_zero_keeps_every_click(): void
    {
        config(['analytics.support_clicks.retention_days' => 0]);
        SupportClick::factory()->link()->create(['clicked_at' => now()->subYears(3)]);

        $this->artisan('model:prune', ['--model' => [SupportClick::class]])->assertSuccessful();

        $this->assertSame(1, SupportClick::count());
    }

    public function test_reporting_days_follow_the_configured_timezone(): void
    {
        $default = date_default_timezone_get();
        config(['app.timezone' => 'Asia/Kolkata']);
        date_default_timezone_set('Asia/Kolkata');

        try {
            // 00:30 on the 19th in India is still the 18th in UTC.
            $this->travelTo(Carbon::parse('2026-11-19 00:30:00', 'Asia/Kolkata'));
            SupportClick::factory()->link()->create(['clicked_at' => now()->subMinutes(10)]);
            SupportClick::factory()->link()->create(['clicked_at' => now()->subHour()]);

            $report = $this->report(['range' => 'today']);

            $this->assertSame(['2026-11-19', 'Asia/Kolkata'], [$report['range']['from'], $report['range']['timezone']]);
            $this->assertSame(1, $report['totals']['clicks']);
        } finally {
            date_default_timezone_set($default);
        }
    }
}
