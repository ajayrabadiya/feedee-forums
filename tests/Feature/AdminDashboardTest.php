<?php

namespace Tests\Feature;

use App\Models\ContactMessage;
use App\Models\Post;
use App\Models\PostMedia;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(config('media.disk'));
        $this->admin = User::factory()->admin()->create(['created_at' => now()->subMonth()]);
    }

    public function test_stat_cards_use_real_counts(): void
    {
        User::factory()->count(2)->create(['created_at' => now()->subDays(2)]);
        User::factory()->create(['created_at' => now()->subDays(20)]);

        Post::factory()->pending()->count(2)->create();
        Post::factory()->pending()->create(['created_at' => now()->subDays(2)]);

        Post::factory()->create(['moderated_at' => now(), 'created_at' => now()->subDays(3)]);
        Post::factory()->create(['created_at' => now()]);
        Post::factory()->create(['moderated_at' => now()->subDay()->setTime(12, 0), 'created_at' => now()->subDays(2)]);

        ContactMessage::factory()->count(2)->create();
        ContactMessage::factory()->read()->create();
        ContactMessage::factory()->create(['created_at' => now()->subDays(3)]);

        $stats = $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertOk()->viewData('stats');

        $this->assertSame(3, $stats['pending']);
        $this->assertSame(2, $stats['pendingToday']);
        $this->assertSame(3, $stats['unreadMessages']);
        $this->assertSame(3, $stats['messagesToday']);
        $this->assertSame(2, $stats['approvedToday']);
        $this->assertSame(1, $stats['approvedYesterday']);
        $this->assertSame(3 + 6, $stats['members']);
        $this->assertSame(2 + 6, $stats['membersThisWeek']);
    }

    public function test_chart_counts_submitted_and_approved_per_day(): void
    {
        Post::factory()->count(3)->create(['created_at' => now()]);
        Post::factory()->pending()->create(['created_at' => now()]);
        Post::factory()->create(['created_at' => now()->subDays(2), 'moderated_at' => now()->subDay()]);
        Post::factory()->create(['created_at' => now()->subDays(10)]);

        $chart = $this->actingAs($this->admin)->get(route('admin.dashboard'))->viewData('chart');

        $this->assertCount(7, $chart);
        $this->assertSame('Today', $chart->last()['label']);
        $this->assertSame(4, $chart->last()['submitted']);
        $this->assertSame(3, $chart->last()['approved']);
        $this->assertSame(90, $chart->last()['submittedHeight']);
        $this->assertSame(1, $chart[4]['submitted']);
        $this->assertSame(1, $chart[5]['approved']);
        $this->assertSame(5, $chart->sum('submitted'));
    }

    public function test_waiting_for_review_lists_oldest_pending_posts_with_working_actions(): void
    {
        $author = User::factory()->create(['name' => 'maya_bakes']);
        $oldest = Post::factory()->for($author)->pending()->create(['title' => 'Oldest pending', 'created_at' => now()->subHours(5)]);
        $newest = Post::factory()->pending()->create(['title' => 'Newest pending', 'created_at' => now()->subMinutes(5)]);
        Post::factory()->create(['title' => 'Already approved']);
        $image = PostMedia::factory()->for($oldest)->create();

        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertSeeInOrder(['Oldest pending', 'Newest pending']);
        $response->assertDontSee('Already approved');
        $response->assertSee('maya_bakes');
        $response->assertSee('data-id="'.$oldest->id.'" data-status="pending"', false);
        $response->assertSee('data-moderate-url="'.route('admin.posts.moderate').'"', false);
        $response->assertSee(Storage::disk(config('media.disk'))->url($image->file_path), false);
        $response->assertSee('href="'.route('posts.show', $newest).'"', false);
        $response->assertDontSee('picsum.photos/seed/ff-q', false);
    }

    public function test_latest_contact_messages_replace_fake_reports(): void
    {
        $message = ContactMessage::factory()->create(['subject' => 'Rejected post appeal', 'name' => 'Lena']);

        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertSee('Latest contact messages');
        $response->assertSee('Rejected post appeal');
        $response->assertSee('href="'.route('admin.contacts.show', $message).'"', false);
        $response->assertDontSee('Latest reports');
        $response->assertDontSee('Free followers, click the link in my profile');
        $response->assertDontSee('128,402');
    }

    public function test_empty_dashboard_renders_without_errors(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('No messages yet.');
        $response->assertSee('nothing pending');
    }
}
