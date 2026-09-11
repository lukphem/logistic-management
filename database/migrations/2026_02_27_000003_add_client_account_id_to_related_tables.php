<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase 1, continued. Every table that's genuinely about ONE
     * account's operations — not the client as a whole — gains
     * client_account_id. The old client_user_id columns on these
     * tables are deliberately NOT touched here: they stay, unused
     * once the data migration (next file) runs, until the final
     * cleanup phase once everything reading from them has been moved
     * over and confirmed working. That's what makes this reversible
     * mid-way if something looks wrong.
     */
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->foreignId('client_account_id')->nullable()->after('client_user_id')->constrained()->cascadeOnDelete();
        });

        Schema::table('client_service_discounts', function (Blueprint $table) {
            $table->foreignId('client_account_id')->nullable()->after('client_user_id')->constrained()->cascadeOnDelete();
        });

        Schema::table('client_special_tariffs', function (Blueprint $table) {
            $table->foreignId('client_account_id')->nullable()->after('client_user_id')->constrained()->cascadeOnDelete();
        });

        Schema::table('client_service_subscriptions', function (Blueprint $table) {
            $table->foreignId('client_account_id')->nullable()->after('client_user_id')->constrained()->cascadeOnDelete();
        });

        Schema::table('shipments', function (Blueprint $table) {
            $table->foreignId('client_account_id')->nullable()->after('client_user_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('departments', fn (Blueprint $table) => $table->dropConstrainedForeignId('client_account_id'));
        Schema::table('client_service_discounts', fn (Blueprint $table) => $table->dropConstrainedForeignId('client_account_id'));
        Schema::table('client_special_tariffs', fn (Blueprint $table) => $table->dropConstrainedForeignId('client_account_id'));
        Schema::table('client_service_subscriptions', fn (Blueprint $table) => $table->dropConstrainedForeignId('client_account_id'));
        Schema::table('shipments', fn (Blueprint $table) => $table->dropConstrainedForeignId('client_account_id'));
    }
};
