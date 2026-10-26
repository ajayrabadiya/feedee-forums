<?php

namespace App\Http\Controllers;

use App\Enums\MediaType;
use App\Http\Requests\StorePostRequest;
use App\Models\Post;
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
    public function create(): View
    {
        return view('pages.create-post', [
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
                $post = $request->user()->posts()->create($request->safe()->only(['title', 'topic', 'body']));

                $altTexts = $request->input('alt_text', []);

                foreach ($request->file('photos', []) as $index => $photo) {
                    $altText = is_array($altTexts) && is_string($altTexts[$index] ?? null) ? $altTexts[$index] : null;

                    $this->attachMedia($post, $photo, MediaType::Image, $disk, $storedPaths, $altText);
                }

                foreach ($request->file('videos', []) as $video) {
                    $this->attachMedia($post, $video, MediaType::Video, $disk, $storedPaths);
                }

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
            ->with('status', 'Thanks for sharing! Your post has been published.');
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

        return view('pages.post-show', [
            'post' => $realPost,
            'images' => $realPost->media->filter->isImage()->values(),
            'videos' => $realPost->media->filter->isVideo()->values(),
        ]);
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
