<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per configured pricing rule. A client can have several rate
     * cards active at once (e.g. one per service type), each using a
     * different billing_model. model_config holds model-specific parameters
     * so no schema change is needed to add a new pricing variant.
     *
     * model_config examples:
     *  flat            -> {"amount": 1500}
     *  distance         -> {"per_km": 120, "tiers": [{"upto_km":10,"per_km":100}]}
     *  weight           -> {"per_kg": 300, "tiers": [{"upto_kg":5,"per_kg":250}]}
     *  volumetric       -> {"divisor": 5000, "per_kg": 300}
     *  hybrid           -> {"base_fare": 500, "per_km": 80, "per_kg": 150}
     *  service_multiplier -> {"multiplier": 1.5}
     *  time_surcharge   -> {"peak_multiplier": 1.2, "weekend_multiplier": 1.3}
     *  contract         -> {"fixed_amount": 25000, "client_id": 14}
     *  zone_to_zone     -> resolved via zone_rate_matrix table, not model_config
     */
    public function up(): void
    {
        Schema::create('rate_cards', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g. "Express - Lagos Intra-city"
            $table->string('service_type'); // maps to config/branding.php service_names keys
            $table->enum('billing_model', [
                'flat', 'distance', 'zone_to_zone', 'weight', 'volumetric',
                'hybrid', 'service_multiplier', 'time_surcharge', 'contract',
            ]);
            $table->json('model_config')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('priority')->default(0); // resolution order when multiple cards could apply
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rate_cards');
    }
};
