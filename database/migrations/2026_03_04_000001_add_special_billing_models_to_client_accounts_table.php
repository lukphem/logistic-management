<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Same JSON-array pattern as disabled_billing_models — which
     * company-enabled billing models this account has explicitly put
     * into Special mode. This is what makes Standard/Special genuinely
     * mutually exclusive, not just a UI toggle: when a billing model
     * is in this list, ShipmentPricingService skips that model's
     * discount entirely at pricing time, regardless of whether a
     * matching special rate actually exists for the specific shipment
     * being priced. Previously a discount could silently stack on top
     * of a special rate whenever both happened to be configured, since
     * they were applied at two independent stages of pricing with no
     * awareness of each other.
     *
     * Null/empty means every enabled model stays Standard, matching
     * the existing "absence means default/unrestricted" convention
     * used throughout this project.
     */
    public function up(): void
    {
        Schema::table('client_accounts', function (Blueprint $table) {
            $table->json('special_billing_models')->nullable()->after('disabled_billing_models');
        });
    }

    public function down(): void
    {
        Schema::table('client_accounts', function (Blueprint $table) {
            $table->dropColumn('special_billing_models');
        });
    }
};
