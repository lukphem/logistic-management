<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClientAccount extends Model
{
    protected $fillable = [
        'client_user_id', 'account_name', 'account_number', 'is_default', 'status', 'suspension_reason',
        'account_type', 'disabled_billing_models', 'special_billing_models', 'special_fallback_models', 'id_type', 'id_number',
        'company_name', 'logo_path', 'rc_number', 'tin', 'industry', 'contact_person_name', 'contact_person_role',
        'address', 'city_id', 'city_name', 'outlet_id', 'country_id', 'state_id', 'territory_id', 'business_objective',
        'alternate_phone', 'billing_address', 'use_default_contact',
        'warehouse_access', 'warehouse_charge', 'cod_enabled', 'cod_percentage',
        'staff_management_enabled', 'staff_management_charge',
        'insurance_agreement', 'insurance_agreement_date', 'insurance_agreement_notes',
        'invoice_due_days', 'sla_pickup_hours', 'sla_delivery_days',
        'is_vatable', 'vat_percentage', 'is_pickup_chargeable', 'pickup_charge',
        'is_onforwarding_chargeable', 'onforwarding_charge', 'maximum_delivery_attempts',
        'payment_type', 'credit_limit',
        'business_manager_id', 'created_by',
    ];

    protected $casts = [
        'disabled_billing_models' => 'array',
        'special_billing_models' => 'array',
        'special_fallback_models' => 'array',
        'is_default' => 'boolean',
        'warehouse_access' => 'boolean',
        'warehouse_charge' => 'float',
        'cod_percentage' => 'float',
        'staff_management_enabled' => 'boolean',
        'staff_management_charge' => 'float',
        'cod_enabled' => 'boolean',
        'insurance_agreement' => 'boolean',
        'insurance_agreement_date' => 'date',
        'use_default_contact' => 'boolean',
        'is_vatable' => 'boolean',
        'vat_percentage' => 'float',
        'is_pickup_chargeable' => 'boolean',
        'pickup_charge' => 'float',
        'is_onforwarding_chargeable' => 'boolean',
        'onforwarding_charge' => 'float',
        'credit_limit' => 'float',
    ];

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    public function isCreditAccount(): bool
    {
        return $this->payment_type === 'credit';
    }

    public const ID_TYPES = [
        'national_id' => 'National ID',
        'passport' => 'Passport',
        'drivers_license' => "Driver's License",
        'voters_card' => "Voter's Card",
    ];

    /**
     * {StateCode:2}{OutletCode:3}{StaffCode:3}{Sequence:5} — e.g.
     * LALOSJ0700001. State and Outlet are optional on the account
     * form, so a missing one falls back to 'XX'/'XXX' rather than
     * blocking account creation on location being filled in — the
     * number can still be told apart from others via the staff code
     * and sequence even without a full location.
     *
     * $creator's own staff_short_code is generated lazily here if
     * they don't have one yet (covers staff created before this
     * scheme existed) rather than requiring a separate backfill.
     */
    /**
     * Default when no custom format is configured — same shape this
     * was originally built with, kept as the fallback so an existing
     * deployment's numbering never shifts just from the format setting
     * existing.
     */
    public const DEFAULT_ACCOUNT_NUMBER_FORMAT = '{state}{outlet}{staff}{seq:5}';

    /**
     * Reads Company Settings' account_number_format (see
     * Setting::ACCOUNT_NUMBER_TOKENS) — customizable the same way
     * tracking numbers are, rather than a fixed pattern nobody can
     * adjust. Falls back to DEFAULT_ACCOUNT_NUMBER_FORMAT when no
     * custom format is set.
     */
    public static function generateAccountNumber(?State $state, ?Outlet $outlet, User $creator): string
    {
        $format = \App\Models\Setting::current()->account_number_format ?: self::DEFAULT_ACCOUNT_NUMBER_FORMAT;

        $stateCode = $state?->short_code ? strtoupper($state->short_code) : 'XX';
        $outletCode = $outlet?->short_code ? strtoupper($outlet->short_code) : 'XXX';

        if (! $creator->staff_short_code) {
            $base = strtoupper(preg_replace('/[^A-Za-z]/', '', $creator->name ?? ''));
            $candidate = str_pad(substr($base, 0, 3), 3, 'X');

            while (User::where('staff_short_code', $candidate)->exists()) {
                $candidate = strtoupper(\Illuminate\Support\Str::random(3));
            }

            $creator->update(['staff_short_code' => $candidate]);
        }

        $staffCode = strtoupper($creator->staff_short_code);

        // Only claimed (and therefore only advances the counter) when
        // the format actually asks for {seq:...} — a format without
        // it shouldn't burn through sequence numbers nobody sees.
        $sequence = null;
        if (str_contains($format, '{seq')) {
            // A missing state/outlet still needs the counter scoped to
            // SOMETHING — NULL (rather than a fake id) correctly
            // reuses the same counter row across repeat "no location"
            // accounts from the same staff member, since Laravel's
            // where(col, null) resolves to a real IS NULL check, not a
            // literal match.
            $sequence = \App\Models\AccountNumberSequence::claimNext($state?->id, $outlet?->id, $creator->id);
        }

        $result = preg_replace_callback('/\{([a-z_]+)(?::([^}]+))?\}/i', function ($m) use ($stateCode, $outletCode, $staffCode, $sequence) {
            $token = strtolower($m[1]);
            $param = $m[2] ?? null;

            return match ($token) {
                'state' => $stateCode,
                'outlet' => $outletCode,
                'staff' => $staffCode,
                'seq' => str_pad((string) $sequence, (int) ($param ?: 5), '0', STR_PAD_LEFT),
                'date' => now()->format($param ?: 'ymd'),
                'random' => strtoupper(\Illuminate\Support\Str::random((int) ($param ?: 6))),
                default => $m[0],
            };
        }, $format);

        // Collapse doubled/leading/trailing separators left behind by
        // an empty token (e.g. a blank {outlet}), same cleanup
        // Shipment::renderTrackingNumberFormat() already does.
        $result = preg_replace('/([-_])\1+/', '$1', $result);

        return trim($result, '-_');
    }

    public function isOrganization(): bool
    {
        return $this->account_type === 'organization';
    }

    /**
     * The city's real name when a known city was picked (city_id
     * set), else whatever free text was typed for a city not yet in
     * the system (city_name) - see the migration's note on why both
     * columns exist.
     */
    public function cityDisplayName(): ?string
    {
        return $this->city?->name ?? $this->city_name;
    }

    public function getLogoUrlAttribute(): ?string
    {
        // Same root-relative reasoning as Setting::getLogoUrlAttribute()
        // - resolves correctly regardless of which port the app is
        // being viewed on locally.
        return $this->logo_path ? '/storage/' . ltrim($this->logo_path, '/') : null;
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_user_id');
    }

    public function businessManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'business_manager_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function territory(): BelongsTo
    {
        return $this->belongsTo(Territory::class);
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    /**
     * Sub-user PROFILES scoped to this account - ->clientUser on each
     * result reaches the actual User/login.
     */
    public function subUserProfiles(): HasMany
    {
        return $this->hasMany(ClientProfile::class);
    }

    public function serviceDiscounts(): HasMany
    {
        return $this->hasMany(ClientServiceDiscount::class);
    }

    public function specialTariffs(): HasMany
    {
        return $this->hasMany(ClientSpecialTariff::class);
    }

    public function originDestinationTariffs(): HasMany
    {
        return $this->hasMany(ClientOriginDestinationTariff::class);
    }

    public function fleetBillingTariffs(): HasMany
    {
        return $this->hasMany(ClientFleetBillingTariff::class);
    }

    /**
     * Whether a given billing model (Setting::BILLING_MODELS key) is
     * available for THIS account — "might turn off some for the
     * special" from the request this was built for: a company-enabled
     * model can still be switched off for one specific account (e.g.
     * this client never uses Fleet at all). Absence in
     * disabled_billing_models means available, matching the same
     * "null/empty = unrestricted" default used everywhere else in this
     * project (supported_billing_models, account_number_format).
     */
    public function usesBillingModel(string $billingModel): bool
    {
        return ! in_array($billingModel, $this->disabled_billing_models ?? [], true);
    }

    /**
     * Whether this account has explicitly put a billing model into
     * Special mode. This is what makes Standard/Special genuinely
     * exclusive — when true, ShipmentPricingService skips this
     * model's discount entirely at pricing time, regardless of
     * whether a matching special rate actually exists for the
     * specific shipment being priced. A partially-configured Special
     * mode (switched on, but no rate covers this exact scenario) means
     * the plain company rate applies, undiscounted — never a silent
     * discount stacking on top of what Special mode was meant to
     * replace.
     */
    public function isSpecialFor(string $billingModel): bool
    {
        return in_array($billingModel, $this->special_billing_models ?? [], true);
    }

    /**
     * For a billing model already in Special mode, whether a shipment
     * with no matching special rate should fall back to the Standard
     * rate (with its discount) instead of being blocked outright.
     * Absence means false — Special mode blocks by default unless this
     * is explicitly turned on for that model, never the other way
     * around.
     */
    public function allowsFallbackToStandard(string $billingModel): bool
    {
        return in_array($billingModel, $this->special_fallback_models ?? [], true);
    }

    /**
     * Resolved at read time, never copied — when use_default_contact
     * is on, this account's own contact_person_name/address/
     * billing_address columns are ignored in favor of the client's
     * default account's current values, so the link never goes stale
     * the way a one-time copy would the moment the default account's
     * info changes. Meaningless (and never checked) on the default
     * account itself — it has nothing else to defer to.
     */
    public function resolvedContactPersonName(): ?string
    {
        return $this->use_default_contact
            ? ($this->client?->defaultAccount?->contact_person_name ?? $this->contact_person_name)
            : $this->contact_person_name;
    }

    public function resolvedAddress(): ?string
    {
        return $this->use_default_contact
            ? ($this->client?->defaultAccount?->address ?? $this->address)
            : $this->address;
    }

    public function resolvedBillingAddress(): ?string
    {
        return $this->use_default_contact
            ? ($this->client?->defaultAccount?->billing_address ?? $this->billing_address)
            : $this->billing_address;
    }

    /**
     * is_vatable gates whether VAT applies at all; vat_percentage
     * only overrides the rate when it does. A vatable account with no
     * override uses the company-wide rate from Settings, same as
     * every other account — only a non-null value here means "this
     * account specifically is taxed differently."
     */
    public function effectiveVatPercentage(): float
    {
        if (! $this->is_vatable) {
            return 0.0;
        }

        return $this->vat_percentage ?? (\App\Models\Setting::current()->vat_percentage ?? 0.0);
    }

    /**
     * Same override shape as effectiveVatPercentage() — null on the
     * account means "use the company-wide default from Settings", a
     * set value here overrides it for this account specifically. Null
     * either way means no limit is enforced, not zero.
     */
    public function effectiveMaximumDeliveryAttempts(): ?int
    {
        return $this->maximum_delivery_attempts ?? \App\Models\Setting::current()->maximum_delivery_attempts;
    }

    public function serviceSubscriptions(): HasMany
    {
        return $this->hasMany(ClientServiceSubscription::class);
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }

    /**
     * Per-service-type discount, same priority rule as before the
     * Client -> Account restructure: a row here for a given service
     * type takes priority; anything without one falls back to the
     * client's flat ClientBillingProfile discount.
     */
    public function discountFractionForServiceType(int $serviceTypeId): float
    {
        $specific = $this->serviceDiscounts()->where('service_type_id', $serviceTypeId)->first();

        if ($specific) {
            return $specific->discount_percentage / 100;
        }

        $billingProfile = $this->client?->billingProfile;

        return $billingProfile?->discountFraction() ?? 0.0;
    }
}
