<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PostStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Number of days shown in the "Posts this week" chart.
     */
    private const CHART_DAYS = 7;

    public function __invoke(): View
    {
        $today = now()->startOfDay();

        $approvedToday = $this->approvedBetween($today, $today->copy()->endOfDay());
        $approvedYesterday = $this->approvedBetween($today->copy()->subDay(), $today->copy()->subSecond());

        return view('admin.dashboard', [
            'stats' => [
                'pending' => Post::query()->where('status', PostStatus::Pending)->count(),
                'pendingToday' => Post::query()->where('status', PostStatus::Pending)->where('created_at', '>=', $today)->count(),
                'unreadMessages' => ContactMessage::query()->unread()->count(),
                'messagesToday' => ContactMessage::query()->where('created_at', '>=', $today)->count(),
                'approvedToday' => $approvedToday,
                'approvedYesterday' => $approvedYesterday,
                'members' => User::query()->where('role', UserRole::Customer)->count(),
                'membersThisWeek' => User::query()->where('role', UserRole::Customer)->where('created_at', '>=', now()->subDays(7))->count(),
            ],
            'chart' => $this->chart($today),
            'pendingPosts' => Post::query()
                ->where('status', PostStatus::Pending)
                ->with(['user', 'media'])
                ->oldest()
                ->oldest('id')
                ->take(5)
                ->get(),
            'latestMessages' => ContactMessage::query()->latest()->latest('id')->take(5)->get(),
        ]);
    }

    /**
     * Posts approved in a time window. Auto-approved posts (no moderator) count on their creation date.
     */
    private function approvedBetween(Carbon $from, Carbon $to): int
    {
        return Post::query()
            ->where('status', PostStatus::Approved)
            ->where(function ($query) use ($from, $to): void {
                $query->whereBetween('moderated_at', [$from, $to])
                    ->orWhere(fn ($query) => $query->whereNull('moderated_at')->whereBetween('created_at', [$from, $to]));
            })
            ->count();
    }

    /**
     * Submitted vs. approved posts for each of the last seven days.
     *
     * @return Collection<int, array{label: string, date: string, submitted: int, approved: int, submittedHeight: int, approvedHeight: int}>
     */
    private function chart(Carbon $today): Collection
    {
        $start = $today->copy()->subDays(self::CHART_DAYS - 1);

        $submitted = Post::query()
            ->where('created_at', '>=', $start)
            ->pluck('created_at')
            ->countBy(fn (Carbon $date) => $date->toDateString());

        $approved = Post::query()
            ->where('status', PostStatus::Approved)
            ->where(fn ($query) => $query->where('moderated_at', '>=', $start)
                ->orWhere(fn ($query) => $query->whereNull('moderated_at')->where('created_at', '>=', $start)))
            ->get(['created_at', 'moderated_at'])
            ->countBy(fn (Post $post) => ($post->moderated_at ?? $post->created_at)->toDateString());

        $days = collect(range(0, self::CHART_DAYS - 1))->map(function (int $offset) use ($start, $submitted, $approved) {
            $date = $start->copy()->addDays($offset);
            $key = $date->toDateString();

            return [
                'label' => $date->isToday() ? 'Today' : $date->format('D'),
                'date' => $date->format('M j'),
                'submitted' => (int) ($submitted[$key] ?? 0),
                'approved' => (int) ($approved[$key] ?? 0),
            ];
        });

        $max = max(1, $days->max('submitted'), $days->max('approved'));

        return $days->map(fn (array $day) => [
            ...$day,
            'submittedHeight' => (int) round($day['submitted'] / $max * 90),
            'approvedHeight' => (int) round($day['approved'] / $max * 90),
        ]);
    }
}
