<?php

namespace App\Http\Controllers;

use App\Enums\MediaType;
use App\Enums\PostStatus;
use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Models\Post;
use App\Models\PostMedia;
use App\Models\Setting;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class PostController extends Controller
{
    /**
     * Number of posts shown per feed page.
     */
    private const PER_PAGE = 20;

    /**
     * Home page feed: live posts, newest first, with optional search and topic filter.
     */
    public function index(Request $request): View
    {
        $search = is_string($request->query('q')) ? Str::limit(trim($request->query('q')), 100, '') : '';
        $topic = in_array($request->query('topic'), Post::TOPICS, true) ? $request->query('topic') : null;

        $showFeatured = $search === '' && $topic === null;

        $featuredPosts = $showFeatured && (int) $request->query('page', 1) <= 1
            ? Post::query()->published()->where('is_featured', true)->with(['user', 'media'])->latest('featured_at')->get()
            : collect();

        $posts = Post::query()
            ->published()
            ->with(['user', 'media'])
            ->when($showFeatured, fn ($query) => $query->where('is_featured', false))
            ->when($topic, fn ($query) => $query->where('topic', $topic))
            ->when($search !== '', function ($query) use ($search): void {
                $like = '%'.addcslashes($search, '%_\\').'%';

                $query->where(fn ($query) => $query->where('title', 'like', $like)->orWhere('body', 'like', $like));
            })
            ->latest()
            ->latest('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('pages.home', [
            'posts' => $posts,
            'featuredPosts' => $featuredPosts,
            'search' => $search,
            'topic' => $topic,
            'topicStyles' => Post::TOPIC_STYLES,
        ]);
    }

    public function create(Request $request): View
    {
        return view('pages.create-post', [
            'requiresApproval' => Setting::requiresPostApproval() && ! $request->user()->isAdmin(),
            'topics' => Post::TOPICS,
            'images' => config('media.images'),
            'videos' => config('media.videos'),
        ]);
    }

    public function store(StorePostRequest $request): RedirectResponse
    {
        $disk = config('media.disk');
        $storedPaths = [];

        try {
            $post = DB::transaction(function () use ($request, $disk, &$storedPaths): Post {
                $post = $request->user()->posts()->make($request->safe()->only(['title', 'topic', 'body']));
                $post->status = Setting::requiresPostApproval() && ! $request->user()->isAdmin()
                    ? PostStatus::Pending
                    : PostStatus::Approved;
                $post->save();

                $this->attachUploads($request, $post, $disk, $storedPaths);

                return $post;
            });
        } catch (Throwable $exception) {
            if ($storedPaths !== []) {
                Storage::disk($disk)->delete($storedPaths);
            }

            report($exception);

            return back()
                ->withInput($request->safe()->only(['title', 'topic', 'body']))
                ->withErrors(['media' => "We couldn't save your post and its media. Please try again."]);
        }

        return redirect()
            ->route('posts.show', $post)
            ->with('status', $post->isPending()
                ? 'Thanks for sharing! Your post will appear in the feed once a moderator approves it.'
                : 'Thanks for sharing! Your post has been published.');
    }

    public function show(Request $request, string $post): View
    {
        $realPost = Post::query()
            ->where('public_id', $post)
            ->with(['user', 'media'])
            ->first();

        if ($realPost === null) {
            return view('pages.post');
        }

        abort_unless(Gate::forUser($request->user())->allows('view', $realPost), 404);

        return view('pages.post-show', [
            'post' => $realPost,
            'images' => $realPost->media->filter->isImage()->values(),
            'videos' => $realPost->media->filter->isVideo()->values(),
        ]);
    }

    public function edit(Post $post): View
    {
        Gate::authorize('update', $post);

        $post->load('media');

        return view('pages.create-post', [
            'post' => $post,
            'topics' => Post::TOPICS,
            'images' => config('media.images'),
            'videos' => config('media.videos'),
            'existingImages' => $post->media->filter->isImage()->values(),
            'existingVideos' => $post->media->filter->isVideo()->values(),
        ]);
    }

    public function update(UpdatePostRequest $request, Post $post): RedirectResponse
    {
        $disk = config('media.disk');
        $storedPaths = [];

        try {
            $removedMedia = DB::transaction(function () use ($request, $post, $disk, &$storedPaths): Collection {
                $post->update($request->safe()->only(['title', 'topic', 'body']));

                if ($post->isRejected() && $post->user_id === $request->user()->id) {
                    $post->resubmit();
                }

                $removedMedia = $post->media()->whereKey($request->removedMediaIds())->get();

                PostMedia::query()
                    ->where('post_id', $post->id)
                    ->whereKey($removedMedia->modelKeys())
                    ->delete();

                $this->attachUploads($request, $post, $disk, $storedPaths);

                return $removedMedia;
            });
        } catch (Throwable $exception) {
            if ($storedPaths !== []) {
                Storage::disk($disk)->delete($storedPaths);
            }

            report($exception);

            return back()
                ->withInput($request->safe()->only(['title', 'topic', 'body', 'remove_media']))
                ->withErrors(['media' => "We couldn't save your changes. Your post is unchanged, please try again."]);
        }

        $removedMedia->each->deleteFile();

        return redirect()
            ->route('posts.show', $post)
            ->with('status', 'Your post has been updated.');
    }

    public function destroy(Request $request, Post $post): RedirectResponse
    {
        Gate::authorize('delete', $post);

        $post->delete();

        return redirect()
            ->to($request->user()->dashboardUrl())
            ->with('status', 'The post and its media have been deleted.');
    }

    /**
     * Store every validated photo and video from the request and attach them to the post.
     *
     * @param  list<string>  $storedPaths
     */
    private function attachUploads(StorePostRequest $request, Post $post, string $disk, array &$storedPaths): void
    {
        $altTexts = $request->input('alt_text', []);

        foreach ($request->file('photos', []) as $index => $photo) {
            $altText = is_array($altTexts) && is_string($altTexts[$index] ?? null) ? $altTexts[$index] : null;

            $this->attachMedia($post, $photo, MediaType::Image, $disk, $storedPaths, $altText);
        }

        foreach ($request->file('videos', []) as $video) {
            $this->attachMedia($post, $video, MediaType::Video, $disk, $storedPaths);
        }
    }

    /**
     * Store one validated upload under a server-generated name and record it.
     *
     * @param  list<string>  $storedPaths
     */
    private function attachMedia(Post $post, UploadedFile $file, MediaType $type, string $disk, array &$storedPaths, ?string $altText = null): void
    {
        $path = $file->store($type->directory(), $disk);

        if ($path === false) {
            throw new RuntimeException('The uploaded file could not be stored.');
        }

        $storedPaths[] = $path;

        $post->media()->create([
            'media_type' => $type,
            'disk' => $disk,
            'file_path' => $path,
            'original_name' => $this->safeOriginalName($file),
            'alt_text' => $altText !== null ? Str::limit(trim($altText), 150, '') : null,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
        ]);
    }

    private function safeOriginalName(UploadedFile $file): ?string
    {
        $name = preg_replace('/[^A-Za-z0-9 ._-]/', '', basename($file->getClientOriginalName()));

        return $name === null || trim($name) === '' ? null : Str::limit(trim($name), 255, '');
    }
}
