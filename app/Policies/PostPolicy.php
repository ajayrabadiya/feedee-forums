<?php

namespace App\Policies;

use App\Models\Post;
use App\Models\User;

class PostPolicy
{
    /**
     * Approved posts are public; pending and rejected posts are only visible
     * to their author and administrators.
     */
    public function view(?User $user, Post $post): bool
    {
        if ($post->isApproved()) {
            return true;
        }

        return $user !== null && ($user->isAdmin() || $post->user_id === $user->id);
    }

    /**
     * Any signed-in member (customer or administrator) may create posts.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Only the post's author or an administrator may edit it (and manage its media).
     */
    public function update(User $user, Post $post): bool
    {
        return $user->isAdmin() || $post->user_id === $user->id;
    }

    /**
     * Only the post's author or an administrator may delete it (and its media).
     */
    public function delete(User $user, Post $post): bool
    {
        return $user->isAdmin() || $post->user_id === $user->id;
    }

    /**
     * Only administrators may approve, reject or feature posts.
     */
    public function moderate(User $user): bool
    {
        return $user->isAdmin();
    }
}
