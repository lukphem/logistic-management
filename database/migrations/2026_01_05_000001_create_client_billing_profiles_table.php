<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per client (portal user OR external api_client — never
     * both). billing_type = 'standard' means the client simply pays
     * whatever the active rate card says, full stop. 'special' stores a
     * discount_percentage taken off the standard rate at quote time — it
     * is never a frozen price. If staff raise the standard rate tomorrow,
     * every special client's price moves with it automatically, because
     * the discount is always applied to whatever the standard rate
     * resolves to right then, not to a number saved when the agreement
     * was made.
     */
    public function up(): void
    {
        Schema::create('client_billing_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_user_id')->nullable()->unique()->constrained('users')->cascadeOnDelete();
            $table->foreignId('api_client_id')->nullable()->unique()->constrained()->cascadeOnDelete();
            $table->enum('billing_type', ['standard', 'special'])->default('standard');
            $table->decimal('discount_percentage', 5, 2)->default(0); // only meaningful when billing_type = 'special'
            $table->string('notes')->nullable(); // e.g. "Agreed by Ops Manager, contract dated..."
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_billing_profiles');
    }
};
