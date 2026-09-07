<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Additive alongside client_billing_profiles' existing flat
     * discount_percentage, not a replacement — a row here for a given
     * service type takes priority over the flat discount for that
     * service type specifically; a service type with no row here falls
     * back to the flat discount (0 for a 'standard' client, same as
     * always). "Agreed and subscribed for" per service type, per the
     * request this was built for — a client isn't discounted on
     * everything just because they're discounted on one service.
     */
    public function up(): void
    {
        Schema::create('client_service_discounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('service_type_id')->constrained()->cascadeOnDelete();
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->timestamps();

            $table->unique(['client_user_id', 'service_type_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_service_discounts');
    }
};
