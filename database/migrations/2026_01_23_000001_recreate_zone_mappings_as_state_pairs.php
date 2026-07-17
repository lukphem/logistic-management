<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Correction to Increment 32's zone_mappings design. The actual
     * requirement: a route between two STATES — regardless of
     * direction — equates to ONE zone. "Abuja to Lagos = Zone 2" and
     * "Lagos to Abuja" are the same mapping, not two. So this is a
     * state-PAIR → zone lookup, not a single-state (or single-city)
     * assignment.
     *
     * state_a_id/state_b_id are always stored normalized
     * (lower ID first) by ZoneMapping::booted()'s saving hook, so a
     * lookup never needs to check both orderings or store both
     * directions as separate rows — one row covers the route both ways.
     *
     * No production data depends on the old structure yet (this whole
     * feature was introduced one increment ago), so this drops and
     * recreates rather than carrying forward a design that's simply
     * wrong.
     */
    public function up(): void
    {
        Schema::dropIfExists('zone_mappings');

        Schema::create('zone_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('state_a_id')->constrained('states')->cascadeOnDelete();
            $table->foreignId('state_b_id')->constrained('states')->cascadeOnDelete();
            $table->foreignId('zone_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['state_a_id', 'state_b_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zone_mappings');

        Schema::create('zone_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('city_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('zone_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }
};
