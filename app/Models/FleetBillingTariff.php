<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FleetBillingTariff extends Model
{
    protected $fillable = [
        'service_type_id', 'vehicle_type_id',
        'origin_state_id', 'origin_city_id', 'origin_country_id',
        'destination_state_id', 'destination_city_id', 'destination_country_id',
        'min_weight', 'max_weight', 'max_weight_limit', 'base_charge', 'additional_weight', 'additional_charge',
        'fuel_surcharge_percentage',
        'empty_return_charge_type', 'empty_return_charge_value',
        'transit_days', 'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public const EMPTY_RETURN_CHARGE_TYPES = [
        'flat' => 'Flat amount',
        'percentage' => 'Percentage of freight',
    ];

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
     * Defensive against a row missing BOTH country and state (bad
     * data, however it got there) — never crash rendering a label,
     * show what's actually known instead. A row with a genuinely
     * complete route never hits the fallback.
     */
    public function originLabel(): string
    {
        if ($this->origin_country_id) {
            return $this->originCountry?->name ?? 'Unknown country';
        }

        if (! $this->origin_state_id) {
            return 'Not set';
        }

        return $this->originCity ? "{$this->originState?->name} ({$this->originCity->name})" : ($this->originState?->name ?? 'Unknown state');
    }

    public function destinationLabel(): string
    {
        if ($this->destination_country_id) {
            return $this->destinationCountry?->name ?? 'Unknown country';
        }

        if (! $this->destination_state_id) {
            return 'Not set';
        }

        return $this->destinationCity ? "{$this->destinationState?->name} ({$this->destinationCity->name})" : ($this->destinationState?->name ?? 'Unknown state');
    }

    /**
     * The empty-return charge, resolved against a freight amount — flat
     * returns its own value directly, percentage returns that
     * percentage of $freight. Only actually added to a quote when the
     * shipper marks the trip as empty-return at booking/quote time (see
     * PricingEngine::fleetBilling()) — this method just knows how to
     * compute the figure once that choice is made.
     */
    public function resolveEmptyReturnCharge(float $freight): float
    {
        return $this->empty_return_charge_type === 'percentage'
            ? round($freight * ($this->empty_return_charge_value / 100), 2)
            : round($this->empty_return_charge_value, 2);
    }
}
