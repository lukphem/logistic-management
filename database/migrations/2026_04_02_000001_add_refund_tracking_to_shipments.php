<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * There's no in-app refund processing (no Paystack refund API
     * call, no cash-settlement reversal) — that's a separate,
     * substantial feature. This is the minimal, audit-safe interim:
     * a place to record that a refund happened outside the app
     * (cash handed back, a Paystack refund issued through their
     * dashboard) before the system will let a paid shipment actually
     * be cancelled, so a cancellation still can't silently forget
     * that money changed hands — it now requires an explicit record
     * of how that was resolved instead.
     */
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->timestamp('refunded_at')->nullable()->after('cash_settlement_id');
            $table->foreignId('refunded_by_user_id')->nullable()->after('refunded_at')->constrained('users')->nullOnDelete();
            $table->string('refund_note')->nullable()->after('refunded_by_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('refunded_by_user_id');
            $table->dropColumn(['refunded_at', 'refund_note']);
        });
    }
};
