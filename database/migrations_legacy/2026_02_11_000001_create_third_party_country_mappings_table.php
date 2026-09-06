<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * For shipments this business arranges between two OTHER countries
     * (e.g. US to Congo, via a third-party arrangement) — neither side
     * is Nigeria, unlike every other international mapping in this
     * system. Kept in its own table rather than relaxing
     * ZoneCountryMapping's country_a_id: that table's country_a is
     * ALWAYS Nigeria by design (the International Mapping screen
     * displays it that way), and mixing in genuinely-arbitrary pairs
     * would break that invariant.
     *
     * Same bidirectional-pair shape as domestic ZoneMapping — normalized
     * (lower id first) with a unique constraint, not bulk-generated the
     * way domestic or the Nigeria-anchored international table are.
     * With ~178 countries, pre-generating every possible pair would be
     * tens of thousands of rows for what's an occasional arrangement,
     * not a routine one — this is added to manually, one real route at
     * a time.
     */
    public function up(): void
    {
        Schema::create('third_party_country_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_a_id')->constrained('countries')->cascadeOnDelete();
            $table->foreignId('country_b_id')->constrained('countries')->cascadeOnDelete();
            $table->foreignId('zone_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->unique(['country_a_id', 'country_b_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('third_party_country_mappings');
    }
};
