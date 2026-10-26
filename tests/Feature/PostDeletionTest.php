<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\PostMedia;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PostDeletionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(config('media.disk'));
    }

    /**
     * @return array{Post, PostMedia, PostMedia}
     */
    private function postWithStoredMedia(User $owner): array
    {
        $post = Post::factory()->for($owner)->create();
        $image = PostMedia::factory()->for($post)->create();
        $video = PostMedia::factory()->video()->for($post)->create();

        Storage::disk(config('media.disk'))->put($image->file_path, 'image-bytes');
        Storage::disk(config('media.disk'))->put($video->file_path, 'video-bytes');

        return [$post, $image, $video];
    }

    public function test_owner_can_delete_their_post_and_its_files(): void
    {
        $owner = User::factory()->create();
        [$post, $image, $video] = $this->postWithStoredMedia($owner);

        $response = $this->actingAs($owner)->delete(route('posts.destroy', $post));

        $response->assertRedirect(route('dashboard'));
        $this->assertModelMissing($post);
        $this->assertSame(0, PostMedia::count());
        Storage::disk(config('media.disk'))->assertMissing($image->file_path);
        Storage::disk(config('media.disk'))->assertMissing($video->file_path);
    }

    public function test_customer_cannot_delete_another_customers_post_or_media(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        [$post, $image, $video] = $this->postWithStoredMedia($owner);

        $this->actingAs($intruder)->delete(route('posts.destroy', $post))->assertForbidden();

        $this->assertModelExists($post);
        $this->assertSame(2, PostMedia::count());
        Storage::disk(config('media.disk'))->assertExists($image->file_path);
        Storage::disk(config('media.disk'))->assertExists($video->file_path);
    }

    public function test_admin_can_delete_any_post(): void
    {
        [$post, $image] = $this->postWithStoredMedia(User::factory()->create());

        $response = $this->actingAs(User::factory()->admin()->create())->delete(route('posts.destroy', $post));

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertModelMissing($post);
        Storage::disk(config('media.disk'))->assertMissing($image->file_path);
    }

    public function test_guest_cannot_delete_a_post(): void
    {
        [$post, $image] = $this->postWithStoredMedia(User::factory()->create());

        $this->delete(route('posts.destroy', $post))->assertRedirect(route('login'));

        $this->assertModelExists($post);
        Storage::disk(config('media.disk'))->assertExists($image->file_path);
    }

    public function test_files_outside_the_media_directories_are_never_deleted(): void
    {
        $owner = User::factory()->create();
        $post = Post::factory()->for($owner)->create();
        $disk = Storage::disk(config('media.disk'));
        $disk->put('keep/important.txt', 'do not delete');

        PostMedia::factory()->for($post)->create(['file_path' => 'keep/important.txt']);
        PostMedia::factory()->for($post)->create(['file_path' => 'posts/images/../../keep/important.txt']);

        $this->actingAs($owner)->delete(route('posts.destroy', $post));

        $this->assertModelMissing($post);
        $disk->assertExists('keep/important.txt');
    }

    public function test_delete_button_is_only_shown_to_owner_and_admins(): void
    {
        $owner = User::factory()->create();
        $post = Post::factory()->for($owner)->create();
        $deleteAction = 'action="'.route('posts.destroy', $post).'"';

        $this->actingAs($owner)->get(route('posts.show', $post))->assertSee($deleteAction, false);
        $this->actingAs(User::factory()->admin()->create())->get(route('posts.show', $post))->assertSee($deleteAction, false);
        $this->actingAs(User::factory()->create())->get(route('posts.show', $post))->assertDontSee($deleteAction, false);
    }

    public function test_demo_post_page_still_renders_for_unknown_ids(): void
    {
        $this->get(route('posts.show', 'k7Xq92Lm'))->assertOk()->assertSee('cinnamon roll recipe right');
    }
}
