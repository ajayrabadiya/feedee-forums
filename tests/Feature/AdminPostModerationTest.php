<?php

namespace Tests\Feature;

use App\Enums\PostStatus;
use App\Models\Post;
use App\Models\PostMedia;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\PostRejected;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminPostModerationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(config('media.disk'));
        $this->admin = User::factory()->admin()->create();
    }

    private function moderate(array $payload, ?User $as = null): TestResponse
    {
        return $this->actingAs($as ?? $this->admin)->postJson(route('admin.posts.moderate'), $payload);
    }

    public function test_posts_page_lists_real_posts_with_status_and_media(): void
    {
        $author = User::factory()->create(['name' => 'maya_bakes']);
        $approved = Post::factory()->for($author)->create(['title' => 'Approved cinnamon rolls']);
        $pending = Post::factory()->pending()->create(['title' => 'Waiting pumpkin soup']);
        $rejected = Post::factory()->rejected()->create(['title' => 'Spammy shop link']);
        $featured = Post::factory()->featured()->create(['title' => 'Featured welcome']);
        $image = PostMedia::factory()->for($approved)->create();

        $response = $this->actingAs($this->admin)->get(route('admin.posts'));

        $response->assertOk();
        foreach ([$approved, $pending, $rejected, $featured] as $post) {
            $response->assertSee('data-id="'.$post->id.'" data-status="'.$post->status->value.'"', false);
            $response->assertSee($post->title);
            $response->assertSee('#'.$post->public_id);
        }
        $response->assertSee('maya_bakes');
        $response->assertSee(Storage::disk(config('media.disk'))->url($image->file_path), false);
        $response->assertSee('data-moderate-url="'.route('admin.posts.moderate').'"', false);
        $response->assertSee('<span class="status st-pending"', false);
        $response->assertSee('data-featured="1"', false);
        $response->assertDontSee('Homemade pumpkin soup');
        $response->assertDontSee('Rt6uB2nQ');
        $response->assertSee('data-tab="pending" role="tab" aria-selected="true"', false);
    }

    public function test_sidebar_shows_real_pending_count(): void
    {
        Post::factory()->pending()->count(3)->create();

        $content = $this->actingAs($this->admin)->get(route('admin.dashboard'))->getContent();

        $this->assertMatchesRegularExpression('#data-pending-count title="Posts waiting for review"\s*>3</span>#', $content);
    }

    public function test_admin_can_approve_posts(): void
    {
        $post = Post::factory()->pending()->create();

        $this->moderate(['action' => 'approve', 'ids' => [$post->id]])
            ->assertOk()
            ->assertJsonPath('posts.0.status', 'approved')
            ->assertJsonPath('counts.pending', 0);

        $post->refresh();
        $this->assertSame(PostStatus::Approved, $post->status);
        $this->assertSame($this->admin->id, $post->moderated_by);
        $this->assertNotNull($post->moderated_at);
    }

    public function test_admin_can_reject_with_reason_and_author_is_emailed(): void
    {
        Notification::fake();
        $author = User::factory()->create();
        $post = Post::factory()->for($author)->featured()->create();

        $this->moderate([
            'action' => 'reject',
            'ids' => [$post->id],
            'reason' => 'Spam or self-promotion',
            'note' => 'Please drop the shop link.',
        ])->assertOk()->assertJsonPath('posts.0.status', 'rejected')->assertJsonPath('posts.0.featured', false);

        $post->refresh();
        $this->assertSame(PostStatus::Rejected, $post->status);
        $this->assertFalse($post->is_featured);
        $this->assertSame('Spam or self-promotion', $post->rejection_reason);
        $this->assertSame('Please drop the shop link.', $post->rejection_note);

        Notification::assertSentTo($author, PostRejected::class, function (PostRejected $notification) use ($author, $post) {
            $mail = $notification->toMail($author);

            return $notification->post->is($post)
                && str_contains(implode(' ', $mail->introLines), 'Spam or self-promotion')
                && str_contains(implode(' ', $mail->introLines), 'Please drop the shop link.');
        });
    }

    public function test_reject_requires_a_listed_reason(): void
    {
        $post = Post::factory()->create();

        $this->moderate(['action' => 'reject', 'ids' => [$post->id]])->assertJsonValidationErrors('reason');
        $this->moderate(['action' => 'reject', 'ids' => [$post->id], 'reason' => 'Made up reason'])->assertJsonValidationErrors('reason');
        $this->moderate(['action' => 'reject', 'ids' => [$post->id], 'reason' => 'Off topic', 'note' => str_repeat('a', 501)])->assertJsonValidationErrors('note');

        $this->assertSame(PostStatus::Approved, $post->fresh()->status);
    }

    public function test_feature_approves_and_pins_post_and_unfeature_removes_it(): void
    {
        $post = Post::factory()->pending()->create();

        $this->moderate(['action' => 'feature', 'ids' => [$post->id]])
            ->assertOk()
            ->assertJsonPath('posts.0.status', 'approved')
            ->assertJsonPath('posts.0.featured', true);

        $post->refresh();
        $this->assertTrue($post->is_featured);
        $this->assertNotNull($post->featured_at);
        $this->assertSame(PostStatus::Approved, $post->status);

        $this->moderate(['action' => 'unfeature', 'ids' => [$post->id]])->assertJsonPath('posts.0.featured', false);
        $this->assertFalse($post->fresh()->is_featured);
    }

    public function test_admin_can_delete_posts_and_their_files(): void
    {
        $post = Post::factory()->create();
        $media = PostMedia::factory()->for($post)->create();
        Storage::disk(config('media.disk'))->put($media->file_path, 'bytes');

        $this->moderate(['action' => 'delete', 'ids' => [$post->id]])->assertOk()->assertJsonPath('posts', []);

        $this->assertModelMissing($post);
        $this->assertModelMissing($media);
        Storage::disk(config('media.disk'))->assertMissing($media->file_path);
    }

    public function test_bulk_actions_apply_to_every_selected_post(): void
    {
        $posts = Post::factory()->pending()->count(3)->create();
        $untouched = Post::factory()->pending()->create();

        $this->moderate(['action' => 'approve', 'ids' => $posts->modelKeys()])
            ->assertOk()
            ->assertJsonCount(3, 'posts')
            ->assertJsonPath('message', '3 posts approved and published to the feed.')
            ->assertJsonPath('counts.pending', 1);

        $this->assertSame(3, Post::where('status', PostStatus::Approved)->count());
        $this->assertSame(PostStatus::Pending, $untouched->fresh()->status);
    }

    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    public static function invalidPayloadProvider(): array
    {
        return [
            'unknown action' => [['action' => 'publish', 'ids' => [1]], 'action'],
            'missing ids' => [['action' => 'approve'], 'ids'],
            'empty ids' => [['action' => 'approve', 'ids' => []], 'ids'],
            'non numeric id' => [['action' => 'approve', 'ids' => ['abc']], 'ids.0'],
            'missing post' => [['action' => 'approve', 'ids' => [999999]], 'ids.0'],
            'duplicate ids' => [['action' => 'approve', 'ids' => [1, 1]], 'ids.0'],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    #[DataProvider('invalidPayloadProvider')]
    public function test_invalid_requests_are_rejected(array $payload, string $errorKey): void
    {
        Post::factory()->create(['id' => 1]);

        $this->moderate($payload)->assertUnprocessable()->assertJsonValidationErrors($errorKey);
    }

    public function test_customers_and_guests_cannot_moderate(): void
    {
        $post = Post::factory()->pending()->create();
        $customer = User::factory()->create();

        $this->moderate(['action' => 'approve', 'ids' => [$post->id]], $customer)->assertForbidden();
        $this->actingAs($customer)->putJson(route('admin.settings.post-approval'), ['enabled' => true])->assertForbidden();
        $this->actingAs($customer)->get(route('admin.posts'))->assertForbidden();

        auth()->logout();
        $this->postJson(route('admin.posts.moderate'), ['action' => 'delete', 'ids' => [$post->id]])->assertUnauthorized();

        $this->assertModelExists($post);
        $this->assertSame(PostStatus::Pending, $post->fresh()->status);
        $this->assertFalse(Setting::requiresPostApproval());
    }

    public function test_admin_can_toggle_require_approval_setting(): void
    {
        $this->actingAs($this->admin)
            ->putJson(route('admin.settings.post-approval'), ['enabled' => true])
            ->assertOk()
            ->assertJsonPath('enabled', true);

        $this->assertTrue(Setting::requiresPostApproval());
        $this->actingAs($this->admin)->get(route('admin.posts'))->assertSee('id="requireApproval" data-approval-switch', false)->assertSee('checked', false);

        $this->actingAs($this->admin)->putJson(route('admin.settings.post-approval'), ['enabled' => false])->assertJsonPath('enabled', false);
        $this->assertFalse(Setting::requiresPostApproval());

        $this->actingAs($this->admin)->putJson(route('admin.settings.post-approval'), ['enabled' => 'maybe'])->assertJsonValidationErrors('enabled');
    }
}
