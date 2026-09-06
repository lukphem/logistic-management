<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Correction, paired with 2026_01_23_000001: since a route now
     * resolves to exactly ONE zone (via the corrected state-pair
     * zone_mappings), pricing only needs one zone dimension too — not
     * separate From/To zone columns. A (zone, weight band, service type)
     * combination is now enough to find a price.
     */
    public function up(): void
    {
        Schema::table('zone_weight_rates', function (Blueprint $table) {
            $table->dropUnique('zone_weight_rate_unique');
            $table->dropConstrainedForeignId('from_zone_id');
            $table->dropConstrainedForeignId('to_zone_id');
            $table->foreignId('zone_id')->after('rate_card_id')->constrained('zones')->cascadeOnDelete();
            $table->unique(['rate_card_id', 'zone_id', 'min_weight', 'max_weight', 'service_type'], 'zone_weight_rate_unique');
        });
    }

    public function down(): void
    {
        Schema::table('zone_weight_rates', function (Blueprint $table) {
            $table->dropUnique('zone_weight_rate_unique');
            $table->dropConstrainedForeignId('zone_id');
            $table->foreignId('from_zone_id')->after('rate_card_id')->constrained('zones')->cascadeOnDelete();
            $table->foreignId('to_zone_id')->after('from_zone_id')->constrained('zones')->cascadeOnDelete();
            $table->unique(['rate_card_id', 'from_zone_id', 'to_zone_id', 'min_weight', 'max_weight', 'service_type'], 'zone_weight_rate_unique');
        });
    }
};
