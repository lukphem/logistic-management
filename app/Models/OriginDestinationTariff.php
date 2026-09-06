<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OriginDestinationTariff extends Model
{
    protected $fillable = [
        'service_type_id',
        'origin_state_id', 'origin_city_id', 'origin_country_id',
        'destination_state_id', 'destination_city_id', 'destination_country_id',
        'min_weight', 'max_weight', 'max_weight_limit',
        'base_charge', 'additional_weight', 'additional_charge',
        'transit_days', 'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(ServiceType::class);
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
     * "Abuja" for a state-wide row, "Lagos (Ikeja)" for a city-specific
     * one, or "United States" for a country-based (international) side
     * — for display wherever the route needs a single readable label
     * rather than several separate columns.
     */
    public function originLabel(): string
    {
        if ($this->origin_country_id) {
            return $this->originCountry->name;
        }

        return $this->originCity ? "{$this->originState->name} ({$this->originCity->name})" : $this->originState->name;
    }

    public function destinationLabel(): string
    {
        if ($this->destination_country_id) {
            return $this->destinationCountry->name;
        }

        return $this->destinationCity ? "{$this->destinationState->name} ({$this->destinationCity->name})" : $this->destinationState->name;
    }
}
