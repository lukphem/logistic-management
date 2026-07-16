<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Additive to region_id, not a replacement — Region is a staff-access
     * grouping (Global > Region > Hub > Outlet), City is the actual
     * operating-location geography (Country > State > City) the original
     * "operating countries/states/cities" setup requirement asked for.
     * A hub can have both: a Region for access-scoping purposes and a
     * City for "where is this place, physically."
     */
    public function up(): void
    {
        Schema::table('hubs', function (Blueprint $table) {
            $table->foreignId('city_id')->nullable()->after('region_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('hubs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('city_id');
        });
    }
};
