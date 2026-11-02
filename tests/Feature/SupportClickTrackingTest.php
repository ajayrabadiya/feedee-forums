<?php

namespace Tests\Feature;

use App\Models\SupportClick;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SupportClickTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_click_is_stored_with_a_timestamp(): void
    {
        $this->travelTo(Carbon::parse('2026-11-18 14:30:15'));

        $this->postJson(route('support.click'), ['page_path' => '/rules'], ['User-Agent' => 'FeedeeTestBrowser/1.0'])
            ->assertNoContent();

        $click = SupportClick::sole();

        $this->assertNull($click->user_id);
        $this->assertSame('/rules', $click->page_path);
        $this->assertSame('127.0.0.1', $click->ip_address);
        $this->assertSame('FeedeeTestBrowser/1.0', $click->user_agent);
        $this->assertSame('2026-11-18 14:30:15', $click->clicked_at->toDateTimeString());
    }

    public function test_member_click_is_linked_to_the_member(): void
    {
        $member = User::factory()->create();

        $this->actingAs($member)->postJson(route('support.click'), ['page_path' => '/'])->assertNoContent();

        $this->assertDatabaseHas('support_clicks', ['user_id' => $member->id, 'page_path' => '/']);
    }

    public function test_every_click_is_stored_as_its_own_event(): void
    {
        $this->postJson(route('support.click'), ['page_path' => '/']);
        $this->postJson(route('support.click'), ['page_path' => '/']);
        $this->postJson(route('support.click'), ['page_path' => '/contact']);

        $this->assertSame(3, SupportClick::count());
    }

    public function test_page_path_is_optional(): void
    {
        $this->postJson(route('support.click'))->assertNoContent();

        $this->assertNull(SupportClick::sole()->page_path);
    }

    public function test_admin_clicks_are_not_recorded(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->postJson(route('support.click'), ['page_path' => '/'])->assertNoContent();

        $this->assertSame(0, SupportClick::count());
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function invalidPagePathProvider(): array
    {
        return [
            'absolute url' => ['https://evil.example/'],
            'protocol-relative url' => ['//evil.example/path'],
            'backslash trick' => ['/\\evil.example'],
            'javascript scheme' => ['javascript:alert(1)'],
            'whitespace' => ['/p/abc def'],
            'control character' => ["/p/a\x01bc"],
            'too long' => ['/'.str_repeat('a', 255)],
            'not a string' => [['/']],
        ];
    }

    #[DataProvider('invalidPagePathProvider')]
    public function test_invalid_page_paths_are_rejected(mixed $pagePath): void
    {
        $this->postJson(route('support.click'), ['page_path' => $pagePath])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('page_path');

        $this->assertSame(0, SupportClick::count());
    }

    public function test_click_endpoint_only_accepts_post(): void
    {
        $this->get('/support/click')->assertMethodNotAllowed();
    }

    public function test_click_endpoint_is_rate_limited(): void
    {
        for ($i = 0; $i < 30; $i++) {
            $this->postJson(route('support.click'), ['page_path' => '/'])->assertNoContent();
        }

        $this->postJson(route('support.click'), ['page_path' => '/'])->assertTooManyRequests();

        $this->assertSame(30, SupportClick::count());
    }

    public function test_support_button_reports_clicks_for_visitors_and_members(): void
    {
        $trackingAttribute = 'class="support-fab" data-track-url="'.route('support.click').'"';

        $this->get(route('home'))->assertSee($trackingAttribute, false);
        $this->actingAs(User::factory()->create())->get(route('rules'))->assertSee($trackingAttribute, false);
    }

    public function test_support_button_does_not_report_clicks_for_admins(): void
    {
        $response = $this->actingAs(User::factory()->admin()->create())->get(route('home'));

        $response->assertSee('class="support-fab"', false);
        $response->assertSee('class="support-fab-btn"', false);
        $response->assertDontSee('data-track-url', false);
    }
}
