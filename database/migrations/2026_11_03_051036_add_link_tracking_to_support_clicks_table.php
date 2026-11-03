<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Rows with a platform are clicks on a payment link (YouPay, Cash App, ...);
     * rows without one are clicks that open the Support button. Full IP
     * addresses and user agents are replaced by a keyed visitor hash.
     */
    public function up(): void
    {
        Schema::table('support_clicks', function (Blueprint $table) {
            $table->string('platform', 20)->nullable()->after('user_id');
            $table->string('support_url')->nullable()->after('platform');
            $table->char('country_code', 2)->nullable()->after('page_path');
            $table->string('country_name', 80)->nullable()->after('country_code');
            $table->char('visitor_hash', 64)->nullable()->after('country_name');
            $table->string('filtered_as', 20)->nullable()->after('visitor_hash');

            $table->index(['platform', 'clicked_at']);
            $table->index(['country_code', 'clicked_at']);
        });

        DB::table('support_clicks')->whereNull('visitor_hash')->orderBy('id')->chunkById(500, function ($clicks): void {
            foreach ($clicks as $click) {
                $identity = $click->user_id !== null
                    ? 'member:'.$click->user_id
                    : 'guest:'.$click->ip_address.'|'.$click->user_agent;

                DB::table('support_clicks')->where('id', $click->id)->update([
                    'visitor_hash' => hash_hmac('sha256', $identity, (string) config('app.key')),
                ]);
            }
        });

        Schema::table('support_clicks', function (Blueprint $table) {
            $table->dropColumn(['ip_address', 'user_agent']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('support_clicks', function (Blueprint $table) {
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
        });

        Schema::table('support_clicks', function (Blueprint $table) {
            $table->dropIndex(['platform', 'clicked_at']);
            $table->dropIndex(['country_code', 'clicked_at']);
            $table->dropColumn(['platform', 'support_url', 'country_code', 'country_name', 'visitor_hash', 'filtered_as']);
        });
    }
};
