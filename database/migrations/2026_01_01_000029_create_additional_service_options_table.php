<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('additional_service_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('additional_service_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->enum('charge_type', ['flat', 'percentage'])->default('flat');
            $table->foreignId('reverse_service_type_id')->nullable()->constrained('service_types')->nullOnDelete();
            $table->decimal('reverse_weight_kg', 10, 2)->nullable();
            $table->decimal('price', 12, 2);
            $table->boolean('is_vatable')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('additional_service_options');
    }
};
