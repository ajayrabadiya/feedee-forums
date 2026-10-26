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
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class PostMediaUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(config('media.disk'));
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'title' => 'Weekend baking',
            'topic' => 'Recipes',
            'body' => 'A few photos from the kitchen.',
            'agree' => '1',
            ...$overrides,
        ];
    }

    private function disk(): Filesystem
    {
        return Storage::disk(config('media.disk'));
    }

    /**
     * Copy content into a real temporary file so MIME detection reads the actual bytes.
     */
    private function realUpload(string $contents, string $clientName, string $clientMime): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'ffup');
        file_put_contents($path, $contents);

        return new UploadedFile($path, $clientName, $clientMime, null, true);
    }

    public function test_guest_cannot_upload_media(): void
    {
        $response = $this->post(route('posts.store'), $this->payload([
            'photos' => [UploadedFile::fake()->image('cake.jpg')],
        ]));

        $response->assertRedirect(route('login'));
        $this->assertSame(0, Post::count());
        $this->assertSame([], $this->disk()->allFiles());
    }

    public function test_customer_can_create_a_text_only_post(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('posts.store'), $this->payload());

        $post = Post::sole();
        $response->assertRedirect(route('posts.show', $post));
        $this->assertSame(0, $post->media()->count());
        $this->assertSame([], $this->disk()->allFiles());

        $this->get(route('posts.show', $post))->assertOk()->assertSee('Weekend baking')->assertDontSee('class="gallery"', false);
    }

    public function test_customer_can_upload_a_valid_image(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('posts.store'), $this->payload([
            'photos' => [UploadedFile::fake()->image('Cinnamon Rolls!.jpg', 800, 600)],
            'alt_text' => ['Tray of cinnamon rolls'],
        ]))->assertSessionHasNoErrors();

        $media = PostMedia::sole();

        $this->assertSame(MediaType::Image, $media->media_type);
        $this->assertSame(config('media.disk'), $media->disk);
        $this->assertStringStartsWith('posts/images/', $media->file_path);
        $this->assertStringEndsWith('.jpg', $media->file_path);
        $this->assertStringNotContainsString('Cinnamon', $media->file_path);
        $this->assertSame('image/jpeg', $media->mime_type);
        $this->assertSame('Cinnamon Rolls.jpg', $media->original_name);
        $this->assertSame('Tray of cinnamon rolls', $media->alt_text);
        $this->assertGreaterThan(0, $media->file_size);
        $this->assertSame($user->id, $media->post->user_id);
        $this->disk()->assertExists($media->file_path);
        $this->assertSame($media->file_size, $this->disk()->size($media->file_path));
    }

    public function test_customer_can_upload_a_valid_video(): void
    {
        $this->actingAs(User::factory()->create())->post(route('posts.store'), $this->payload([
            'videos' => [UploadedFile::fake()->create('clip.mp4', 2048, 'video/mp4')],
        ]))->assertSessionHasNoErrors();

        $media = PostMedia::sole();

        $this->assertSame(MediaType::Video, $media->media_type);
        $this->assertStringStartsWith('posts/videos/', $media->file_path);
        $this->assertSame('video/mp4', $media->mime_type);
        $this->disk()->assertExists($media->file_path);
    }

    public function test_multiple_images_and_videos_within_limits_are_saved(): void
    {
        $photos = collect(range(1, config('media.images.max_files')))
            ->map(fn (int $i) => UploadedFile::fake()->image("photo-{$i}.png", 300, 200))
            ->all();

        $videos = [
            UploadedFile::fake()->create('one.mp4', 1024, 'video/mp4'),
            UploadedFile::fake()->create('two.webm', 1024, 'video/webm'),
        ];

        $this->actingAs(User::factory()->create())->post(route('posts.store'), $this->payload([
            'photos' => $photos,
            'videos' => $videos,
        ]))->assertSessionHasNoErrors();

        $post = Post::sole();

        $this->assertSame(config('media.images.max_files'), $post->images()->count());
        $this->assertSame(2, $post->videos()->count());
        $this->assertCount(config('media.images.max_files') + 2, $this->disk()->allFiles());
    }

    /**
     * @return array<string, array{string, \Closure(): UploadedFile}>
     */
    public static function invalidUploadProvider(): array
    {
        return [
            'php script as photo' => ['photos', fn () => UploadedFile::fake()->create('shell.php', 1, 'application/x-php')],
            'svg as photo' => ['photos', fn () => UploadedFile::fake()->create('logo.svg', 1, 'image/svg+xml')],
            'html as photo' => ['photos', fn () => UploadedFile::fake()->create('page.html', 1, 'text/html')],
            'gif as photo' => ['photos', fn () => UploadedFile::fake()->image('anim.gif')],
            'mov as video' => ['videos', fn () => UploadedFile::fake()->create('clip.mov', 100, 'video/quicktime')],
            'image as video' => ['videos', fn () => UploadedFile::fake()->image('still.jpg')],
            'exe as video' => ['videos', fn () => UploadedFile::fake()->create('clip.exe', 100, 'application/x-msdownload')],
        ];
    }

    /**
     * @param  \Closure(): UploadedFile  $makeFile
     */
    #[DataProvider('invalidUploadProvider')]
    public function test_unsupported_files_are_rejected(string $field, \Closure $makeFile): void
    {
        $response = $this->actingAs(User::factory()->create())->post(route('posts.store'), $this->payload([
            $field => [$makeFile()],
        ]));

        $response->assertSessionHasErrors("{$field}.0");
        $this->assertSame(0, Post::count());
        $this->assertSame([], $this->disk()->allFiles());
    }

    public function test_script_disguised_with_an_image_extension_is_rejected_by_content(): void
    {
        $disguised = $this->realUpload('<?php echo "pwned"; ?>', 'holiday.jpg', 'image/jpeg');

        $this->actingAs(User::factory()->create())
            ->post(route('posts.store'), $this->payload(['photos' => [$disguised]]))
            ->assertSessionHasErrors('photos.0');

        $this->assertSame(0, Post::count());
        $this->assertSame([], $this->disk()->allFiles());
    }

    public function test_real_image_with_executable_extension_is_rejected(): void
    {
        $jpeg = UploadedFile::fake()->image('real.jpg');
        $renamed = $this->realUpload(file_get_contents($jpeg->getRealPath()), 'payload.php', 'image/jpeg');

        $this->actingAs(User::factory()->create())
            ->post(route('posts.store'), $this->payload(['photos' => [$renamed]]))
            ->assertSessionHasErrors('photos.0');

        $this->assertSame([], $this->disk()->allFiles());
    }

    public function test_real_image_is_stored_with_extension_from_its_contents(): void
    {
        $png = UploadedFile::fake()->image('real.png');
        $upload = $this->realUpload(file_get_contents($png->getRealPath()), 'my photo.png', 'image/png');

        $this->actingAs(User::factory()->create())
            ->post(route('posts.store'), $this->payload(['photos' => [$upload]]))
            ->assertSessionHasNoErrors();

        $media = PostMedia::sole();
        $this->assertSame('image/png', $media->mime_type);
        $this->assertMatchesRegularExpression('#^posts/images/[A-Za-z0-9]{40}\.png$#', $media->file_path);
    }

    public function test_oversized_files_are_rejected(): void
    {
        $tooBigImageKb = config('media.images.max_kb') + 1;
        $tooBigVideoKb = config('media.videos.max_kb') + 1;

        $response = $this->actingAs(User::factory()->create())->post(route('posts.store'), $this->payload([
            'photos' => [UploadedFile::fake()->image('huge.jpg')->size($tooBigImageKb)],
            'videos' => [UploadedFile::fake()->create('huge.mp4', $tooBigVideoKb, 'video/mp4')],
        ]));

        $response->assertSessionHasErrors(['photos.0', 'videos.0']);
        $this->assertSame(0, Post::count());
        $this->assertSame([], $this->disk()->allFiles());
    }

    public function test_file_count_limits_are_enforced(): void
    {
        $photos = collect(range(1, config('media.images.max_files') + 1))
            ->map(fn (int $i) => UploadedFile::fake()->image("p{$i}.jpg", 50, 50))
            ->all();

        $videos = collect(range(1, config('media.videos.max_files') + 1))
            ->map(fn (int $i) => UploadedFile::fake()->create("v{$i}.mp4", 10, 'video/mp4'))
            ->all();

        $response = $this->actingAs(User::factory()->create())->post(route('posts.store'), $this->payload([
            'photos' => $photos,
            'videos' => $videos,
        ]));

        $response->assertSessionHasErrors(['photos', 'videos']);
        $this->assertSame(0, Post::count());
        $this->assertSame([], $this->disk()->allFiles());
    }

    public function test_post_page_renders_working_media_urls(): void
    {
        $this->actingAs(User::factory()->create())->post(route('posts.store'), $this->payload([
            'photos' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.jpg')],
            'alt_text' => ['First photo', 'Second photo'],
            'videos' => [UploadedFile::fake()->create('clip.webm', 100, 'video/webm')],
        ]));

        $post = Post::sole();
        $response = $this->get(route('posts.show', $post));

        $response->assertOk();
        foreach ($post->media as $media) {
            $url = $this->disk()->url($media->file_path);
            $this->assertStringContainsString('/storage/'.$media->file_path, $url);
            $response->assertSee($url, false);
        }
        $response->assertSee('alt="First photo"', false);
        $response->assertSee('<source src="'.$this->disk()->url($post->videos->first()->file_path).'" type="video/webm">', false);
        $response->assertSee('preload="metadata"', false);
        $response->assertSee('2 photos');
        $response->assertSee('1 video');
    }

    public function test_dashboard_lists_media_counts_and_thumbnail(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('posts.store'), $this->payload([
            'photos' => [UploadedFile::fake()->image('a.jpg')],
            'videos' => [UploadedFile::fake()->create('clip.mp4', 100, 'video/mp4')],
        ]));

        $image = PostMedia::where('media_type', MediaType::Image)->sole();

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee($this->disk()->url($image->file_path), false)
            ->assertSee('1 photo')
            ->assertSee('1 video');
    }

    public function test_failed_database_write_leaves_no_post_or_orphaned_files(): void
    {
        PostMedia::creating(function (PostMedia $media): void {
            if ($media->media_type === MediaType::Video) {
                throw new RuntimeException('Simulated database failure');
            }
        });

        $response = $this->actingAs(User::factory()->create())->post(route('posts.store'), $this->payload([
            'photos' => [UploadedFile::fake()->image('a.jpg')],
            'videos' => [UploadedFile::fake()->create('clip.mp4', 100, 'video/mp4')],
        ]));

        $response->assertSessionHasErrors('media');
        $response->assertSessionHasInput('title', 'Weekend baking');
        $this->assertSame(0, Post::count());
        $this->assertSame(0, PostMedia::count());
        $this->assertSame([], $this->disk()->allFiles());
    }

    public function test_customer_cannot_upload_media_on_behalf_of_another_user(): void
    {
        $customer = User::factory()->create();
        $victim = User::factory()->create();
        $victimPost = Post::factory()->for($victim)->create();

        $this->actingAs($customer)->post(route('posts.store'), $this->payload([
            'user_id' => $victim->id,
            'post_id' => $victimPost->id,
            'photos' => [UploadedFile::fake()->image('a.jpg')],
            'file_path' => ['../../.env'],
            'disk' => 'local',
        ]))->assertSessionHasNoErrors();

        $media = PostMedia::sole();

        $this->assertNotSame($victimPost->id, $media->post_id);
        $this->assertSame($customer->id, $media->post->user_id);
        $this->assertSame(config('media.disk'), $media->disk);
        $this->assertStringStartsWith('posts/images/', $media->file_path);
        $this->assertSame(0, $victimPost->media()->count());
    }
}
