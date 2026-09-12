<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Same JSON-array pattern as settings.supported_billing_models —
     * a company-enabled billing model can be turned OFF for one
     * specific account (e.g. this client never uses Fleet at all).
     * Null/empty means every company-enabled model is available,
     * matching the same "absence means unrestricted" default used
     * throughout this project (supported_billing_models,
     * account_number_format, etc.) — a pure addition, no existing
     * account's available billing models change just from this column
     * existing.
     */
    public function up(): void
    {
        Schema::table('client_accounts', function (Blueprint $table) {
            $table->json('disabled_billing_models')->nullable()->after('account_type');
        });
    }

    public function down(): void
    {
        Schema::table('client_accounts', function (Blueprint $table) {
            $table->dropColumn('disabled_billing_models');
        });
    }
};
