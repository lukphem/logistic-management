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
