<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Optional add-on services (packaging, fragile handling, gift
     * wrapping, whatever a business wants to offer) that increase a
     * shipment's price. Deliberately simple for now — a flat price per
     * service, selected or not. Treated like insurance/onforwarding in
     * ShipmentPricingService: not discounted (it's a real extra service,
     * not part of the negotiated freight rate), but subject to VAT.
     */
    public function up(): void
    {
        Schema::create('additional_services', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('price', 12, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('additional_services');
    }
};
