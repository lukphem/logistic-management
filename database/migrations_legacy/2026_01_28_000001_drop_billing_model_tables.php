<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Clears the whole billing-model calculation layer — rate_cards and
     * every table that hung off it (zone_rate_matrix, zone_weight_rates,
     * carton_rates) — to be rebuilt one model at a time, each discussed
     * and configured deliberately rather than all 12 at once. Nothing
     * else in billing is touched: ClientBillingProfile (discounts),
     * OnforwardingClassification, Zone/ZoneMapping/ZoneCountryMapping,
     * and every price column already on shipments all stay exactly as
     * they are — none of that is billing-MODEL logic, it's billing
     * context the rebuilt models will still plug into.
     */
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('rate_card_id');
        });

        Schema::dropIfExists('carton_rates');
        Schema::dropIfExists('zone_weight_rates');
        Schema::dropIfExists('zone_rate_matrix');
        Schema::dropIfExists('rate_cards');
    }

    public function down(): void
    {
        // Deliberately not reversible — the whole point is a clean
        // rebuild, not a system that can un-delete itself into the
        // exact same 12-model sprawl this migration removes.
    }
};
