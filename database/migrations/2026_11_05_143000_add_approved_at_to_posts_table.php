<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add an approval timestamp used for the 30-day post lifetime.
     *
     * Backfill policy for existing approved posts:
     * use moderated_at when present (admin approval/moderation time),
     * otherwise fall back to created_at (auto-approved posts that never
     * received a moderation stamp). This is the best available approximation
     * and is documented for operators — it is not invented silently.
     */
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table): void {
            $table->timestamp('approved_at')->nullable()->after('moderated_at');
            $table->index(['status', 'approved_at']);
        });

        DB::table('posts')
            ->where('status', 'approved')
            ->whereNull('approved_at')
            ->update([
                'approved_at' => DB::raw('COALESCE(moderated_at, created_at)'),
            ]);
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table): void {
            $table->dropIndex(['status', 'approved_at']);
            $table->dropColumn('approved_at');
        });
    }
};
