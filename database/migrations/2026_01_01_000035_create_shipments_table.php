<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->string('tracking_number')->unique();
            $table->foreignId('client_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('api_client_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('service_type_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('shipping_type', ['domestic', 'international', 'third_party'])->nullable();
            $table->string('origin_address');
            $table->foreignId('origin_zone_id')->nullable()->constrained('zones')->nullOnDelete();
            $table->foreignId('origin_city_id')->nullable()->constrained('cities')->nullOnDelete();
            $table->foreignId('origin_district_id')->nullable()->constrained('districts')->nullOnDelete();
            $table->string('destination_address');
            $table->foreignId('destination_zone_id')->nullable()->constrained('zones')->nullOnDelete();
            $table->foreignId('destination_city_id')->nullable()->constrained('cities')->nullOnDelete();
            $table->foreignId('destination_district_id')->nullable()->constrained('districts')->nullOnDelete();
            $table->decimal('distance_km', 8, 2)->nullable();
            $table->decimal('weight_kg', 8, 2)->nullable();
            $table->decimal('length_cm', 8, 2)->nullable();
            $table->decimal('width_cm', 8, 2)->nullable();
            $table->decimal('height_cm', 8, 2)->nullable();
            $table->decimal('chargeable_weight_kg', 8, 2)->nullable();
            $table->unsignedInteger('quantity')->nullable();
            $table->enum('carton_size', ['small', 'medium', 'large'])->nullable();
            $table->decimal('base_amount', 12, 2)->default(0);
            $table->decimal('surcharge_amount', 12, 2)->default(0);
            $table->decimal('onforwarding_amount', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('vat_amount', 12, 2)->default(0);
            $table->decimal('insurance_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->boolean('is_cod')->default(false);
            $table->decimal('cod_amount', 12, 2)->default(0);
            $table->timestamp('cod_remitted_at')->nullable();
            $table->string('current_status')->default('booked');
            $table->foreignId('assigned_rider_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('current_hub_id')->nullable()->constrained('hubs')->nullOnDelete();
            $table->foreignId('current_outlet_id')->nullable()->constrained('outlets')->nullOnDelete();
            $table->foreignId('origin_hub_id')->nullable()->constrained('hubs')->nullOnDelete();
            $table->foreignId('destination_hub_id')->nullable()->constrained('hubs')->nullOnDelete();
            $table->boolean('sla_breached')->default(false);
            $table->timestamp('promised_delivery_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
