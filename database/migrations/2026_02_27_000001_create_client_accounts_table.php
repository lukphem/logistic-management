<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase 1 of the Client -> Account restructure. A Client (the
     * User login, user_type='client') can now have multiple Accounts
     * — Lagos, Abuja, E-commerce — each with its own type, contact
     * details, products, billing configuration, and Business Manager.
     * Previously all of this lived 1:1 on client_profiles, forcing a
     * second login just to represent a second account.
     *
     * client_user_id is the PARENT client and is deliberately NOT
     * unique here (that's the whole point — one client, many
     * accounts). is_default marks the account auto-created for every
     * existing client during this migration, so nothing that reads
     * "the" account for a client (during the transition period, before
     * every call site is updated) breaks — it has an unambiguous
     * account to fall back to.
     *
     * Every field that used to live on client_profiles and is
     * genuinely about ONE operational/billing arrangement — not the
     * client's identity as a whole — moves here: address/contact
     * (confirmed per-account), individual/organization type and its
     * fields, business objective, managerial services (warehouse/COD/
     * insurance/invoice/SLA), and Business Manager assignment.
     */
    public function up(): void
    {
        Schema::create('client_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('account_name');
            $table->string('account_number')->unique();
            $table->boolean('is_default')->default(false);

            $table->enum('account_type', ['individual', 'organization'])->default('individual');
            $table->enum('id_type', ['national_id', 'passport', 'drivers_license', 'voters_card'])->nullable();
            $table->string('id_number')->nullable();
            $table->string('company_name')->nullable();
            $table->string('rc_number')->nullable();
            $table->string('tin')->nullable();
            $table->string('industry')->nullable();
            $table->string('contact_person_name')->nullable();
            $table->string('contact_person_role')->nullable();

            $table->text('address')->nullable();
            $table->foreignId('city_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('country_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('state_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('territory_id')->nullable()->constrained()->nullOnDelete();
            $table->string('express_center')->nullable();
            $table->text('business_objective')->nullable();
            $table->string('alternate_phone')->nullable();
            $table->text('billing_address')->nullable();

            $table->boolean('warehouse_access')->default(false);
            $table->boolean('cod_enabled')->default(false);
            $table->boolean('insurance_agreement')->default(false);
            $table->date('insurance_agreement_date')->nullable();
            $table->text('insurance_agreement_notes')->nullable();
            $table->unsignedInteger('invoice_due_days')->nullable();
            $table->unsignedInteger('sla_pickup_hours')->nullable();
            $table->unsignedInteger('sla_delivery_days')->nullable();

            $table->foreignId('business_manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_accounts');
    }
};
