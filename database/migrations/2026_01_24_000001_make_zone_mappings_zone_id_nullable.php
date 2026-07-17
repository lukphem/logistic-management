<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Domestic mapping is now auto-generated (every combination of
     * Nigeria's states, ~666 pairs) rather than entered one at a time —
     * the generated rows need to start with no zone assigned, since
     * there's no sensible default, and staff fill them in progressively
     * via the inline zone picker on each row.
     */
    public function up(): void
    {
        Schema::table('zone_mappings', function (Blueprint $table) {
            $table->foreignId('zone_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('zone_mappings', function (Blueprint $table) {
            $table->foreignId('zone_id')->nullable(false)->change();
        });
    }
};
