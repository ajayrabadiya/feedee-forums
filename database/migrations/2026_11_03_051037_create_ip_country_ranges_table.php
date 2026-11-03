<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * IP ranges and their country, filled by `php artisan app:import-geoip`. Addresses are stored as
     * 32-character hex strings of the 16-byte IPv6 form (IPv4 is IPv4-mapped), so plain string
     * comparison orders them numerically on every database.
     */
    public function up(): void
    {
        $isMysql = in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'], true);

        Schema::create('ip_country_ranges', function (Blueprint $table) use ($isMysql) {
            $ipFrom = $table->char('ip_from', 32)->primary();
            $ipTo = $table->char('ip_to', 32);
            $table->char('country_code', 2);

            if ($isMysql) {
                $ipFrom->charset('ascii')->collation('ascii_bin');
                $ipTo->charset('ascii')->collation('ascii_bin');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ip_country_ranges');
    }
};
