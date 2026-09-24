<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A wallet, unlike a shipment or a settlement, gets funded
     * repeatedly over its lifetime — so it can't carry a single
     * payment_reference of its own the way Shipment/CashSettlement
     * do. This is that tracking entity instead: one row per funding
     * attempt, holding the reference a Paystack transaction is
     * initialized against. For 'bank_transfer', status is set to
     * 'paid' immediately (staff directly attesting a transfer was
     * received, the same trust model already used for refund_note on
     * shipments) and the wallet credited right away. For 'paystack',
     * status starts 'pending' and the wallet is only actually
     * credited once PaymentController confirms it via callback,
     * webhook, or the scheduled requery — exactly the same three-path
     * confirmation pattern already protecting shipment and settlement
     * payments.
     */
    public function up(): void
    {
        Schema::create('account_wallet_fundings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_wallet_id')->constrained()->cascadeOnDelete();
            $table->enum('funding_method', ['paystack', 'bank_transfer']);
            $table->decimal('amount', 14, 2);
            $table->enum('status', ['pending', 'paid', 'failed'])->default('pending');
            $table->string('payment_reference')->nullable()->unique();
            $table->string('bank_reference')->nullable();
            $table->foreignId('initiated_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_wallet_fundings');
    }
};
