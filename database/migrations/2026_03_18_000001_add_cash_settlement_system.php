<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * collection_method/cash_collected_at replace the old, narrower
     * cod_remitted_at flag as the actual source of truth for "was this
     * paid electronically, or is there physical cash sitting somewhere
     * that still needs to reach the company." Deliberately not scoped
     * to COD specifically — a walk-in paying cash at an outlet counter
     * is the exact same situation as a receiver paying a rider cash on
     * delivery: money was physically collected and now needs settling.
     * One mechanism for both, not two.
     *
     * cash_settlement_id links a shipment to the batch it was paid off
     * in — a shipment is "settled" once its batch's own Paystack
     * transaction succeeds, not the moment someone selects it on the
     * reconciliation page (selecting is just building the batch;
     * paying is what actually moves money to the company).
     */
    public function up(): void
    {
        Schema::create('cash_settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('initiated_by_user_id')->constrained('users');
            $table->decimal('total_amount', 12, 2);
            $table->string('payment_reference')->nullable();
            $table->enum('status', ['pending', 'paid', 'failed'])->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        Schema::table('shipments', function (Blueprint $table) {
            $table->enum('collection_method', ['paystack', 'cash'])->nullable()->after('payment_reference');
            $table->timestamp('cash_collected_at')->nullable()->after('collection_method');
            $table->foreignId('cash_settlement_id')->nullable()->after('cash_collected_at')->constrained('cash_settlements')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cash_settlement_id');
            $table->dropColumn(['collection_method', 'cash_collected_at']);
        });

        Schema::dropIfExists('cash_settlements');
    }
};
