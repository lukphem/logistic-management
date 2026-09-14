<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * payment_status is a real lifecycle state (unpaid/paid/failed),
     * same reasoning as ClientAccount's status field — self-documenting
     * in queries/exports compared to a boolean, and leaves room for
     * more states later (refunded, etc.) without another migration.
     *
     * payment_reference stores Paystack's own transaction reference
     * (their generated one, or ours if we generate it) — this is
     * what ties a shipment back to a specific Paystack transaction
     * for verification/lookup, independent of the tracking number.
     */
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->enum('payment_status', ['unpaid', 'paid', 'failed'])->default('unpaid')->after('total_amount');
            $table->string('payment_reference')->nullable()->after('payment_status');
            $table->timestamp('paid_at')->nullable()->after('payment_reference');
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropColumn(['payment_status', 'payment_reference', 'paid_at']);
        });
    }
};
