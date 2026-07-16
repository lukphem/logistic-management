<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Append-only — rows are never updated or deleted. The users table
     * only holds the CURRENT status; this is the history of how it got
     * there, which is the actual audit record for security/compliance
     * purposes (termination, suspension, reinstatement, etc.).
     */
    public function up(): void
    {
        Schema::create('user_status_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('from_status');
            $table->string('to_status');
            $table->string('reason')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_status_audits');
    }
};
