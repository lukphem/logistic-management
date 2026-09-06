<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Extends the default users table to distinguish the three platform user
     * categories. Role/permission granularity itself is handled by
     * spatie/laravel-permission (roles + permissions tables published
     * separately via: php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider").
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('user_type', ['staff', 'rider', 'client'])->default('staff')->after('email');
            $table->boolean('is_active')->default(true)->after('user_type');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['user_type', 'is_active']);
        });
    }
};
