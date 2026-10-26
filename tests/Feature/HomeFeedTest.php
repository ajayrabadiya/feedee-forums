<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\PostMedia;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HomeFeedTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(config('media.disk'));
    }

    public function test_feed_lists_live_posts_newest_first_with_author_and_topic(): void
    {
        $author = User::factory()->create(['name' => 'maya_bakes']);
        Post::factory()->for($author)->create(['title' => 'Older cinnamon post', 'topic' => 'Recipes', 'created_at' => now()->subDays(2)]);
        $newest = Post::factory()->for($author)->create(['title' => 'Newest sunset post', 'topic' => 'Outdoors', 'created_at' => now()->subHour()]);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSeeInOrder(['Newest sunset post', 'Older cinnamon post']);
        $response->assertSee('maya_bakes');
        $response->assertSee('href="'.route('posts.show', $newest).'"', false);
        $response->assertSee('<a href="'.route('home', ['topic' => 'Outdoors']).'" class="topic t-green">Outdoors</a>', false);
        $response->assertSeeInOrder(['Today', 'Newest sunset post', 'Older cinnamon post']);
    }

    public function test_demo_placeholder_posts_are_no_longer_shown(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertDontSee('picsum.photos', false);
        $response->assertDontSee('Finally got my grandma');
        $response->assertSee('No posts yet');
    }

    public function test_expired_posts_are_hidden(): void
    {
        Post::factory()->create(['title' => 'Still live post', 'created_at' => now()->subDays(29)]);
        Post::factory()->create(['title' => 'Expired old post', 'created_at' => now()->subDays(31)]);

        $this->get(route('home'))->assertSee('Still live post')->assertDontSee('Expired old post');
    }

    public function test_feed_shows_photo_grid_with_overflow_count(): void
    {
        $post = Post::factory()->create();
        $images = PostMedia::factory()->count(5)->for($post)->create();

        $response = $this->get(route('home'));

        $response->assertSee('media-grid count-3', false);
        $response->assertSee(Storage::disk(config('media.disk'))->url($images[0]->file_path), false);
        $response->assertDontSee(Storage::disk(config('media.disk'))->url($images[3]->file_path), false);
        $response->assertSee('<span class="media-more">+2</span>', false);
        $response->assertSee('5 photos');
    }

    public function test_feed_shows_video_preview_when_post_has_only_video(): void
    {
        $post = Post::factory()->create();
        $video = PostMedia::factory()->video()->for($post)->create();

        $response = $this->get(route('home'));

        $response->assertSee('<source src="'.Storage::disk(config('media.disk'))->url($video->file_path).'#t=0.1" type="video/mp4">', false);
        $response->assertSee('preload="metadata"', false);
        $response->assertSee('1 video');
    }

    public function test_text_only_posts_render_without_media(): void
    {
        Post::factory()->create(['title' => 'Just words', 'body' => 'A short story with no photos.']);

        $response = $this->get(route('home'));

        $response->assertSee('Just words');
        $response->assertSee('A short story with no photos.');
        $response->assertDontSee('class="media-grid', false);
    }

    public function test_search_filters_by_title_and_body(): void
    {
        Post::factory()->create(['title' => 'Cinnamon rolls', 'body' => 'Sweet dough']);
        Post::factory()->create(['title' => 'Coastal ride', 'body' => 'Sunset by the sea with cinnamon coffee']);
        Post::factory()->create(['title' => 'Balcony garden', 'body' => 'Flowers blooming']);

        $response = $this->get(route('home', ['q' => 'cinnamon']));

        $response->assertSee('Cinnamon rolls');
        $response->assertSee('Coastal ride');
        $response->assertDontSee('Balcony garden');
        $response->assertSee('2 results');
        $response->assertSee('value="cinnamon"', false);
    }

    public function test_topic_filter_shows_only_that_topic(): void
    {
        Post::factory()->create(['title' => 'A recipe post', 'topic' => 'Recipes']);
        Post::factory()->create(['title' => 'An outdoors post', 'topic' => 'Outdoors']);

        $response = $this->get(route('home', ['topic' => 'Recipes']));

        $response->assertSee('A recipe post');
        $response->assertDontSee('An outdoors post');
        $this->assertMatchesRegularExpression(
            '#<a href="'.preg_quote(route('home', ['topic' => 'Recipes']), '#').'"\s+class="active"\s+aria-current="page"#',
            $response->getContent(),
        );
        $response->assertDontSee('<a href="'.route('home', ['topic' => 'Outdoors']).'" class="active"', false);
    }

    public function test_unknown_topic_and_array_search_are_ignored(): void
    {
        Post::factory()->create(['title' => 'Visible post']);

        $this->get('/?topic=Hacking&q[]=x')->assertOk()->assertSee('Visible post');
    }

    public function test_no_matches_shows_filtered_empty_state(): void
    {
        Post::factory()->create(['title' => 'Something else']);

        $this->get(route('home', ['q' => 'nothing-matches-this']))
            ->assertOk()
            ->assertSee('No posts found')
            ->assertSee('No results for');
    }

    public function test_feed_paginates_twenty_posts_per_page(): void
    {
        foreach (range(1, 25) as $i) {
            Post::factory()->create(['title' => "Feed post number {$i}", 'created_at' => now()->subMinutes(100 - $i)]);
        }

        $firstPage = $this->get(route('home'));
        $firstPage->assertSee('Feed post number 25');
        $firstPage->assertSee('Feed post number 6');
        $firstPage->assertDontSee('Feed post number 5<', false);
        $firstPage->assertSee('Page 1 of 2');
        $firstPage->assertSee('href="'.route('home', ['page' => 2]).'" rel="next"', false);

        $secondPage = $this->get(route('home', ['page' => 2]));
        $secondPage->assertSee('Feed post number 5');
        $secondPage->assertSee('Feed post number 1');
        $secondPage->assertDontSee('Feed post number 25');
        $secondPage->assertSee('Page 2 of 2');
    }

    public function test_pagination_keeps_search_and_topic(): void
    {
        foreach (range(1, 21) as $i) {
            Post::factory()->create(['title' => "Recipe idea {$i}", 'topic' => 'Recipes']);
        }

        $this->get(route('home', ['topic' => 'Recipes', 'q' => 'idea']))
            ->assertSee(e(route('home', ['topic' => 'Recipes', 'q' => 'idea', 'page' => 2])), false);
    }

    public function test_post_content_is_escaped(): void
    {
        Post::factory()->create(['title' => '<script>alert(1)</script>', 'body' => '<img src=x onerror=alert(1)>']);

        $response = $this->get(route('home'));

        $response->assertDontSee('<script>alert(1)</script>', false);
        $response->assertDontSee('<img src=x onerror', false);
        $response->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
    }

    public function test_edit_option_only_shown_on_own_posts(): void
    {
        $owner = User::factory()->create();
        $ownPost = Post::factory()->for($owner)->create();
        $otherPost = Post::factory()->create();

        $this->get(route('home'))->assertDontSee(route('posts.edit', $ownPost), false);

        $response = $this->actingAs($owner)->get(route('home'));

        $response->assertSee('href="'.route('posts.edit', $ownPost).'"', false);
        $response->assertDontSee('href="'.route('posts.edit', $otherPost).'"', false);
    }
}
