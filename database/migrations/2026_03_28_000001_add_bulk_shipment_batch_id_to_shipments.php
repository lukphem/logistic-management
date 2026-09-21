<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Without this, a bulk batch's own number was trackable as a
     * record but had no way to show which shipments actually came
     * from it — the batch listing and its printable document both
     * need to list the real shipments created under a given batch,
     * not just the batch's own shipper/service details.
     */
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->foreignId('bulk_shipment_batch_id')->nullable()->after('client_account_id')->constrained('bulk_shipment_batches')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('bulk_shipment_batch_id');
        });
    }
};
