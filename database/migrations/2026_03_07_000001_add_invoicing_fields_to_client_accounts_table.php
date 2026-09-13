<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Every field here is per-account, not shared across a client's
     * whole portfolio — two accounts under the same client can be
     * taxed differently, use different tax IDs, or have different
     * pickup arrangements, same as they can already have different
     * billing rates.
     *
     * use_default_contact is deliberately a live link, not a one-time
     * copy: when true, this account's contact_person_name/address/
     * billing_address (already existing columns) are ignored in favor
     * of the client's default account's current values, resolved at
     * read time. A one-time copy would silently go stale the moment
     * the default account's info changed; a link never can.
     *
     * vat_percentage is nullable on purpose — null means "use the
     * company-wide rate from Settings", only a non-null value here
     * overrides it for this specific account. is_vatable gates
     * whether VAT applies at all, independent of what rate would
     * apply if it did.
     */
    public function up(): void
    {
        Schema::table('client_accounts', function (Blueprint $table) {
            $table->boolean('use_default_contact')->default(false)->after('billing_address');
            $table->boolean('is_vatable')->default(true)->after('use_default_contact');
            $table->decimal('vat_percentage', 5, 2)->nullable()->after('is_vatable');
            $table->boolean('is_pickup_chargeable')->default(false)->after('vat_percentage');
            $table->decimal('pickup_charge', 10, 2)->nullable()->after('is_pickup_chargeable');
            $table->boolean('is_onforwarding_chargeable')->default(false)->after('pickup_charge');
            $table->decimal('onforwarding_charge', 10, 2)->nullable()->after('is_onforwarding_chargeable');
            $table->unsignedInteger('maximum_delivery_attempts')->nullable()->after('onforwarding_charge');
        });
    }

    public function down(): void
    {
        Schema::table('client_accounts', function (Blueprint $table) {
            $table->dropColumn([
                'use_default_contact', 'is_vatable', 'vat_percentage',
                'is_pickup_chargeable', 'pickup_charge',
                'is_onforwarding_chargeable', 'onforwarding_charge',
                'maximum_delivery_attempts',
            ]);
        });
    }
};
