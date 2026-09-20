<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * "In Transit" was too generic for what Departure Scan actually
     * uses it for — a local, unit-to-unit transfer, not a description
     * of movement in general. Renamed to describe what's actually
     * happening: the shipment is being handed to another local unit
     * for further processing. A real migration rather than a seeder
     * change, since firstOrCreate() never touches an existing row —
     * this needs to reach installs where 'in_transit' was already
     * seeded under the old label. Staff can still rename it again
     * from the Scan Statuses page if they'd rather word it
     * differently; this only changes the shipped default.
     */
    public function up(): void
    {
        DB::table('scan_statuses')
            ->where('key', 'in_transit')
            ->where('label', 'In Transit')
            ->update(['label' => 'Transfer to Another Unit for Processing']);
    }

    /**
     * Deliberately no-op — reversing this would mean overwriting
     * whatever label a company has since set for this status, which
     * isn't a safe assumption to make automatically.
     */
    public function down(): void
    {
    }
};
