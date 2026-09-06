<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A saved Rate Checker result, given a shareable ID so it can be
     * handed to whoever books the shipment later — walk-in customer on
     * the phone, sales rep, etc. — without re-entering the route, weight,
     * dimensions, and additional services all over again.
     *
     * context/result are a frozen snapshot of exactly what PricingEngine
     * and ShipmentPricingService returned at generation time (the same
     * arrays RateCheckerController already builds) — booking against a
     * quote reuses that snapshot rather than recalculating, so the price
     * a customer was quoted is the price they pay, even if a tariff
     * changes before they come back to book. That's the entire point of
     * a quote; a "quote" that silently re-prices at booking time isn't
     * one.
     *
     * expires_at is set at generation time from
     * Setting::current()->quote_validity_days, not computed on the fly —
     * so a later change to that setting never retroactively shifts an
     * already-issued quote's expiry.
     */
    public function up(): void
    {
        Schema::create('quotes', function (Blueprint $table) {
            $table->id();
            $table->string('quote_number')->unique();
            $table->foreignId('service_type_id')->nullable()->constrained()->nullOnDelete();
            $table->json('context');
            $table->json('result');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['active', 'used', 'expired'])->default('active');
            $table->timestamp('expires_at');
            $table->foreignId('used_by_shipment_id')->nullable()->constrained('shipments')->nullOnDelete();
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotes');
    }
};
