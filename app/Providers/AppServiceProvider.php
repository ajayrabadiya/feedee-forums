<?php

namespace App\Providers;

use App\Enums\PostStatus;
use App\Models\ContactMessage;
use App\Models\Post;
use App\Models\Setting;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Throwable;

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
        Setting::applyMailConfig();

        View::composer('partials.support-fab', function ($view): void {
            try {
                $view->with('support', Setting::supportButton());
            } catch (Throwable) {
                $view->with('support', ['enabled' => false, 'links' => []]);
            }
        });

        View::composer('admin.partials.sidebar', function ($view): void {
            $view->with('pendingPostsCount', Post::query()->where('status', PostStatus::Pending)->count());
            $view->with('unreadContactsCount', ContactMessage::query()->unread()->count());
        });
    }
}
