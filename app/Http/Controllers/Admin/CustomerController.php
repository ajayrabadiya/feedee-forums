<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PostStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;

class CustomerController extends Controller
{
    /**
     * Number of days without posting before a customer counts as inactive.
     */
    public const ACTIVE_DAYS = 30;

    /**
     * List every customer account with their posting activity.
     */
    public function __invoke(): View
    {
        $customers = User::query()
            ->where('role', UserRole::Customer)
            ->withCount([
                'posts',
                'posts as approved_posts_count' => fn (Builder $query) => $query->where('status', PostStatus::Approved),
                'posts as pending_posts_count' => fn (Builder $query) => $query->where('status', PostStatus::Pending),
                'posts as rejected_posts_count' => fn (Builder $query) => $query->where('status', PostStatus::Rejected),
            ])
            ->withMax('posts', 'created_at')
            ->latest()
            ->latest('id')
            ->get();

        $activeSince = now()->subDays(self::ACTIVE_DAYS);

        return view('admin.customers', [
            'customers' => $customers,
            'activeSince' => $activeSince,
            'stats' => [
                'total' => $customers->count(),
                'joinedThisWeek' => $customers->where('created_at', '>=', now()->subDays(7))->count(),
                'active' => $customers->filter(fn (User $customer) => $customer->posts_max_created_at !== null
                    && $activeSince->lte($customer->posts_max_created_at))->count(),
                'pendingPosts' => Post::query()
                    ->where('status', PostStatus::Pending)
                    ->whereHas('user', fn (Builder $query) => $query->where('role', UserRole::Customer))
                    ->count(),
            ],
        ]);
    }
}
