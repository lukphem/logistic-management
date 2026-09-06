<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Completes the access scale alongside the existing hub_id
     * (Migration 2026_01_06_000001): a user is scoped to exactly one of
     * three levels — both null = global, region_id set = every hub in
     * that region, hub_id set = that one hub only. See
     * User::hasGlobalAccess() / hasRegionAccess() / accessibleHubIds()
     * for how these combine — the three are mutually exclusive, not
     * stacked filters.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('region_id')->nullable()->after('user_type')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('region_id');
        });
    }
};
