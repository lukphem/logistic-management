<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClientAccount extends Model
{
    protected $fillable = [
        'client_user_id', 'account_name', 'account_number', 'is_default',
        'account_type', 'id_type', 'id_number',
        'company_name', 'logo_path', 'rc_number', 'tin', 'industry', 'contact_person_name', 'contact_person_role',
        'address', 'city_id', 'city_name', 'outlet_id', 'country_id', 'state_id', 'territory_id', 'business_objective',
        'alternate_phone', 'billing_address',
        'warehouse_access', 'cod_enabled',
        'insurance_agreement', 'insurance_agreement_date', 'insurance_agreement_notes',
        'invoice_due_days', 'sla_pickup_hours', 'sla_delivery_days',
        'business_manager_id', 'created_by',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'warehouse_access' => 'boolean',
        'cod_enabled' => 'boolean',
        'insurance_agreement' => 'boolean',
        'insurance_agreement_date' => 'date',
    ];

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
