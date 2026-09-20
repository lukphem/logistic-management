<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A Departure Scan's destination is either a different hub in the
     * same city (destination_hub_id, already existed) or a different
     * unit within the SAME hub (destination_unit_id, new here) — two
     * genuinely different kinds of "heading to," not one field
     * pressed into double duty. Kept separate so the existing
     * same-place guard (destination hub can't equal the origin hub)
     * never collides with a legitimate unit-to-unit transfer, which
     * by definition stays within the origin hub.
     */
    public function up(): void
    {
        Schema::table('scan_events', function (Blueprint $table) {
            $table->foreignId('destination_unit_id')->nullable()->after('destination_hub_id')->constrained('units')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('scan_events', function (Blueprint $table) {
            $table->dropConstrainedForeignId('destination_unit_id');
        });
    }
};
