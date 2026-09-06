<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ip_whitelists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_client_id')->constrained()->cascadeOnDelete();
            $table->string('ip_or_cidr');
            $table->string('label')->nullable();
            $table->timestamp('added_at')->useCurrent();
            $table->timestamps();
            $table->unique(['api_client_id', 'ip_or_cidr']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ip_whitelists');
    }
};
