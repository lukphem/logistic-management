<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Was a DB-level enum (9 fixed values) — switching to a plain string
     * with the allowed values enforced by validation instead
     * (RateCardController), the same pattern already used elsewhere in
     * this app (e.g. Zone::TYPES). Lets new billing models be added going
     * forward without a fragile ALTER-TABLE-on-enum migration every time.
     *
     * Requires doctrine/dbal for the ->change() below:
     *   composer require doctrine/dbal
     */
    public function up(): void
    {
        Schema::table('rate_cards', function (Blueprint $table) {
            $table->string('billing_model', 50)->change();
        });
    }

    public function down(): void
    {
        Schema::table('rate_cards', function (Blueprint $table) {
            $table->enum('billing_model', [
                'flat', 'distance', 'zone_to_zone', 'weight', 'volumetric',
                'hybrid', 'service_multiplier', 'time_surcharge', 'contract',
            ])->change();
        });
    }
};
