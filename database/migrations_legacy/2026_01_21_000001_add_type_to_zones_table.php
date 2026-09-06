<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The primary, required classification when creating a zone —
     * simpler than picking a tier: just Domestic or International. The
     * A–F tier (Increment 30) becomes an optional sub-classification
     * that only makes sense for domestic zones (a "Zone C" doesn't mean
     * anything for an international zone grouped by region) — so
     * `type` is what's actually required, `tier` stays optional and is
     * only offered in the UI when type = domestic.
     */
    public function up(): void
    {
        Schema::table('zones', function (Blueprint $table) {
            $table->enum('type', ['domestic', 'international'])->default('domestic')->after('code');
        });
    }

    public function down(): void
    {
        Schema::table('zones', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
