<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The staff-side ClientController::upgrade() applies an upgrade
     * immediately — staff already are the approver in that flow.
     * This is the opposite: a client submits their own organization
     * data through the portal, but it stays pending until a staff
     * member with the right permission reviews it — same fields
     * (company_name, rc_number, tin, industry, contact_person_*) so
     * approving one just copies them onto the account, but nothing
     * here touches client_accounts until that approval actually
     * happens.
     */
    public function up(): void
    {
        Schema::create('client_upgrade_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_account_id')->constrained()->cascadeOnDelete();
            $table->string('company_name');
            $table->string('rc_number');
            $table->string('tin')->nullable();
            $table->string('industry')->nullable();
            $table->string('contact_person_name');
            $table->string('contact_person_role')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('rejection_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_upgrade_requests');
    }
};
