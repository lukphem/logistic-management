<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * refund_wallet_id is deliberately separate from the existing
     * account_wallet_id (which records which wallet *paid* for the
     * shipment) — a cash or Paystack refund can be credited to a
     * wallet that had nothing to do with the original payment (the
     * outlet's own wallet, say, for a shipment paid in cash), so this
     * needs its own column rather than reusing that one.
     */
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->enum('refund_destination', ['bank', 'wallet'])->nullable()->after('refund_note');
            $table->foreignId('refund_wallet_id')->nullable()->after('refund_destination')->constrained('account_wallets')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('refund_wallet_id');
            $table->dropColumn('refund_destination');
        });
    }
};
