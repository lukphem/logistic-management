<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_access_denials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_client_id')->nullable()->constrained()->nullOnDelete();
            $table->string('attempted_ip');
            $table->string('reason'); // e.g. 'ip_not_whitelisted', 'inactive_key', 'rate_limited'
            $table->string('endpoint')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_access_denials');
    }
};
