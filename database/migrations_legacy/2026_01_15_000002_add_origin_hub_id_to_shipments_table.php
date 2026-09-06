<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Distinct from current_hub_id (which tracks where the shipment is
     * RIGHT NOW and changes as it moves through the network) —
     * origin_hub_id is fixed at booking time: the hub that picked up /
     * originated the shipment. That's what the waybill number is coded
     * with, so it never changes even after the shipment moves elsewhere.
     */
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->foreignId('origin_hub_id')->nullable()->after('current_outlet_id')->constrained('hubs')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('origin_hub_id');
        });
    }
};
