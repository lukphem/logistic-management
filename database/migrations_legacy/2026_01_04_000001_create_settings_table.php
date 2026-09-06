<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Single-row table (id is always 1) — this is a single-tenant
     * deployment, so there's exactly one company's settings, not a
     * per-tenant table. Populated by the setup wizard; config/branding.php
     * still holds the fallback defaults for a fresh install before the
     * wizard has been run.
     */
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('company_name')->nullable();
            $table->string('logo_path')->nullable();
            $table->json('service_names')->nullable(); // {"express": "Express", "same_day": "Same-Day", ...}
            $table->string('color_primary')->nullable();
            $table->string('color_secondary')->nullable();
            $table->decimal('vat_percentage', 5, 2)->nullable();
            $table->string('currency', 3)->nullable();
            $table->string('waybill_thermal_size')->nullable(); // e.g. '4x6', '2x1'
            $table->boolean('waybill_show_qr')->default(true);
            $table->json('operating_regions')->nullable(); // ["Lagos", "Abuja", ...]
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
