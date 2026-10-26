<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->string('status', 20)->default('approved')->after('body');
            $table->boolean('is_featured')->default(false)->after('status');
            $table->timestamp('featured_at')->nullable()->after('is_featured');
            $table->string('rejection_reason', 100)->nullable()->after('featured_at');
            $table->text('rejection_note')->nullable()->after('rejection_reason');
            $table->foreignId('moderated_by')->nullable()->after('rejection_note')->constrained('users')->nullOnDelete();
            $table->timestamp('moderated_at')->nullable()->after('moderated_by');

            $table->index(['status', 'created_at']);
            $table->index(['is_featured', 'featured_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropIndex(['status', 'created_at']);
            $table->dropIndex(['is_featured', 'featured_at']);
            $table->dropConstrainedForeignId('moderated_by');
            $table->dropColumn(['status', 'is_featured', 'featured_at', 'rejection_reason', 'rejection_note', 'moderated_at']);
        });
    }
};
