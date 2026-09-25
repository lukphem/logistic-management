<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A transfer produces two ledger entries — a debit on the source
     * wallet, a credit on the destination — but neither of those is
     * "the transfer" on its own; this is. Both ledger entries carry
     * this row's generated reference, so browsing either wallet's
     * history and following the reference finds the other side.
     * Kept as its own table (not just two transaction rows) so
     * transfers can be listed and audited as a first-class thing,
     * the same way fundings already are.
     */
    public function up(): void
    {
        Schema::create('account_wallet_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_account_wallet_id')->constrained('account_wallets')->cascadeOnDelete();
            $table->foreignId('to_account_wallet_id')->constrained('account_wallets')->cascadeOnDelete();
            $table->decimal('amount', 14, 2);
            $table->string('reference')->unique();
            $table->string('note')->nullable();
            $table->foreignId('initiated_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_wallet_transfers');
    }
};
