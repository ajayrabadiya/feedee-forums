<?php

namespace Tests\Feature;

use App\Enums\MediaType;
use App\Enums\PostStatus;
use App\Models\Comment;
use App\Models\Post;
use App\Models\PostLike;
use App\Models\PostMedia;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PurgeExpiredPostsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(config('media.disk'));
    }

    public function test_pending_post_does_not_start_lifetime_or_appear_publicly(): void
    {
        $post = Post::factory()->pending()->create([
            'title' => 'Waiting for review',
            'created_at' => now()->subDays(10),
        ]);

        $this->assertNull($post->approved_at);
        $this->assertNull($post->expiresAt());
        $this->assertFalse($post->isWithinLifetime());
        $this->get(route('home'))->assertDontSee('Waiting for review');
        $this->get(route('posts.show', $post))->assertNotFound();
    }

    public function test_post_approved_today_expires_exactly_thirty_days_later(): void
    {
        $approvedAt = now()->startOfSecond();
        $post = Post::factory()->create(['approved_at' => $approvedAt]);

        $this->assertSame($approvedAt->timestamp, $post->expiresAt()->subDays(Post::LIFETIME_DAYS)->timestamp);
        $this->assertSame($approvedAt->copy()->addDays(Post::LIFETIME_DAYS)->timestamp, $post->expiresAt()->timestamp);
    }

    public function test_post_approved_twenty_nine_days_ago_remains_active(): void
    {
        $post = Post::factory()->create([
            'title' => 'Almost expired',
            'approved_at' => now()->subDays(29),
        ]);

        $this->assertTrue($post->isWithinLifetime());
        $this->get(route('home'))->assertSee('Almost expired');
        $this->get(route('posts.show', $post))->assertOk();
    }

    public function test_post_approved_exactly_thirty_days_ago_is_eligible_for_deletion(): void
    {
        $post = Post::factory()->create([
            'title' => 'Exactly thirty',
            'approved_at' => now()->subDays(Post::LIFETIME_DAYS),
        ]);

        $this->assertTrue($post->isExpired());
        $this->assertTrue(Post::query()->expired()->whereKey($post)->exists());
        $this->get(route('home'))->assertDontSee('Exactly thirty');
        $this->get(route('posts.show', $post))->assertNotFound();
    }

    public function test_post_approved_thirty_one_days_ago_is_permanently_deleted_by_purge(): void
    {
        $post = Post::factory()->expired(now()->subDays(31))->create(['title' => 'Old approved']);

        Artisan::call('posts:purge-expired');

        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
        $this->assertSame(0, Post::count());
    }

    public function test_pending_posts_are_not_deleted_by_purge(): void
    {
        $pending = Post::factory()->pending()->create([
            'created_at' => now()->subDays(60),
        ]);

        Artisan::call('posts:purge-expired');

        $this->assertDatabaseHas('posts', ['id' => $pending->id, 'status' => PostStatus::Pending->value]);
    }

    public function test_rejected_posts_are_not_deleted_by_purge(): void
    {
        $rejected = Post::factory()->rejected()->create([
            'created_at' => now()->subDays(60),
            'moderated_at' => now()->subDays(59),
        ]);

        Artisan::call('posts:purge-expired');

        $this->assertDatabaseHas('posts', ['id' => $rejected->id, 'status' => PostStatus::Rejected->value]);
    }

    public function test_editing_an_approved_post_does_not_reset_expiry(): void
    {
        $owner = User::factory()->create();
        $approvedAt = now()->subDays(5);
        $post = Post::factory()->for($owner)->create([
            'title' => 'Original',
            'approved_at' => $approvedAt,
            'body' => 'Before',
        ]);

        $this->actingAs($owner)->put(route('posts.update', $post), [
            'title' => 'Edited title',
            'body' => 'After edit',
        ])->assertRedirect(route('posts.show', $post));

        $post->refresh();
        $this->assertSame('Edited title', $post->title);
        $this->assertSame($approvedAt->timestamp, $post->approved_at->timestamp);
        $this->assertSame($approvedAt->copy()->addDays(Post::LIFETIME_DAYS)->timestamp, $post->expiresAt()->timestamp);
    }

    public function test_expired_posts_cannot_receive_likes_or_comments(): void
    {
        $member = User::factory()->create();
        $post = Post::factory()->expired()->create();

        $this->actingAs($member)
            ->postJson(route('posts.like', $post))
            ->assertForbidden();

        $this->actingAs($member)
            ->post(route('posts.comments.store', $post), ['body' => 'Too late to comment'])
            ->assertForbidden();

        $this->assertSame(0, PostLike::count());
        $this->assertSame(0, Comment::count());
    }

    public function test_purge_deletes_related_records_and_media_files(): void
    {
        $post = Post::factory()->expired()->create();
        $image = PostMedia::factory()->for($post)->create();
        $video = PostMedia::factory()->video()->for($post)->create();
        Comment::factory()->for($post)->create();
        $post->likes()->forceCreate(['user_id' => User::factory()->create()->id]);

        Storage::disk(config('media.disk'))->put($image->file_path, 'image-bytes');
        Storage::disk(config('media.disk'))->put($video->file_path, 'video-bytes');

        Artisan::call('posts:purge-expired');

        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
        $this->assertDatabaseMissing('post_media', ['id' => $image->id]);
        $this->assertDatabaseMissing('post_media', ['id' => $video->id]);
        $this->assertSame(0, Comment::count());
        $this->assertSame(0, PostLike::count());
        Storage::disk(config('media.disk'))->assertMissing($image->file_path);
        Storage::disk(config('media.disk'))->assertMissing($video->file_path);
    }

    public function test_missing_media_files_do_not_break_cleanup(): void
    {
        $post = Post::factory()->expired()->create();
        PostMedia::factory()->for($post)->create([
            'file_path' => 'posts/images/missing-file.jpg',
            'media_type' => MediaType::Image,
        ]);

        $exit = Artisan::call('posts:purge-expired');

        $this->assertSame(0, $exit);
        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }

    public function test_running_cleanup_twice_is_safe(): void
    {
        Post::factory()->expired()->create();

        $this->assertSame(0, Artisan::call('posts:purge-expired'));
        $this->assertSame(0, Artisan::call('posts:purge-expired'));
        $this->assertSame(0, Post::count());
    }

    public function test_reapproving_rejected_post_starts_a_new_lifetime(): void
    {
        $admin = User::factory()->admin()->create();
        $post = Post::factory()->rejected()->create([
            'approved_at' => now()->subDays(40),
        ]);

        $post->approve($admin);
        $post->refresh();

        $this->assertTrue($post->approved_at->greaterThan(now()->subMinute()));
        $this->assertTrue($post->isWithinLifetime());
    }

    public function test_approving_again_does_not_reset_lifetime_for_live_post(): void
    {
        $admin = User::factory()->admin()->create();
        $approvedAt = now()->subDays(3);
        $post = Post::factory()->create(['approved_at' => $approvedAt]);

        $post->approve($admin);
        $post->refresh();

        $this->assertSame($approvedAt->timestamp, $post->approved_at->timestamp);
    }
}
