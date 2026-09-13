<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * status is a real lifecycle state, not just a boolean flag — room
     * for more than "active vs suspended" later without another
     * migration, and self-documenting in queries/exports compared to
     * an ambiguous is_suspended = 0.
     *
     * payment_type is 'cash' (pay upfront, no balance carried) or
     * 'credit' (can transact now, invoiced later, up to credit_limit).
     * credit_limit only means anything when payment_type is 'credit' —
     * left nullable rather than defaulting to 0, since 0 would read as
     * "credit account with no credit" rather than "not a credit
     * account at all".
     */
    public function up(): void
    {
        Schema::table('client_accounts', function (Blueprint $table) {
            $table->enum('status', ['active', 'suspended'])->default('active')->after('is_default');
            $table->text('suspension_reason')->nullable()->after('status');
            $table->enum('payment_type', ['cash', 'credit'])->default('cash')->after('maximum_delivery_attempts');
            $table->decimal('credit_limit', 12, 2)->nullable()->after('payment_type');
        });
    }

    public function down(): void
    {
        Schema::table('client_accounts', function (Blueprint $table) {
            $table->dropColumn(['status', 'suspension_reason', 'payment_type', 'credit_limit']);
        });
    }
};
