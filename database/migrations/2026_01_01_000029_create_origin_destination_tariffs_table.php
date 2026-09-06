<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('origin_destination_tariffs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_type_id')->constrained()->cascadeOnDelete();
            $table->foreignId('origin_state_id')->nullable()->constrained('states')->cascadeOnDelete();
            $table->foreignId('origin_city_id')->nullable()->constrained('cities')->cascadeOnDelete();
            $table->foreignId('origin_country_id')->nullable()->constrained('countries')->cascadeOnDelete();
            $table->foreignId('destination_state_id')->nullable()->constrained('states')->cascadeOnDelete();
            $table->foreignId('destination_city_id')->nullable()->constrained('cities')->cascadeOnDelete();
            $table->foreignId('destination_country_id')->nullable()->constrained('countries')->cascadeOnDelete();
            $table->decimal('min_weight', 10, 2);
            $table->decimal('max_weight', 10, 2);
            $table->decimal('max_weight_limit', 10, 2);
            $table->decimal('base_charge', 12, 2);
            $table->decimal('additional_weight', 10, 2)->default(1.00);
            $table->decimal('additional_charge', 12, 2)->default(0.00);
            $table->unsignedInteger('transit_days')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('origin_destination_tariffs');
    }
};
