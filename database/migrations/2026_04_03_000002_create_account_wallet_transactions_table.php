<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * funding_method distinguishes how a credit actually got into the
     * wallet — 'paystack' (verified online payment, same reference-
     * prefix-dispatch pattern PaystackService already uses for
     * shipments and cash settlements) or 'bank_transfer' (a direct
     * value update: staff records that a bank transfer was received
     * and credits the wallet to match, the same "staff attests,
     * recorded for audit" pattern already used for refund_note on
     * shipments). Null for anything that isn't a funding event at all
     * — a debit for a shipment payment, or an admin transfer between
     * wallets.
     */
    public function up(): void
    {
        Schema::create('account_wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_wallet_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['credit', 'debit']);
            $table->decimal('amount', 14, 2);
            $table->decimal('balance_after', 14, 2);
            $table->enum('funding_method', ['paystack', 'bank_transfer'])->nullable();
            $table->string('reference')->nullable()->index();
            $table->string('description')->nullable();
            $table->foreignId('recorded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_wallet_transactions');
    }
};
