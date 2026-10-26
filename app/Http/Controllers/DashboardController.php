<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Show the signed-in user's own account overview and posts.
     */
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        return view('pages.dashboard', [
            'user' => $user,
            'recentPosts' => $user->posts()->with('media')->latest()->take(10)->get(),
            'postCount' => $user->posts()->count(),
            'livePostCount' => $user->posts()->where('created_at', '>=', now()->subDays(Post::LIFETIME_DAYS))->count(),
        ]);
    }
}
