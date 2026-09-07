<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Access, not pricing - separate from client_service_discounts on
     * purpose. A row here (is_active = true) means this client can use
     * that service type at all; client_service_discounts only ever
     * matters for a service type this table already allows. A client
     * with no rows here has no restriction (every active service type
     * is available) - this table is for the cases where staff
     * deliberately want to scope a client down to a subset.
     *
     * Explicit short name on the unique index - the default
     * Laravel-generated name for this table+column combination exceeds
     * MySQL's 64-character identifier limit (learned the hard way on
     * client_special_tariff_zone_prices earlier in this project).
     */
    public function up(): void
    {
        Schema::create('client_service_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('service_type_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['client_user_id', 'service_type_id'], 'css_client_service_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_service_subscriptions');
    }
};
