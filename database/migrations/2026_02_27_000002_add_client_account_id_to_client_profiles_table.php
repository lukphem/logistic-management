<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase 1, continued. client_profiles no longer carries account-
     * level data (moved to client_accounts in the previous migration)
     * — it now only marks two things: that a login is a client, and,
     * for sub-users, which specific Account and Department they're
     * scoped to. client_account_id NULL means "this is the primary
     * client, not a sub-user" — a sub-user's parent client is reached
     * via client_account.client_user_id, so no separate
     * parent_client_user_id column is needed once this exists.
     *
     * The old account-level columns are dropped in a LATER migration
     * (2026_02_27_000005), after the data has actually been copied
     * into client_accounts — dropping them here, before the copy,
     * would destroy the only source of truth for that copy.
     */
    public function up(): void
    {
        Schema::table('client_profiles', function (Blueprint $table) {
            $table->foreignId('client_account_id')->nullable()->after('client_user_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('client_profiles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('client_account_id');
        });
    }
};
