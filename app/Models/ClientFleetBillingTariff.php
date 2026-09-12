<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientFleetBillingTariff extends Model
{
    protected $fillable = [
        'client_user_id', 'client_account_id', 'service_type_id', 'vehicle_type_id',
        'origin_state_id', 'origin_city_id', 'origin_country_id',
        'destination_state_id', 'destination_city_id', 'destination_country_id',
        'min_weight', 'max_weight', 'max_weight_limit', 'base_charge', 'additional_weight', 'additional_charge',
        'fuel_surcharge_percentage',
        'empty_return_charge_type', 'empty_return_charge_value',
        'transit_days', 'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function clientUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_user_id');
    }

    public function clientAccount(): BelongsTo
    {
        return $this->belongsTo(ClientAccount::class);
    }

    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(ServiceType::class);
    }

    public function vehicleType(): BelongsTo
    {
        return $this->belongsTo(VehicleType::class);
    }

    public function originState(): BelongsTo
    {
        return $this->belongsTo(State::class, 'origin_state_id');
    }

    public function originCity(): BelongsTo
    {
        return $this->belongsTo(City::class, 'origin_city_id');
    }

    public function originCountry(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'origin_country_id');
    }

    public function destinationState(): BelongsTo
    {
        return $this->belongsTo(State::class, 'destination_state_id');
    }

    public function destinationCity(): BelongsTo
    {
        return $this->belongsTo(City::class, 'destination_city_id');
    }

    public function destinationCountry(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'destination_country_id');
    }

    /**
     * Same computation as FleetBillingTariff::resolveEmptyReturnCharge()
     * — flat returns its own value, percentage returns that percentage
     * of $freight.
     */
    public function resolveEmptyReturnCharge(float $freight): float
    {
        return $this->empty_return_charge_type === 'percentage'
            ? round($freight * ($this->empty_return_charge_value / 100), 2)
            : round($this->empty_return_charge_value, 2);
    }
}
