<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mirrors Standard Billing's Domestic/International split onto
     * Origin to Destination — one side stays a Nigeria state (+
     * optional city), the other can now be a country instead. Exactly
     * one of state/country is expected per side (enforced in
     * OriginDestinationTariffController's validation, not a DB
     * constraint — a CHECK constraint isn't worth the MySQL/SQLite
     * portability cost for this).
     *
     * origin_state_id/destination_state_id become nullable — a
     * genuinely international row (e.g. a country on the origin side)
     * has no Nigeria state on that side at all.
     */
    public function up(): void
    {
        Schema::table('origin_destination_tariffs', function (Blueprint $table) {
            $table->foreignId('origin_country_id')->nullable()->after('origin_city_id')->constrained('countries')->cascadeOnDelete();
            $table->foreignId('destination_country_id')->nullable()->after('destination_city_id')->constrained('countries')->cascadeOnDelete();
        });

        Schema::table('origin_destination_tariffs', function (Blueprint $table) {
            $table->foreignId('origin_state_id')->nullable()->change();
            $table->foreignId('destination_state_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('origin_destination_tariffs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('origin_country_id');
            $table->dropConstrainedForeignId('destination_country_id');
        });

        Schema::table('origin_destination_tariffs', function (Blueprint $table) {
            $table->foreignId('origin_state_id')->nullable(false)->change();
            $table->foreignId('destination_state_id')->nullable(false)->change();
        });
    }
};
