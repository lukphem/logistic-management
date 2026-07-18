<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Which billing model (Setting::BILLING_MODELS key) calculates price
     * for this service type — the link the Pricing Engine dispatches on.
     * "Express" might use standard_billing; a future "Bulk Freight"
     * service type might use a truckload model once that's built.
     * Nullable — a service type with no model assigned yet just can't be
     * quoted or booked, which is the correct behavior (see PricingEngine).
     */
    public function up(): void
    {
        Schema::table('service_types', function (Blueprint $table) {
            $table->string('billing_model')->nullable()->after('code');
        });
    }

    public function down(): void
    {
        Schema::table('service_types', function (Blueprint $table) {
            $table->dropColumn('billing_model');
        });
    }
};
