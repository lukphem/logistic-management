<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A separate table rather than more columns on users — client-only
     * data (RC number, TIN, ID type) has no business living on the same
     * model as staff fields (staff_id, hub_id, employment_type). One row
     * per client user, created at account-creation time.
     *
     * account_type starts 'individual' and can move to 'organization'
     * later ("upgrade") simply by filling in the organization_* fields
     * and flipping this column — same row, no migration of data needed.
     * It never moves back the other way in the UI (an org that's
     * "downgraded" would lose its RC/TIN trail), though nothing at the
     * DB level prevents it.
     *
     * Individual-only and organization-only fields are both nullable
     * and both always present on the table — which set is required is
     * enforced in the controller/form based on account_type, not by the
     * schema (a nullable column doesn't mean optional-for-everyone,
     * just optional-for-the-other-account-type).
     */
    public function up(): void
    {
        Schema::create('client_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_user_id')->constrained('users')->cascadeOnDelete()->unique();
            $table->enum('account_type', ['individual', 'organization'])->default('individual');

            // Individual (KYC)
            $table->enum('id_type', ['national_id', 'passport', 'drivers_license', 'voters_card'])->nullable();
            $table->string('id_number')->nullable();

            // Organization
            $table->string('company_name')->nullable();
            $table->string('rc_number')->nullable();
            $table->string('tin')->nullable();
            $table->string('industry')->nullable();
            $table->string('contact_person_name')->nullable();
            $table->string('contact_person_role')->nullable();

            // Shared
            $table->text('address')->nullable();
            $table->foreignId('city_id')->nullable()->constrained()->nullOnDelete();
            $table->string('alternate_phone')->nullable();
            $table->text('billing_address')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_profiles');
    }
};
