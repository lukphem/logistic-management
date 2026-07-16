<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mirrors origin_hub_id — the hub responsible for final delivery,
     * resolved the same way (explicit choice, then home-city match, then
     * state coverage). Together the two give the waybill code its
     * origin-destination shape, e.g. "LOS-PHC-...".
     */
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->foreignId('destination_hub_id')->nullable()->after('origin_hub_id')->constrained('hubs')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('destination_hub_id');
        });
    }
};
