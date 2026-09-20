<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A ScanBatch is the persisted record behind a TRF-/DEL- reference
     * number — previously these were generated purely for display at
     * print time and never saved anywhere, so a document couldn't be
     * looked up or reprinted later the way a manifest or trip number
     * already could be. One table covers both kinds (kind: transfer
     * or delivery) since they're the same underlying shape — a batch
     * of shipments, an origin, who handled it, when — just used for
     * two different departure outcomes.
     */
    public function up(): void
    {
        Schema::create('scan_batches', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->enum('kind', ['transfer', 'delivery']);
            $table->string('status_key');
            $table->foreignId('origin_hub_id')->nullable()->constrained('hubs')->nullOnDelete();
            $table->foreignId('origin_outlet_id')->nullable()->constrained('outlets')->nullOnDelete();
            $table->foreignId('origin_unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->foreignId('destination_hub_id')->nullable()->constrained('hubs')->nullOnDelete();
            $table->foreignId('destination_unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->foreignId('handed_to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('scan_batch_shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scan_batch_id')->constrained('scan_batches')->cascadeOnDelete();
            $table->foreignId('shipment_id')->constrained('shipments')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['scan_batch_id', 'shipment_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scan_batch_shipments');
        Schema::dropIfExists('scan_batches');
    }
};
