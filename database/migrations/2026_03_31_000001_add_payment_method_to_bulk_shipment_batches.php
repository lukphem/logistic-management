<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mirrors the single-shipment form's own payment_method field —
     * left null for an account-based (deferred) batch, set to
     * 'cash' or 'paystack' for a walk-in or non-credit account
     * actually paying now. A credit account ignores this regardless
     * (enforced centrally in ShipmentCreationService, not here) —
     * this column just carries what was actually chosen at Step 1
     * through to every shipment the batch produces.
     */
    public function up(): void
    {
        Schema::table('bulk_shipment_batches', function (Blueprint $table) {
            $table->string('payment_method')->nullable()->after('origin_city_id');
        });
    }

    public function down(): void
    {
        Schema::table('bulk_shipment_batches', function (Blueprint $table) {
            $table->dropColumn('payment_method');
        });
    }
};
