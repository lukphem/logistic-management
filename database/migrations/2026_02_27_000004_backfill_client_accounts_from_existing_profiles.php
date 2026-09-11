<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Phase 1's actual data move. For every existing client_profiles
     * row (every client that exists today), creates exactly one
     * client_accounts row — "Default Account" — copying across every
     * field that moved. Nothing is deleted here; client_profiles keeps
     * its old columns intact for now (dropped only in the final
     * cleanup migration, once everything reading from them has been
     * confirmed moved over).
     *
     * account_number here is the client's OLD account_number (already
     * unique per client, generated at account creation) - reused
     * as-is so existing account numbers customers may already know
     * don't change. is_default = true marks this as the one account
     * that existed before multi-account support, so any code not yet
     * updated to think in terms of accounts still has an unambiguous
     * one to fall back to.
     *
     * Sub-users (client_profiles.parent_client_user_id IS NOT NULL)
     * get their client_account_id pointed at their parent's new
     * Default Account, preserving the sub-user -> organization
     * relationship exactly, just expressed through the new column.
     *
     * Departments, discounts, special tariffs, subscriptions, and
     * shipments all get backfilled the same way: client_account_id =
     * that client's Default Account.
     */
    public function up(): void
    {
        $profiles = DB::table('client_profiles')->whereNull('parent_client_user_id')->get();

        foreach ($profiles as $profile) {
            $accountId = DB::table('client_accounts')->insertGetId([
                'client_user_id' => $profile->client_user_id,
                'account_name' => 'Default Account',
                'account_number' => $profile->account_number ?? ('ACC' . str_pad((string) $profile->client_user_id, 10, '0', STR_PAD_LEFT)),
                'is_default' => true,
                'account_type' => $profile->account_type ?? 'individual',
                'id_type' => $profile->id_type,
                'id_number' => $profile->id_number,
                'company_name' => $profile->company_name,
                'rc_number' => $profile->rc_number,
                'tin' => $profile->tin,
                'industry' => $profile->industry,
                'contact_person_name' => $profile->contact_person_name,
                'contact_person_role' => $profile->contact_person_role,
                'address' => $profile->address,
                'city_id' => $profile->city_id,
                'country_id' => $profile->country_id ?? null,
                'state_id' => $profile->state_id ?? null,
                'territory_id' => $profile->territory_id ?? null,
                'express_center' => $profile->express_center ?? null,
                'business_objective' => $profile->business_objective ?? null,
                'alternate_phone' => $profile->alternate_phone,
                'billing_address' => $profile->billing_address,
                'warehouse_access' => $profile->warehouse_access ?? false,
                'cod_enabled' => $profile->cod_enabled ?? false,
                'insurance_agreement' => $profile->insurance_agreement ?? false,
                'insurance_agreement_date' => $profile->insurance_agreement_date ?? null,
                'insurance_agreement_notes' => $profile->insurance_agreement_notes ?? null,
                'invoice_due_days' => $profile->invoice_due_days ?? null,
                'sla_pickup_hours' => $profile->sla_pickup_hours ?? null,
                'sla_delivery_days' => $profile->sla_delivery_days ?? null,
                'business_manager_id' => $profile->business_manager_id ?? null,
                'created_by' => $profile->created_by ?? null,
                'created_at' => $profile->created_at,
                'updated_at' => now(),
            ]);

            DB::table('client_profiles')->where('id', $profile->id)->update(['client_account_id' => $accountId]);

            DB::table('departments')->where('client_user_id', $profile->client_user_id)->update(['client_account_id' => $accountId]);
            DB::table('client_service_discounts')->where('client_user_id', $profile->client_user_id)->update(['client_account_id' => $accountId]);
            DB::table('client_special_tariffs')->where('client_user_id', $profile->client_user_id)->update(['client_account_id' => $accountId]);
            DB::table('client_service_subscriptions')->where('client_user_id', $profile->client_user_id)->update(['client_account_id' => $accountId]);
            DB::table('shipments')->where('client_user_id', $profile->client_user_id)->update(['client_account_id' => $accountId]);

            // Sub-users of this client point at the same Default Account.
            DB::table('client_profiles')
                ->where('parent_client_user_id', $profile->client_user_id)
                ->update(['client_account_id' => $accountId]);
        }
    }

    public function down(): void
    {
        // Data-only migration - nothing to structurally reverse here.
        // The client_accounts rows this created can be removed by
        // truncating that table if this migration is ever rolled back.
    }
};
