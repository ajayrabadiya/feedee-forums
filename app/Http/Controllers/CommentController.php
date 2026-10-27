<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommentRequest;
use App\Models\Comment;
use App\Models\Post;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class CommentController extends Controller
{
    public function store(StoreCommentRequest $request, Post $post): RedirectResponse
    {
        $comment = new Comment($request->safe()->only(['body']));
        $comment->user()->associate($request->user());
        $post->comments()->save($comment);

        return redirect()
            ->to(route('posts.show', $post).'#comment-'.$comment->id)
            ->with('status', 'Your comment has been posted.');
    }

    public function destroy(Comment $comment): RedirectResponse
    {
        Gate::authorize('delete', $comment);

        $post = $comment->post;
        $comment->delete();

        return redirect()
            ->to(route('posts.show', $post).'#comments')
            ->with('status', 'Comment deleted.');
    }
}
