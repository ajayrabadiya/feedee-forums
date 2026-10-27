<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;

class CommentPolicy
{
    /**
     * Members can comment on approved posts only.
     */
    public function create(User $user, Post $post): bool
    {
        return $post->isApproved();
    }

    /**
     * A comment can be removed by its author, the post's author or an administrator.
     */
    public function delete(User $user, Comment $comment): bool
    {
        return $user->isAdmin()
            || $comment->user_id === $user->id
            || $comment->post->user_id === $user->id;
    }
}
