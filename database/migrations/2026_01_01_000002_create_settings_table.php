<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('company_name')->nullable();
            $table->string('logo_path')->nullable();
            $table->text('invoice_header')->nullable();
            $table->text('invoice_footer')->nullable();
            $table->string('color_primary')->nullable();
            $table->string('color_secondary')->nullable();
            $table->string('login_design')->default('route');
            $table->json('supported_billing_models')->nullable();
            $table->decimal('vat_percentage', 5, 2)->nullable();
            $table->unsignedInteger('volumetric_divisor')->default(5000);
            $table->string('currency', 3)->nullable();
            $table->string('waybill_thermal_size')->nullable();
            $table->boolean('waybill_show_qr')->default(true);
            $table->json('operating_regions')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
