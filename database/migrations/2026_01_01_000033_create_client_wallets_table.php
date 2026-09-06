<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete()->unique();
            $table->foreignId('api_client_id')->nullable()->constrained()->cascadeOnDelete()->unique();
            $table->decimal('balance', 14, 2)->default(0.00);
            $table->string('currency', 3)->default('NGN');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_wallets');
    }
};
