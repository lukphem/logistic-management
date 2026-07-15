<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One-to-many: an api_client can have multiple whitelisted IPs/CIDR ranges.
     */
    public function up(): void
    {
        Schema::create('ip_whitelists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_client_id')->constrained()->cascadeOnDelete();
            $table->string('ip_or_cidr'); // e.g. 197.210.5.10 or 197.210.5.0/24
            $table->string('label')->nullable(); // e.g. "Client production server"
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
