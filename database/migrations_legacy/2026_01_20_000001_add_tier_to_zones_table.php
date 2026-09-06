<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The standard courier-industry zone-tier model: Zone A (same
     * city/local) through Zone F (remote/hard-to-reach), plus
     * International. This classifies WHAT KIND of zone this is —
     * separate from the actual origin/destination price (still set via
     * ZoneRateMatrix / the Zone Mapping screen) — so staff have a
     * consistent, industry-standard way to reason about coverage and
     * tariff level rather than an arbitrary named zone with no inherent
     * ordering.
     */
    public function up(): void
    {
        Schema::table('zones', function (Blueprint $table) {
            $table->enum('tier', ['A', 'B', 'C', 'D', 'E', 'F', 'international'])->nullable()->after('code');
            $table->string('coverage_description')->nullable()->after('tier');
        });
    }

    public function down(): void
    {
        Schema::table('zones', function (Blueprint $table) {
            $table->dropColumn(['tier', 'coverage_description']);
        });
    }
};
