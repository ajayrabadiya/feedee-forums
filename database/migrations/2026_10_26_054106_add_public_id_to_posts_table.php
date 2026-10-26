<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->string('public_id', 12)->nullable()->unique()->after('id');
        });

        DB::table('posts')->whereNull('public_id')->orderBy('id')->each(function (object $post) {
            do {
                $publicId = Str::random(8);
            } while (DB::table('posts')->where('public_id', $publicId)->exists());

            DB::table('posts')->where('id', $post->id)->update(['public_id' => $publicId]);
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->string('public_id', 12)->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropUnique(['public_id']);
            $table->dropColumn('public_id');
        });
    }
};
