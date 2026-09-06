<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Configurable scan-status list per client, per the original spec
     * ("Configurable scan status list... custom status labels/order per
     * company"). ScanEvent.status and Shipment.current_status remain plain
     * strings (see increment 2) — this table is the editable reference
     * list the setup UI manages; it's descriptive/config, not a foreign key
     * constraint on those columns, so relabeling never breaks history.
     */
    public function up(): void
    {
        Schema::create('scan_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // stable identifier stored on shipments/scan_events, e.g. "out_for_delivery"
            $table->string('label'); // display label, editable per client, e.g. "Out for Delivery"
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_terminal')->default(false); // true for delivered/cancelled/returned — ends the shipment lifecycle
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scan_statuses');
    }
};
