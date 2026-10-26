<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePostRequest;
use App\Models\Post;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PostController extends Controller
{
    public function create(): View
    {
        return view('pages.create-post', [
            'topics' => Post::TOPICS,
        ]);
    }

    public function store(StorePostRequest $request): RedirectResponse
    {
        $request->user()->posts()->create($request->safe()->only(['title', 'topic', 'body']));

        return redirect()->route('dashboard')->with('status', 'Thanks for sharing! Your post has been published.');
    }
}
