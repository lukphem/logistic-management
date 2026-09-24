<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Outlet extends Model
{
    protected $fillable = ['hub_id', 'name', 'code', 'short_code', 'address', 'latitude', 'longitude', 'is_active', 'can_collect_cash', 'can_use_wallet', 'can_collect_online', 'disabled_billing_models', 'disabled_service_type_ids', 'discount_percentage'];

    protected $casts = [
        'is_active' => 'boolean',
        'can_collect_cash' => 'boolean',
        'can_use_wallet' => 'boolean',
        'can_collect_online' => 'boolean',
        'disabled_billing_models' => 'array',
        'disabled_service_type_ids' => 'array',
        'discount_percentage' => 'float',
    ];

    protected static function booted(): void
    {
        static::creating(function (Outlet $outlet) {
            if (! $outlet->short_code) {
                $outlet->short_code = static::generateShortCode($outlet->name);
            }
        });
    }

    /**
     * First 3 letters of the name, uppercased; falls back to random
     * if that collides (a short pool, so collisions are plausible
     * once there are a few outlets with similar names).
     */
    public static function generateShortCode(string $name): string
    {
        $base = strtoupper(preg_replace('/[^A-Za-z]/', '', $name));
        $candidate = str_pad(substr($base, 0, 3), 3, 'X');

        while (static::where('short_code', $candidate)->exists()) {
            $candidate = strtoupper(\Illuminate\Support\Str::random(3));
        }

        return $candidate;
    }

    public function hub(): BelongsTo
    {
        return $this->belongsTo(Hub::class);
    }

    public function wallet(): \Illuminate\Database\Eloquent\Relations\MorphOne
    {
        return $this->morphOne(\App\Models\AccountWallet::class, 'owner');
    }

    /**
     * Same "null/empty = unrestricted" shape as
     * ClientAccount::usesBillingModel() — an outlet that's never had
     * this touched allows every billing model the company itself
     * supports.
     */
    public function usesBillingModel(string $billingModel): bool
    {
        return ! in_array($billingModel, $this->disabled_billing_models ?? [], true);
    }

    public function allowsServiceType(int $serviceTypeId): bool
    {
        return ! in_array($serviceTypeId, $this->disabled_service_type_ids ?? [], true);
    }

    /**
     * Flat, not per-service-type — a single negotiated-in-house
     * discount off the standard tariff for a walk-in shipment booked
     * at this outlet, not a contracted client relationship. See
     * ShipmentPricingService for where this actually gets applied.
     */
    public function discountFraction(): float
    {
        return $this->discount_percentage / 100;
    }
}
