<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Plain optional text on all three — unlike `code` (Increment 19),
     * postal_code is a real-world value staff type directly (e.g. a
     * state's general postal prefix, a city's postal code, a district's
     * specific one) rather than something auto-composed from a parent.
     */
    public function up(): void
    {
        Schema::table('states', function (Blueprint $table) {
            $table->string('postal_code')->nullable()->after('code');
        });

        Schema::table('cities', function (Blueprint $table) {
            $table->string('postal_code')->nullable()->after('code');
        });

        Schema::table('districts', function (Blueprint $table) {
            $table->string('postal_code')->nullable()->after('code');
        });
    }

    public function down(): void
    {
        Schema::table('states', function (Blueprint $table) {
            $table->dropColumn('postal_code');
        });

        Schema::table('cities', function (Blueprint $table) {
            $table->dropColumn('postal_code');
        });

        Schema::table('districts', function (Blueprint $table) {
            $table->dropColumn('postal_code');
        });
    }
};
