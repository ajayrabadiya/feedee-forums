<?php

namespace Tests\Feature;

use App\Models\SupportClick;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminSupportAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // Wednesday, so "this month" and "last 7 days" cover different days.
        $this->travelTo(Carbon::parse('2026-11-18 12:00:00'));
        $this->admin = User::factory()->admin()->create();
    }

    /**
     * Today ×2, 3 days ago, 10 days ago (this month, not last 7 days) and 40 days ago (October 9).
     */
    private function seedClicks(): void
    {
        SupportClick::factory()->count(2)->create(['clicked_at' => now()->subHour(), 'page_path' => '/']);
        SupportClick::factory()->create(['clicked_at' => now()->subDays(3), 'page_path' => '/rules']);
        SupportClick::factory()->byMember()->create(['clicked_at' => now()->subDays(10), 'page_path' => '/']);
        SupportClick::factory()->create(['clicked_at' => now()->subDays(40), 'page_path' => '/contact']);
    }

    public function test_guests_cannot_view_support_analytics(): void
    {
        $this->getJson(route('admin.support-analytics'))->assertUnauthorized();
        $this->get(route('admin.support-analytics'))->assertRedirect(route('login'));
    }

    public function test_customers_cannot_view_support_analytics(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer)->getJson(route('admin.support-analytics'))->assertForbidden();
        $this->actingAs($customer)->get(route('admin.dashboard'))->assertForbidden()->assertDontSee('Support analytics');
    }

    public function test_dashboard_shows_support_click_stat_cards(): void
    {
        $this->seedClicks();

        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertOk();
        $stats = $response->viewData('supportAnalytics')['stats'];

        $this->assertSame(['total' => 5, 'today' => 2, 'last7Days' => 3, 'thisMonth' => 4], $stats);
        $response->assertSee('Support analytics');
        $response->assertSeeInOrder(['Total clicks (all time)', "Today's clicks", 'Last 7 days', 'This month'], false);
        $response->assertSee('data-support-stat="thisMonth">4<', false);
    }

    public function test_dashboard_renders_filters_and_live_refresh_settings(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertSee('data-url="'.route('admin.support-analytics').'"', false);
        $response->assertSee('data-poll="'.SupportClick::POLL_SECONDS.'"', false);
        $response->assertSee('data-support-initial', false);
        $response->assertSee('data-group="week"', false);
        $response->assertSee('max="2026-11-18"', false);

        foreach (['today', 'last_7_days', 'last_30_days', 'this_month', 'custom'] as $range) {
            $response->assertSee('data-range="'.$range.'"', false);
        }
    }

    public function test_empty_analytics_render_without_errors(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('No Support clicks in this period yet.');
        $this->assertSame(0, $response->viewData('supportAnalytics')['stats']['total']);

        $this->actingAs($this->admin)->getJson(route('admin.support-analytics'))
            ->assertOk()
            ->assertJsonPath('range.clicks', 0)
            ->assertJsonCount(7, 'chart')
            ->assertJsonPath('topPages', []);
    }

    /**
     * @return array<string, array{string, int, int, string}>
     */
    public static function presetRangeProvider(): array
    {
        return [
            'today' => ['today', 1, 2, '2026-11-18'],
            'last 7 days' => ['last_7_days', 7, 3, '2026-11-12'],
            'last 30 days' => ['last_30_days', 30, 4, '2026-10-20'],
            'this month' => ['this_month', 18, 4, '2026-11-01'],
        ];
    }

    #[DataProvider('presetRangeProvider')]
    public function test_preset_ranges_return_a_daily_chart(string $range, int $bars, int $clicks, string $from): void
    {
        $this->seedClicks();

        $response = $this->actingAs($this->admin)->getJson(route('admin.support-analytics', ['range' => $range]));

        $response->assertOk()
            ->assertJsonPath('range.key', $range)
            ->assertJsonPath('range.from', $from)
            ->assertJsonPath('range.to', '2026-11-18')
            ->assertJsonPath('range.clicks', $clicks)
            ->assertJsonPath('group', 'day')
            ->assertJsonCount($bars, 'chart')
            ->assertJsonPath('stats.total', 5);

        $this->assertSame($clicks, collect($response->json('chart'))->sum('clicks'));
        $this->assertSame(['label' => 'Today', 'title' => 'Wed, Nov 18, 2026', 'clicks' => 2], $response->json('chart.'.($bars - 1)));
    }

    public function test_daily_chart_places_clicks_on_the_right_day(): void
    {
        $this->seedClicks();

        $chart = $this->actingAs($this->admin)->getJson(route('admin.support-analytics', ['range' => 'last_7_days']))->json('chart');

        $this->assertSame(['Thu', 'Fri', 'Sat', 'Sun', 'Mon', 'Tue', 'Today'], array_column($chart, 'label'));
        $this->assertSame([0, 0, 0, 1, 0, 0, 2], array_column($chart, 'clicks'));
    }

    public function test_weekly_chart_groups_clicks_into_monday_weeks(): void
    {
        $this->seedClicks();

        $response = $this->actingAs($this->admin)->getJson(route('admin.support-analytics', ['range' => 'last_30_days', 'group' => 'week']));

        $response->assertOk()->assertJsonPath('group', 'week')->assertJsonPath('range.clicks', 4);
        $this->assertSame([
            ['label' => 'Oct 20', 'title' => 'Oct 20 – Oct 25, 2026', 'clicks' => 0],
            ['label' => 'Oct 26', 'title' => 'Oct 26 – Nov 1, 2026', 'clicks' => 0],
            ['label' => 'Nov 2', 'title' => 'Nov 2 – Nov 8, 2026', 'clicks' => 1],
            ['label' => 'Nov 9', 'title' => 'Nov 9 – Nov 15, 2026', 'clicks' => 1],
            ['label' => 'Nov 16', 'title' => 'Nov 16 – Nov 18, 2026', 'clicks' => 2],
        ], $response->json('chart'));
    }

    public function test_custom_range_uses_the_given_dates(): void
    {
        $this->seedClicks();

        $response = $this->actingAs($this->admin)->getJson(route('admin.support-analytics', [
            'range' => 'custom',
            'from' => '2026-10-01',
            'to' => '2026-10-31',
        ]));

        $response->assertOk()
            ->assertJsonPath('range.key', 'custom')
            ->assertJsonPath('range.label', 'Custom range')
            ->assertJsonPath('range.from', '2026-10-01')
            ->assertJsonPath('range.to', '2026-10-31')
            ->assertJsonPath('range.clicks', 1)
            ->assertJsonCount(31, 'chart')
            ->assertJsonPath('chart.8', ['label' => 'Oct 9', 'title' => 'Fri, Oct 9, 2026', 'clicks' => 1])
            ->assertJsonPath('topPages', [['path' => '/contact', 'clicks' => 1]]);
    }

    public function test_custom_range_may_span_a_full_year(): void
    {
        $this->actingAs($this->admin)->getJson(route('admin.support-analytics', [
            'range' => 'custom',
            'from' => '2025-11-18',
            'to' => '2026-11-18',
            'group' => 'week',
        ]))->assertOk()->assertJsonCount(53, 'chart');
    }

    /**
     * @return array<string, array{array<string, string>, string}>
     */
    public static function invalidFilterProvider(): array
    {
        return [
            'unknown range' => [['range' => 'forever'], 'range'],
            'unknown group' => [['group' => 'hour'], 'group'],
            'custom without dates' => [['range' => 'custom'], 'from'],
            'custom without end date' => [['range' => 'custom', 'from' => '2026-11-01'], 'to'],
            'end before start' => [['range' => 'custom', 'from' => '2026-11-10', 'to' => '2026-11-01'], 'to'],
            'future end date' => [['range' => 'custom', 'from' => '2026-11-10', 'to' => '2026-11-19'], 'to'],
            'wrong date format' => [['range' => 'custom', 'from' => '11/01/2026', 'to' => '2026-11-10'], 'from'],
            'range longer than a year' => [['range' => 'custom', 'from' => '2025-11-17', 'to' => '2026-11-18'], 'to'],
        ];
    }

    /**
     * @param  array<string, string>  $query
     */
    #[DataProvider('invalidFilterProvider')]
    public function test_invalid_filters_are_rejected(array $query, string $field): void
    {
        $this->actingAs($this->admin)
            ->getJson(route('admin.support-analytics', $query))
            ->assertUnprocessable()
            ->assertJsonValidationErrors($field);
    }

    public function test_top_pages_are_ranked_by_clicks(): void
    {
        SupportClick::factory()->count(3)->create(['page_path' => '/p/abc123']);
        SupportClick::factory()->count(2)->create(['page_path' => '/']);
        SupportClick::factory()->create(['page_path' => '/rules']);
        SupportClick::factory()->create(['page_path' => null]);
        SupportClick::factory()->create(['page_path' => '/old', 'clicked_at' => now()->subDays(8)]);

        $this->actingAs($this->admin)->getJson(route('admin.support-analytics', ['range' => 'today']))
            ->assertOk()
            ->assertJsonPath('range.clicks', 7)
            ->assertJsonPath('topPages', [
                ['path' => '/p/abc123', 'clicks' => 3],
                ['path' => '/', 'clicks' => 2],
                ['path' => '/rules', 'clicks' => 1],
            ]);
    }

    public function test_today_starts_at_midnight(): void
    {
        SupportClick::factory()->create(['clicked_at' => Carbon::parse('2026-11-17 23:59:59')]);
        SupportClick::factory()->create(['clicked_at' => Carbon::parse('2026-11-18 00:00:00')]);

        $this->actingAs($this->admin)->getJson(route('admin.support-analytics', ['range' => 'today']))
            ->assertJsonPath('stats.today', 1)
            ->assertJsonPath('stats.last7Days', 2)
            ->assertJsonPath('range.clicks', 1);
    }

    public function test_admin_clicks_never_reach_the_dashboard(): void
    {
        $this->actingAs($this->admin)->postJson(route('support.click'), ['page_path' => '/'])->assertNoContent();
        $this->actingAs(User::factory()->create())->postJson(route('support.click'), ['page_path' => '/'])->assertNoContent();

        $this->actingAs($this->admin)->getJson(route('admin.support-analytics', ['range' => 'today']))
            ->assertJsonPath('stats.total', 1)
            ->assertJsonPath('range.clicks', 1);
    }

    public function test_polling_reflects_new_clicks_without_a_page_reload(): void
    {
        $this->actingAs($this->admin)->getJson(route('admin.support-analytics'))->assertJsonPath('stats.today', 0);

        auth()->logout();
        $this->postJson(route('support.click'), ['page_path' => '/'])->assertNoContent();

        $this->actingAs($this->admin)->getJson(route('admin.support-analytics'))
            ->assertJsonPath('stats.today', 1)
            ->assertJsonPath('updatedAt', now()->toIso8601String());
    }
}
