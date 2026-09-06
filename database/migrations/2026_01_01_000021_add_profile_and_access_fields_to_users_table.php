<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('staff_id')->nullable()->unique();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('title')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('photo_path')->nullable();
            $table->enum('user_type', ['staff','rider','client'])->default('staff');
            $table->foreignId('region_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('hub_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('outlet_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->enum('account_status', ['active','suspended','locked','terminated'])->default('active');
            $table->string('status_reason')->nullable();
            $table->timestamp('status_changed_at')->nullable();
            $table->foreignId('status_changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('date_of_birth')->nullable();
            $table->string('gender')->nullable();
            $table->text('address')->nullable();
            $table->string('job_title')->nullable();
            $table->date('date_joined')->nullable();
            $table->enum('employment_type', ['full_time','part_time','contract','intern'])->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();
        });
    }

    public function down(): void
    {
        // Squashed migration - see the reconciled branch history for the granular changes.
    }
};
