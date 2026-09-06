<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_billing_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_user_id')->nullable()->unique()->constrained('users')->cascadeOnDelete();
            $table->foreignId('api_client_id')->nullable()->unique()->constrained()->cascadeOnDelete();
            $table->enum('billing_type', ['standard', 'special'])->default('standard');
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->string('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_billing_profiles');
    }
};
