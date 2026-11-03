<?php

namespace Tests\Feature;

use App\Enums\SupportClickFilter;
use App\Enums\SupportPlatform;
use App\Models\Setting;
use App\Models\SupportClick;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SupportLinkTrackingTest extends TestCase
{
    use RefreshDatabase;

    private const BROWSER = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0 Safari/537.36';

    protected function setUp(): void
    {
        parent::setUp();

        $this->withHeader('User-Agent', self::BROWSER);
    }

    public function test_cash_app_click_is_recorded_before_redirecting(): void
    {
        $this->travelTo(Carbon::parse('2026-11-18 14:30:15'));

        $response = $this->get(route('support.go', 'cashapp'), ['Referer' => url('/p/abc123?utm=x')]);

        $response->assertRedirect('https://cash.app/$zaiyaxx');
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertSame('noindex, nofollow', $response->headers->get('X-Robots-Tag'));

        $click = SupportClick::sole();
        $this->assertSame(SupportPlatform::CashApp, $click->platform);
        $this->assertSame('https://cash.app/$zaiyaxx', $click->support_url);
        $this->assertSame('/p/abc123', $click->page_path);
        $this->assertNull($click->filtered_as);
        $this->assertNull($click->user_id);
        $this->assertNull($click->country_code);
        $this->assertSame('Unknown', $click->country_name);
        $this->assertSame(hash_hmac('sha256', 'guest:127.0.0.1|'.self::BROWSER, config('app.key')), $click->visitor_hash);
        $this->assertSame('2026-11-18 14:30:15', $click->clicked_at->toDateTimeString());
    }

    public function test_youpay_click_is_recorded_separately(): void
    {
        $this->get(route('support.go', 'youpay'))->assertRedirect('https://youpay.me/zaiyaxx');
        $this->get(route('support.go', 'cashapp'))->assertRedirect('https://cash.app/$zaiyaxx');

        $this->assertSame(1, SupportClick::where('platform', 'youpay')->count());
        $this->assertSame(1, SupportClick::where('platform', 'cashapp')->count());
        $this->assertSame('https://youpay.me/zaiyaxx', SupportClick::where('platform', 'youpay')->sole()->support_url);
    }

    public function test_support_button_links_point_to_the_tracking_route(): void
    {
        $response = $this->get(route('home'));

        $response->assertSee('href="'.route('support.go', ['platform' => 'youpay', 'from' => '/']).'" target="_blank" rel="noopener nofollow"', false);
        $response->assertSee('href="'.route('support.go', ['platform' => 'cashapp', 'from' => '/']).'" target="_blank" rel="noopener nofollow"', false);
        $response->assertSee('class="support-fab-btn"', false);

        $this->get(route('rules'))->assertSee(route('support.go', ['platform' => 'cashapp', 'from' => '/rules']), false);
    }

    public function test_page_the_link_was_clicked_on_is_recorded_without_relying_on_the_referer(): void
    {
        $this->get(route('support.go', ['platform' => 'cashapp', 'from' => '/rules']))->assertRedirect('https://cash.app/$zaiyaxx');
        $this->get(route('support.go', ['platform' => 'youpay', 'from' => '/contact']), ['Referer' => url('/p/other')]);
        $this->get(route('support.go', ['platform' => 'cashapp', 'from' => '//evil.example']), ['Referer' => url('/rules')]);
        $this->get(route('support.go', ['platform' => 'youpay', 'from' => 'https://evil.example/']));

        $this->assertSame(['/rules', '/contact', null, null], SupportClick::orderBy('id')->pluck('page_path')->all());
    }

    public function test_redirect_target_only_comes_from_the_admin_settings(): void
    {
        $this->get(route('support.go', ['platform' => 'cashapp', 'url' => 'https://evil.example', 'redirect' => '//evil.example']))
            ->assertRedirect('https://cash.app/$zaiyaxx');

        Setting::set('support.cashapp_url', 'https://cash.app/$newname');

        $this->get(route('support.go', 'cashapp'))->assertRedirect('https://cash.app/$newname');
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidPlatformProvider(): array
    {
        return [
            'unknown platform' => ['/go/paypal'],
            'wrong case' => ['/go/CASHAPP'],
            'url as platform' => ['/go/https%3A%2F%2Fevil.example'],
            'no platform' => ['/go/'],
        ];
    }

    #[DataProvider('invalidPlatformProvider')]
    public function test_invalid_platforms_are_not_found_and_not_recorded(string $uri): void
    {
        $this->get($uri)->assertNotFound();

        $this->assertSame(0, SupportClick::count());
    }

    public function test_links_are_not_found_when_the_support_button_is_off_or_the_link_is_empty(): void
    {
        Setting::set('support.youpay_url', '');
        $this->get(route('support.go', 'youpay'))->assertNotFound();

        Setting::set('support.enabled', '0');
        $this->get(route('support.go', 'cashapp'))->assertNotFound();

        $this->assertSame(0, SupportClick::count());
    }

    public function test_links_that_are_not_https_are_never_followed(): void
    {
        Setting::set('support.cashapp_url', 'javascript:alert(1)');
        $this->get(route('support.go', 'cashapp'))->assertNotFound();

        Setting::set('support.cashapp_url', 'http://cash.app/$insecure');
        $this->get(route('support.go', 'cashapp'))->assertNotFound();

        $this->assertSame(0, SupportClick::count());
    }

    public function test_member_clicks_are_linked_to_the_member(): void
    {
        $member = User::factory()->create();

        $this->actingAs($member)->get(route('support.go', 'youpay'))->assertRedirect('https://youpay.me/zaiyaxx');

        $click = SupportClick::sole();
        $this->assertSame($member->id, $click->user_id);
        $this->assertSame(hash_hmac('sha256', 'member:'.$member->id, config('app.key')), $click->visitor_hash);
    }

    public function test_admin_clicks_redirect_without_being_recorded(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('support.go', 'cashapp'))
            ->assertRedirect('https://cash.app/$zaiyaxx');

        $this->assertSame(0, SupportClick::count());
    }

    public function test_referrers_from_other_sites_or_unsafe_paths_are_not_stored(): void
    {
        $this->get(route('support.go', 'cashapp'), ['Referer' => 'https://other.example/page']);
        $this->get(route('support.go', 'youpay'), ['Referer' => url('//evil.example/x')]);

        $this->assertSame([null, null], SupportClick::orderBy('id')->pluck('page_path')->all());
    }

    public function test_a_quick_repeat_click_is_kept_but_marked_as_a_duplicate(): void
    {
        $this->travelTo(Carbon::parse('2026-11-18 14:00:00'));

        $this->get(route('support.go', 'cashapp'))->assertRedirect('https://cash.app/$zaiyaxx');
        $this->get(route('support.go', 'cashapp'))->assertRedirect('https://cash.app/$zaiyaxx');
        $this->get(route('support.go', 'youpay'))->assertRedirect('https://youpay.me/zaiyaxx');

        $this->travel(11)->seconds();
        $this->get(route('support.go', 'cashapp'))->assertRedirect('https://cash.app/$zaiyaxx');

        $this->assertSame(
            [null, SupportClickFilter::Duplicate, null, null],
            SupportClick::orderBy('id')->get()->pluck('filtered_as')->all(),
        );
        $this->assertSame(3, SupportClick::counted()->count());
    }

    public function test_different_visitors_are_never_duplicates_of_each_other(): void
    {
        $this->get(route('support.go', 'cashapp'));
        $this->get(route('support.go', 'cashapp'), ['User-Agent' => self::BROWSER.' Edg/130.0']);

        $this->assertSame(2, SupportClick::counted()->count());
    }

    /**
     * @return array<string, array{string}>
     */
    public static function botUserAgentProvider(): array
    {
        return [
            'search engine' => ['Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)'],
            'link preview' => ['facebookexternalhit/1.1'],
            'script' => ['curl/8.4.0'],
            'headless browser' => ['Mozilla/5.0 HeadlessChrome/120.0'],
            'no user agent' => [''],
        ];
    }

    #[DataProvider('botUserAgentProvider')]
    public function test_bot_clicks_are_kept_apart_from_the_counts(string $userAgent): void
    {
        $this->get(route('support.go', 'cashapp'), ['User-Agent' => $userAgent])->assertRedirect('https://cash.app/$zaiyaxx');

        $this->assertSame(SupportClickFilter::Bot, SupportClick::sole()->filtered_as);
        $this->assertSame(0, SupportClick::counted()->count());
    }

    public function test_prefetches_and_head_requests_are_not_recorded(): void
    {
        $this->get(route('support.go', 'cashapp'), ['Sec-Purpose' => 'prefetch'])->assertRedirect('https://cash.app/$zaiyaxx');
        $this->get(route('support.go', 'cashapp'), ['Purpose' => 'prefetch'])->assertRedirect('https://cash.app/$zaiyaxx');
        $this->call('HEAD', route('support.go', 'cashapp'))->assertRedirect('https://cash.app/$zaiyaxx');

        $this->assertSame(0, SupportClick::count());
    }

    public function test_visitors_over_the_rate_limit_are_still_redirected(): void
    {
        config(['analytics.support_clicks.rate_limit' => 3, 'analytics.support_clicks.duplicate_window' => 0]);

        for ($i = 0; $i < 5; $i++) {
            $this->get(route('support.go', 'cashapp'))->assertRedirect('https://cash.app/$zaiyaxx');
        }

        $this->assertSame(3, SupportClick::count());
    }

    public function test_a_tracking_failure_never_blocks_the_redirect(): void
    {
        Exceptions::fake();
        Schema::drop('support_clicks');

        $this->get(route('support.go', 'youpay'))->assertRedirect('https://youpay.me/zaiyaxx');

        Exceptions::assertReported(QueryException::class);
    }
}
