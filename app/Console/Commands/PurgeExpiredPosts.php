<?php

namespace App\Console\Commands;

use App\Models\Post;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

#[Signature('posts:purge-expired {--chunk=100 : Number of posts to delete per chunk}')]
#[Description('Permanently delete approved posts whose 30-day lifetime since approval has ended')]
class PurgeExpiredPosts extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $chunk = max(1, (int) $this->option('chunk'));
        $deleted = 0;
        $failed = 0;

        $this->components->info('Purging approved posts that expired '.$this->expiryCutoff()->toDateTimeString().' UTC or earlier.');

        Post::query()
            ->expired()
            ->orderBy('id')
            ->chunkById($chunk, function ($posts) use (&$deleted, &$failed): void {
                foreach ($posts as $post) {
                    try {
                        // Model deleting/deleted events load media and remove files after commit.
                        // Comments, likes and media rows cascade at the database level.
                        $post->delete();
                        $deleted++;
                    } catch (Throwable $exception) {
                        $failed++;
                        report($exception);
                        Log::error('posts:purge-expired failed for post', [
                            'post_id' => $post->id,
                            'public_id' => $post->public_id,
                            'message' => $exception->getMessage(),
                        ]);
                        $this->components->error("Failed to delete post #{$post->public_id}: {$exception->getMessage()}");
                    }
                }
            });

        Log::info('posts:purge-expired finished', [
            'deleted' => $deleted,
            'failed' => $failed,
            'cutoff' => $this->expiryCutoff()->toIso8601String(),
        ]);

        $this->components->info("Deleted {$deleted} expired post(s).".($failed > 0 ? " Failed: {$failed}." : ''));

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function expiryCutoff(): \Illuminate\Support\Carbon
    {
        return now()->subDays(Post::LIFETIME_DAYS);
    }
}
