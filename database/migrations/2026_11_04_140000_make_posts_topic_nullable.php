<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            // Fresh installs already create topic as nullable.
            return;
        }

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE posts MODIFY topic VARCHAR(50) NULL');
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("UPDATE posts SET topic = '' WHERE topic IS NULL");
            DB::statement('ALTER TABLE posts MODIFY topic VARCHAR(50) NOT NULL');
        }
    }
};
