<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Same override pattern as vat_percentage: this is the
     * company-wide default. A client account's own
     * maximum_delivery_attempts (added in the previous increment)
     * overrides it when set; left null on the account, this value
     * applies instead — see ClientAccount::
     * effectiveMaximumDeliveryAttempts().
     */
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->unsignedInteger('maximum_delivery_attempts')->nullable()->after('vat_percentage');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn('maximum_delivery_attempts');
        });
    }
};
