<?php

namespace Tests\Feature;

use App\Enums\MediaType;
use App\Models\Post;
use App\Models\PostMedia;
use App\Models\User;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class PostEditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(config('media.disk'));
    }

    private function disk(): Filesystem
    {
        return Storage::disk(config('media.disk'));
    }

    /**
     * @return array{Post, PostMedia, PostMedia}
     */
    private function postWithMedia(User $owner): array
    {
        $post = Post::factory()->for($owner)->create([
            'title' => 'Original title',
            'topic' => 'Recipes',
            'body' => 'Original body text.',
        ]);
        $image = PostMedia::factory()->for($post)->create(['original_name' => 'kept-photo.jpg']);
        $video = PostMedia::factory()->video()->for($post)->create(['original_name' => 'kept-clip.mp4']);

        $this->disk()->put($image->file_path, 'image-bytes');
        $this->disk()->put($video->file_path, 'video-bytes');

        return [$post, $image, $video];
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'title' => 'Updated title',
            'topic' => 'Outdoors',
            'body' => 'Updated body text.',
            ...$overrides,
        ];
    }

    public function test_owner_can_open_edit_form_prefilled_with_post_data_and_media(): void
    {
        $owner = User::factory()->create();
        [$post, $image, $video] = $this->postWithMedia($owner);

        $response = $this->actingAs($owner)->get(route('posts.edit', $post));

        $response->assertOk();
        $response->assertSee('Edit post');
        $response->assertSee('action="'.route('posts.update', $post).'"', false);
        $response->assertSee('name="_method" value="put"', false);
        $response->assertSee('value="Original title"', false);
        $response->assertSee('<option value="Recipes" selected>Recipes</option>', false);
        $response->assertSee('Original body text.</textarea>', false);
        $response->assertSee($this->disk()->url($image->file_path), false);
        $response->assertSee($this->disk()->url($video->file_path), false);
        $response->assertSee('name="remove_media[]" value="'.$image->id.'"', false);
        $response->assertSee('name="remove_media[]" value="'.$video->id.'"', false);
        $response->assertSee('Save changes');
        $response->assertSee('href="'.route('posts.show', $post).'" class="btn-soft">Cancel', false);
    }

    public function test_owner_can_update_title_topic_and_body_keeping_media(): void
    {
        $owner = User::factory()->create();
        [$post, $image, $video] = $this->postWithMedia($owner);
        $createdAt = $post->created_at;

        $response = $this->actingAs($owner)->put(route('posts.update', $post), $this->payload());

        $response->assertRedirect(route('posts.show', $post));
        $response->assertSessionHas('status', 'Your post has been updated.');

        $post->refresh();
        $this->assertSame('Updated title', $post->title);
        $this->assertSame('Outdoors', $post->topic);
        $this->assertSame('Updated body text.', $post->body);
        $this->assertTrue($createdAt->equalTo($post->created_at));
        $this->assertSame(2, $post->media()->count());
        $this->disk()->assertExists($image->file_path);
        $this->disk()->assertExists($video->file_path);
    }

    public function test_body_can_be_cleared(): void
    {
        $owner = User::factory()->create();
        [$post] = $this->postWithMedia($owner);

        $this->actingAs($owner)->put(route('posts.update', $post), $this->payload(['body' => '']))->assertSessionHasNoErrors();

        $this->assertNull($post->fresh()->body);
    }

    public function test_owner_can_add_new_images_and_videos(): void
    {
        $owner = User::factory()->create();
        [$post] = $this->postWithMedia($owner);

        $this->actingAs($owner)->put(route('posts.update', $post), $this->payload([
            'photos' => [UploadedFile::fake()->image('new.png', 300, 200)],
            'alt_text' => ['A brand new photo'],
            'videos' => [UploadedFile::fake()->create('new.webm', 500, 'video/webm')],
            'agree' => '1',
        ]))->assertSessionHasNoErrors();

        $this->assertSame(2, $post->images()->count());
        $this->assertSame(2, $post->videos()->count());

        $newImage = $post->images()->get()->last();
        $this->assertSame('A brand new photo', $newImage->alt_text);
        $this->assertSame('image/png', $newImage->mime_type);
        $this->assertStringStartsWith('posts/images/', $newImage->file_path);
        $this->disk()->assertExists($newImage->file_path);
        $this->disk()->assertExists($post->videos()->get()->last()->file_path);
    }

    public function test_adding_media_requires_the_ownership_confirmation(): void
    {
        $owner = User::factory()->create();
        [$post] = $this->postWithMedia($owner);

        $this->actingAs($owner)->put(route('posts.update', $post), $this->payload([
            'photos' => [UploadedFile::fake()->image('new.jpg')],
        ]))->assertSessionHasErrors('agree');

        $this->assertSame(1, $post->images()->count());
    }

    public function test_owner_can_remove_selected_existing_media(): void
    {
        $owner = User::factory()->create();
        [$post, $image, $video] = $this->postWithMedia($owner);

        $this->actingAs($owner)->put(route('posts.update', $post), $this->payload([
            'remove_media' => [$image->id],
        ]))->assertSessionHasNoErrors();

        $this->assertModelMissing($image);
        $this->assertModelExists($video);
        $this->disk()->assertMissing($image->file_path);
        $this->disk()->assertExists($video->file_path);
    }

    public function test_media_limits_count_kept_and_new_files_together(): void
    {
        $owner = User::factory()->create();
        [$post, , $video] = $this->postWithMedia($owner);
        PostMedia::factory()->video()->for($post)->create();

        $response = $this->actingAs($owner)->put(route('posts.update', $post), $this->payload([
            'videos' => [UploadedFile::fake()->create('third.mp4', 100, 'video/mp4')],
            'agree' => '1',
        ]));

        $response->assertSessionHasErrors('videos');
        $this->assertSame(2, $post->videos()->count());
        $this->assertCount(2, $this->disk()->allFiles());

        $this->actingAs($owner)->put(route('posts.update', $post), $this->payload([
            'remove_media' => [$video->id],
            'videos' => [UploadedFile::fake()->create('replacement.mp4', 100, 'video/mp4')],
            'agree' => '1',
        ]))->assertSessionHasNoErrors();

        $this->assertSame(2, $post->videos()->count());
        $this->assertModelMissing($video);
    }

    public function test_customer_cannot_edit_another_customers_post(): void
    {
        [$post, $image] = $this->postWithMedia(User::factory()->create());
        $intruder = User::factory()->create();

        $this->actingAs($intruder)->get(route('posts.edit', $post))->assertForbidden();
        $this->actingAs($intruder)->put(route('posts.update', $post), $this->payload([
            'remove_media' => [$image->id],
        ]))->assertForbidden();

        $this->assertSame('Original title', $post->fresh()->title);
        $this->assertModelExists($image);
        $this->disk()->assertExists($image->file_path);
    }

    public function test_guest_cannot_open_edit_page_or_submit_update(): void
    {
        [$post] = $this->postWithMedia(User::factory()->create());

        $this->get(route('posts.edit', $post))->assertRedirect(route('login'));
        $this->put(route('posts.update', $post), $this->payload())->assertRedirect(route('login'));

        $this->assertSame('Original title', $post->fresh()->title);
    }

    public function test_guest_returns_to_edit_page_after_logging_in(): void
    {
        $owner = User::factory()->create(['email' => 'owner@example.com']);
        [$post] = $this->postWithMedia($owner);

        $this->get(route('posts.edit', $post))->assertRedirect(route('login'));

        $this->post(route('login.store'), ['login' => 'owner@example.com', 'password' => 'password'])
            ->assertRedirect(route('posts.edit', $post));
    }

    public function test_customer_cannot_change_author_or_protected_fields(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        [$post] = $this->postWithMedia($owner);
        $publicId = $post->public_id;

        $this->actingAs($owner)->put(route('posts.update', $post), $this->payload([
            'user_id' => $other->id,
            'author_id' => $other->id,
            'public_id' => 'HIJACKED',
            'created_at' => '2000-01-01 00:00:00',
            'role' => 'admin',
        ]))->assertSessionHasNoErrors();

        $post->refresh();
        $this->assertSame($owner->id, $post->user_id);
        $this->assertSame($publicId, $post->public_id);
        $this->assertNotSame(2000, $post->created_at->year);
        $this->assertFalse($owner->fresh()->isAdmin());
    }

    public function test_customer_cannot_remove_media_belonging_to_another_post(): void
    {
        $owner = User::factory()->create();
        [$post] = $this->postWithMedia($owner);
        [, $otherImage, $otherVideo] = $this->postWithMedia(User::factory()->create());
        [, $ownOtherPostImage] = $this->postWithMedia($owner);

        foreach ([$otherImage, $otherVideo, $ownOtherPostImage] as $foreignMedia) {
            $this->actingAs($owner)->put(route('posts.update', $post), $this->payload([
                'remove_media' => [$foreignMedia->id],
            ]))->assertSessionHasErrors('remove_media.0');

            $this->assertModelExists($foreignMedia);
            $this->disk()->assertExists($foreignMedia->file_path);
        }

        $this->assertSame('Original title', $post->fresh()->title);
    }

    public function test_invalid_input_is_rejected(): void
    {
        $owner = User::factory()->create();
        [$post] = $this->postWithMedia($owner);

        $response = $this->actingAs($owner)->from(route('posts.edit', $post))->put(route('posts.update', $post), [
            'title' => '',
            'topic' => 'Not a topic',
            'body' => str_repeat('a', 3001),
            'remove_media' => ['not-a-number'],
            'photos' => [UploadedFile::fake()->create('evil.php', 1, 'application/x-php')],
            'agree' => '1',
        ]);

        $response->assertRedirect(route('posts.edit', $post));
        $response->assertSessionHasErrors(['title', 'topic', 'body', 'remove_media.0', 'photos.0']);
        $response->assertSessionHasInput('body', str_repeat('a', 3001));
        $this->assertSame('Original title', $post->fresh()->title);
        $this->assertCount(2, $this->disk()->allFiles());
    }

    public function test_failed_update_keeps_existing_media_and_leaves_no_orphaned_files(): void
    {
        $owner = User::factory()->create();
        [$post, $image, $video] = $this->postWithMedia($owner);

        PostMedia::creating(function (PostMedia $media): void {
            if ($media->media_type === MediaType::Video) {
                throw new RuntimeException('Simulated database failure');
            }
        });

        $response = $this->actingAs($owner)->put(route('posts.update', $post), $this->payload([
            'remove_media' => [$image->id],
            'photos' => [UploadedFile::fake()->image('new.jpg')],
            'videos' => [UploadedFile::fake()->create('new.mp4', 100, 'video/mp4')],
            'agree' => '1',
        ]));

        $response->assertSessionHasErrors('media');
        $response->assertSessionHasInput('title', 'Updated title');

        $post->refresh();
        $this->assertSame('Original title', $post->title);
        $this->assertModelExists($image);
        $this->assertModelExists($video);
        $this->assertSame(2, $post->media()->count());
        $this->disk()->assertExists($image->file_path);
        $this->disk()->assertExists($video->file_path);
        $this->assertEqualsCanonicalizing([$image->file_path, $video->file_path], $this->disk()->allFiles());
    }

    public function test_admin_can_edit_any_post_without_taking_ownership(): void
    {
        $owner = User::factory()->create();
        [$post, $image] = $this->postWithMedia($owner);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('posts.edit', $post))->assertOk();
        $this->actingAs($admin)->put(route('posts.update', $post), $this->payload([
            'remove_media' => [$image->id],
        ]))->assertRedirect(route('posts.show', $post));

        $post->refresh();
        $this->assertSame('Updated title', $post->title);
        $this->assertSame($owner->id, $post->user_id);
        $this->assertModelMissing($image);
    }

    public function test_edit_links_are_only_shown_to_owner_and_admins(): void
    {
        $owner = User::factory()->create();
        $post = Post::factory()->for($owner)->create();
        $editLink = 'href="'.route('posts.edit', $post).'"';

        $this->actingAs($owner)->get(route('posts.show', $post))->assertSee($editLink, false);
        $this->actingAs($owner)->get(route('dashboard'))->assertSee($editLink, false);
        $this->actingAs(User::factory()->admin()->create())->get(route('posts.show', $post))->assertSee($editLink, false);
        $this->actingAs(User::factory()->create())->get(route('posts.show', $post))->assertDontSee($editLink, false);
        $this->get(route('posts.show', $post))->assertDontSee($editLink, false);
    }

    public function test_create_post_page_is_unchanged_by_edit_mode(): void
    {
        $response = $this->actingAs(User::factory()->create())->get(route('posts.create'));

        $response->assertOk();
        $response->assertSee('Create a post');
        $response->assertSee('action="'.route('posts.store').'"', false);
        $response->assertDontSee('name="_method"', false);
        $response->assertDontSee('remove_media', false);
        $response->assertSee('Submit for review');
        $response->assertDontSee('Save changes');
    }
}
