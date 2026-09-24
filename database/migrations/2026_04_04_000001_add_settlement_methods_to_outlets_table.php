<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * can_collect_cash already exists as a per-outlet restriction —
     * these two extend the same idea to the other two settlement
     * methods. Default true for both, same reasoning as
     * can_collect_cash's own default: existing outlets keep full
     * functionality unless someone explicitly restricts them, rather
     * than silently losing a payment method the moment this ships.
     * can_collect_online is deliberately separate from the global
     * paystack_enabled setting on Settings — that one gates whether
     * Paystack is configured for the company at all; this one gates
     * whether a specific outlet is allowed to use it even when it is.
     */
    public function up(): void
    {
        Schema::table('outlets', function (Blueprint $table) {
            $table->boolean('can_use_wallet')->default(true)->after('can_collect_cash');
            $table->boolean('can_collect_online')->default(true)->after('can_use_wallet');
        });
    }

    public function down(): void
    {
        Schema::table('outlets', function (Blueprint $table) {
            $table->dropColumn(['can_use_wallet', 'can_collect_online']);
        });
    }
};
