<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Four groups of additions, all on the existing client_profiles
     * row rather than new tables - each client already has exactly
     * one of these:
     *
     *  - Org hierarchy: parent_client_user_id + department_id make a
     *    User a sub-user of an organization (a real, separate login -
     *    the parent's own profile has both null, a sub-user's point
     *    back to the organization). department_id is deliberately NOT
     *    required even when parent_client_user_id is set - a sub-user
     *    can exist before departments are set up.
     *  - Overview: account_number (staff-facing identifier, distinct
     *    from the tracking-number-style ids used elsewhere),
     *    created_by, and the location/industry fields the Overview
     *    tab shows that weren't already covered by address/city_id.
     *  - Managerial services: warehouse access and COD as explicit
     *    per-client toggles (previously only shipment-level, never a
     *    client-level default/permission).
     *  - Standard logistics terms: insurance agreement, invoice due
     *    days (payment terms), and SLA commitments (pickup/delivery
     *    windows) - none of this existed anywhere before.
     */
    public function up(): void
    {
        Schema::table('client_profiles', function (Blueprint $table) {
            $table->foreignId('parent_client_user_id')->nullable()->after('client_user_id')->constrained('users')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->after('parent_client_user_id')->constrained()->nullOnDelete();

            $table->string('account_number')->nullable()->unique()->after('id');
            $table->foreignId('created_by')->nullable()->after('account_number')->constrained('users')->nullOnDelete();
            $table->foreignId('country_id')->nullable()->after('city_id')->constrained()->nullOnDelete();
            $table->foreignId('state_id')->nullable()->after('country_id')->constrained()->nullOnDelete();
            $table->foreignId('territory_id')->nullable()->after('state_id')->constrained()->nullOnDelete();
            $table->string('express_center')->nullable()->after('territory_id');
            $table->text('business_objective')->nullable()->after('express_center');

            $table->boolean('warehouse_access')->default(false);
            $table->boolean('cod_enabled')->default(false);

            $table->boolean('insurance_agreement')->default(false);
            $table->date('insurance_agreement_date')->nullable();
            $table->text('insurance_agreement_notes')->nullable();
            $table->unsignedInteger('invoice_due_days')->nullable();
            $table->unsignedInteger('sla_pickup_hours')->nullable();
            $table->unsignedInteger('sla_delivery_days')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('client_profiles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_client_user_id');
            $table->dropConstrainedForeignId('department_id');
            $table->dropConstrainedForeignId('created_by');
            $table->dropConstrainedForeignId('country_id');
            $table->dropConstrainedForeignId('state_id');
            $table->dropConstrainedForeignId('territory_id');
            $table->dropColumn([
                'account_number', 'express_center', 'business_objective',
                'warehouse_access', 'cod_enabled',
                'insurance_agreement', 'insurance_agreement_date', 'insurance_agreement_notes',
                'invoice_due_days', 'sla_pickup_hours', 'sla_delivery_days',
            ]);
        });
    }
};
