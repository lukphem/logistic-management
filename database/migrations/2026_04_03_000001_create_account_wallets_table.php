<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Named account_wallets (not wallets) and the model AccountWallet
     * (not Wallet) deliberately — a ClientWallet model already exists,
     * scoped to an individual client portal login or API client, used
     * only for a read-only balance API and never touching the actual
     * booking/payment flow. This is a different concept entirely:
     * a wallet belonging to a ClientAccount (the billing entity
     * shipments are actually booked against) or to an Outlet, meant
     * to actually be debited/credited as part of booking a shipment.
     * Polymorphic owner (owner_type/owner_id) rather than two nullable
     * FK columns, since a wallet belongs to exactly one of several
     * possible owner types and that's exactly what morphTo models.
     */
    public function up(): void
    {
        Schema::create('account_wallets', function (Blueprint $table) {
            $table->id();
            $table->morphs('owner');
            $table->decimal('balance', 14, 2)->default(0);
            $table->string('currency', 3)->default('NGN');
            $table->timestamps();

            $table->unique(['owner_type', 'owner_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_wallets');
    }
};
