<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Step 3 of the wallet system: wallet becomes a real, third
     * collection_method alongside cash and paystack — debited
     * immediately at booking, same as cash. account_wallet_id
     * records which wallet actually paid for this shipment (the
     * client's own, or the booking outlet's), since either is
     * possible per shipment depending on what staff chose.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE shipments MODIFY collection_method ENUM('paystack', 'cash', 'wallet') NULL");

        Schema::table('shipments', function (Blueprint $table) {
            $table->foreignId('account_wallet_id')->nullable()->after('collection_method')->constrained('account_wallets')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('account_wallet_id');
        });

        DB::statement("ALTER TABLE shipments MODIFY collection_method ENUM('paystack', 'cash') NULL");
    }
};
