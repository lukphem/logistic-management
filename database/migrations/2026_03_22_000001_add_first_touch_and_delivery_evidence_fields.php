<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * is_first_touch marks which statuses are valid as the very first
     * scan a shipment ever receives after booking — Picked Up (rider
     * collected from sender) and the new Dropped Off (walk-in handed
     * it over at a counter themselves). A freshly booked shipment
     * can't be arrival/departure/delivery/exception-scanned until one
     * of these has happened first — it has to physically be in the
     * company's hands before anything else makes sense.
     *
     * destination_hub_id on scan_events captures "where it's heading"
     * for a Departure Scan specifically — a single shipment leaving
     * on its own, outside any formal manifest, still needs to say
     * where it's going, the same way a manifest already does.
     *
     * receiver_name on scan_events is who actually signed for the
     * delivery — often not the shipment's own registered
     * receiver_name (a neighbour, a gatekeeper, front-desk staff),
     * so this is deliberately separate rather than assumed to match.
     */
    public function up(): void
    {
        Schema::table('scan_statuses', function (Blueprint $table) {
            $table->boolean('is_first_touch')->default(false)->after('is_delivery_attempt');
        });

        Schema::table('scan_events', function (Blueprint $table) {
            $table->foreignId('destination_hub_id')->nullable()->after('outlet_id')->constrained('hubs')->nullOnDelete();
            $table->string('receiver_name')->nullable()->after('signature_path');
        });
    }

    public function down(): void
    {
        Schema::table('scan_statuses', function (Blueprint $table) {
            $table->dropColumn('is_first_touch');
        });

        Schema::table('scan_events', function (Blueprint $table) {
            $table->dropConstrainedForeignId('destination_hub_id');
            $table->dropColumn('receiver_name');
        });
    }
};
