<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A genuinely separate rate, not a discount on the standard one —
     * same shape as standard_billing_tariffs (min_weight/max_weight/
     * max_weight_limit, additional_weight, its own zone price table)
     * but scoped to exactly one client. PricingEngine checks for a
     * matching row here before falling back to the shared
     * standard_billing_tariffs — see standardBilling()'s new client
     * override step.
     *
     * Deliberately does NOT support Origin to Destination or Fleet
     * Billing — those are separate billing models a client either uses
     * or doesn't; a "special rate" here overrides Standard Billing
     * specifically, matching "by default client uses the standard
     * billing" from the request this was built for.
     */
    public function up(): void
    {
        Schema::create('client_special_tariffs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('service_type_id')->constrained()->cascadeOnDelete();
            $table->decimal('min_weight', 10, 2);
            $table->decimal('max_weight', 10, 2);
            $table->decimal('max_weight_limit', 10, 2);
            $table->decimal('additional_weight', 10, 2)->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_special_tariffs');
    }
};
