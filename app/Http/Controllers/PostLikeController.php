<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PostLikeController extends Controller
{
    /**
     * Like or unlike a post for the signed-in member.
     */
    public function __invoke(Request $request, Post $post): JsonResponse|RedirectResponse
    {
        Gate::authorize('like', $post);

        $user = $request->user();
        $existing = $post->likes()->where('user_id', $user->id)->first();

        if ($existing) {
            $existing->delete();
            $liked = false;
        } else {
            try {
                $post->likes()->forceCreate(['user_id' => $user->id]);
            } catch (UniqueConstraintViolationException) {
                // A second click raced the first one; the like already exists.
            }
            $liked = true;
        }

        $count = $post->likes()->count();

        if ($request->expectsJson()) {
            return response()->json(['liked' => $liked, 'count' => $count]);
        }

        return redirect()->back(fallback: route('posts.show', $post));
    }
}
