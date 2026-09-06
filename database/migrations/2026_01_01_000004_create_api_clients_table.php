<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_clients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('api_key')->unique();
            $table->string('api_secret_hash');
            $table->boolean('is_active')->default(true);
            $table->boolean('ip_whitelist_enabled')->default(false);
            $table->unsignedInteger('rate_limit_per_minute')->default(60);
            $table->timestamp('last_used_at')->nullable();
            $table->string('last_used_ip')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_clients');
    }
};
