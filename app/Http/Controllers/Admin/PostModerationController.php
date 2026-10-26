<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PostStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ModeratePostsRequest;
use App\Models\Post;
use App\Models\Setting;
use App\Notifications\PostRejected;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Throwable;

class PostModerationController extends Controller
{
    public function index(): View
    {
        return view('admin.posts', [
            'posts' => Post::query()->with(['user', 'media'])->latest()->latest('id')->get(),
            'topics' => Post::TOPICS,
            'rejectionReasons' => Post::REJECTION_REASONS,
            'requiresApproval' => Setting::requiresPostApproval(),
        ]);
    }

    /**
     * Apply one moderation action to one or more posts.
     */
    public function moderate(ModeratePostsRequest $request): JsonResponse
    {
        $action = $request->validated('action');
        $moderator = $request->user();
        $posts = Post::query()->whereKey($request->validated('ids'))->with('user')->get();
        $rejected = collect();

        try {
            DB::transaction(function () use ($action, $moderator, $posts, $request, $rejected): void {
                foreach ($posts as $post) {
                    match ($action) {
                        'approve' => $post->approve($moderator),
                        'feature' => $post->feature($moderator),
                        'unfeature' => $post->unfeature(),
                        'delete' => $post->delete(),
                        'reject' => $post->isRejected() ? null : $rejected->push(
                            tap($post)->reject($moderator, $request->validated('reason'), $request->validated('note')),
                        ),
                    };
                }
            });
        } catch (Throwable $exception) {
            report($exception);

            return response()->json(['message' => "Something went wrong. The selected posts weren't changed."], 500);
        }

        foreach ($rejected as $post) {
            $post->user?->notify(new PostRejected($post));
        }

        return response()->json([
            'message' => $this->message($action, $posts->count()),
            'posts' => $action === 'delete' ? [] : $posts->map(fn (Post $post) => [
                'id' => $post->id,
                'status' => $post->status->value,
                'featured' => $post->is_featured,
            ])->values(),
            'counts' => $this->counts(),
        ]);
    }

    private function message(string $action, int $count): string
    {
        $subject = $count === 1 ? 'Post' : "{$count} posts";

        return match ($action) {
            'approve' => "{$subject} approved and published to the feed.",
            'reject' => "{$subject} rejected. The author has been emailed the reason.",
            'feature' => "{$subject} featured at the top of the feed.",
            'unfeature' => "{$subject} removed from featured.",
            'delete' => "{$subject} and all media permanently deleted.",
        };
    }

    /**
     * @return array<string, int>
     */
    private function counts(): array
    {
        return [
            'pending' => Post::query()->where('status', PostStatus::Pending)->count(),
        ];
    }
}
