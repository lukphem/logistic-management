<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Groups states together purely for domestic zone auto-determination
     * (see ZoneMapping::determineDefaultZoneTier) — e.g. a "South West"
     * territory containing Lagos, Ogun, Oyo, Osun, Ondo, Ekiti. Not the
     * same thing as Region (Increment 13, an access-scope grouping) —
     * Territory exists specifically for the zone-tier rule.
     */
    public function up(): void
    {
        Schema::create('territories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('territories');
    }
};
