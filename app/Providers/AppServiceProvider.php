<?php

namespace App\Providers;

use App\Enums\PostStatus;
use App\Models\Post;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('admin.partials.sidebar', function ($view): void {
            $view->with('pendingPostsCount', Post::query()->where('status', PostStatus::Pending)->count());
        });
    }
}
