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

            // who booked it — either an internal client-portal user or an external api_client
            $table->foreignId('client_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('api_client_id')->nullable()->constrained()->nullOnDelete();

            $table->string('service_type'); // express / same_day / economy
            $table->foreignId('rate_card_id')->nullable()->constrained()->nullOnDelete();

            // origin/destination
            $table->string('origin_address');
            $table->foreignId('origin_zone_id')->nullable()->constrained('zones')->nullOnDelete();
            $table->string('destination_address');
            $table->foreignId('destination_zone_id')->nullable()->constrained('zones')->nullOnDelete();
            $table->decimal('distance_km', 8, 2)->nullable();

            // package attributes
            $table->decimal('weight_kg', 8, 2)->nullable();
            $table->decimal('length_cm', 8, 2)->nullable();
            $table->decimal('width_cm', 8, 2)->nullable();
            $table->decimal('height_cm', 8, 2)->nullable();
            $table->decimal('chargeable_weight_kg', 8, 2)->nullable();

            // billing breakdown
            $table->decimal('base_amount', 12, 2)->default(0);
            $table->decimal('surcharge_amount', 12, 2)->default(0);
            $table->decimal('vat_amount', 12, 2)->default(0);
            $table->decimal('insurance_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);

            // status/assignment
            $table->string('current_status')->default('booked'); // configurable list, stored as string
            $table->foreignId('assigned_rider_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('current_hub_id')->nullable()->constrained('hubs')->nullOnDelete();

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
