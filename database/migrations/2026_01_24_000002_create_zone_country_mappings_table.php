<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * International zone mapping works differently from domestic: since
     * the business always ships FROM Nigeria, an international shipment
     * only ever needs to know which zone the OTHER country belongs to
     * (e.g. "France = Europe zone") — not a country-pair, since Nigeria
     * is always one fixed side of the route. One row per country.
     */
    public function up(): void
    {
        Schema::create('zone_country_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('zone_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zone_country_mappings');
    }
};
