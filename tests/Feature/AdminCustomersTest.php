<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCustomersTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sees_every_customer_with_post_counts(): void
    {
        $admin = User::factory()->admin()->create(['name' => 'Site Admin', 'email' => 'admin@example.com']);
        $maya = User::factory()->create(['name' => 'Maya Baker', 'username' => 'maya_bakes', 'email' => 'maya@example.com']);
        $quiet = User::factory()->create(['name' => 'Quiet Reader', 'username' => 'quiet_one']);
        Post::factory()->for($maya)->count(2)->create();
        Post::factory()->for($maya)->pending()->create();
        Post::factory()->for($maya)->rejected()->create();

        $response = $this->actingAs($admin)->get(route('admin.customers'));

        $response->assertOk();
        $response->assertSee('Maya Baker');
        $response->assertSee('@maya_bakes');
        $response->assertSee('maya@example.com');
        $response->assertSee('2 approved');
        $response->assertSee('1 pending');
        $response->assertSee('1 rejected');
        $response->assertSee('Quiet Reader');
        $response->assertSee('Never');
        $response->assertDontSee('admin@example.com');
        $response->assertSee('data-status="active" data-id="'.$maya->id.'"', false);
        $response->assertSee('data-status="inactive" data-id="'.$quiet->id.'"', false);
        $response->assertSee(e(route('admin.posts', ['q' => 'Maya Baker'])), false);
    }

    public function test_stats_reflect_real_data(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->create(['created_at' => now()->subDays(20)]);
        $recent = User::factory()->create(['created_at' => now()->subDay()]);
        Post::factory()->for($recent)->pending()->count(2)->create();
        Post::factory()->for($admin)->pending()->create();

        $stats = $this->actingAs($admin)->get(route('admin.customers'))->viewData('stats');

        $this->assertSame(['total' => 2, 'joinedThisWeek' => 1, 'active' => 1, 'pendingPosts' => 2], $stats);
    }

    public function test_customers_with_only_old_posts_count_as_inactive(): void
    {
        $customer = User::factory()->create();
        Post::factory()->for($customer)->create(['created_at' => now()->subDays(45)]);

        $this->actingAs(User::factory()->admin()->create())->get(route('admin.customers'))
            ->assertSee('data-status="inactive" data-id="'.$customer->id.'"', false);
    }

    public function test_customer_details_are_escaped(): void
    {
        User::factory()->create(['name' => '<script>alert(1)</script>']);

        $this->actingAs(User::factory()->admin()->create())->get(route('admin.customers'))
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
    }

    public function test_sidebar_links_to_customers(): void
    {
        $this->actingAs(User::factory()->admin()->create())->get(route('admin.dashboard'))
            ->assertSee('href="'.route('admin.customers').'"', false);
    }

    public function test_posts_page_prefills_search_from_query(): void
    {
        $this->actingAs(User::factory()->admin()->create())->get(route('admin.posts', ['q' => 'Maya Baker']))
            ->assertSee('class="table-search" value="Maya Baker"', false);
    }

    public function test_customers_cannot_view_the_customer_list(): void
    {
        $this->actingAs(User::factory()->create())->get(route('admin.customers'))->assertForbidden();
    }

    public function test_guests_are_sent_to_login(): void
    {
        $this->get(route('admin.customers'))->assertRedirect(route('login'));
    }
}
