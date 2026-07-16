<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class City extends Model
{
    protected $fillable = ['state_id', 'name', 'short_code', 'code', 'postal_code', 'operational_hub_id', 'onforwarding_classification_id'];

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function hubs(): HasMany
    {
        return $this->hasMany(Hub::class);
    }

    public function districts(): HasMany
    {
        return $this->hasMany(District::class);
    }

    /**
     * This city's assigned Zone classification (e.g. "Port Harcourt =
     * Zone 2") — used to resolve from_zone/to_zone for the
     * origin_destination_weight billing model without needing an entry
     * per city PAIR.
     */
    public function zoneMapping(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(ZoneMapping::class);
    }

    public function onforwardingClassification(): BelongsTo
    {
        return $this->belongsTo(OnforwardingClassification::class);
    }

    /**
     * The hub that operationally handles THIS city specifically — an
     * explicit override for when the city's state is covered by more
     * than one hub (Hub::states()) and the automatic "any covering hub"
     * fallback in Shipment::resolveHubForCity() would otherwise be
     * ambiguous. Optional; leave unset when there's no ambiguity to
     * resolve.
     */
    public function operationalHub(): BelongsTo
    {
        return $this->belongsTo(Hub::class, 'operational_hub_id');
    }

    /**
     * `code` is auto-composed from the parent state's code + this city's
     * short_code — e.g. "NG-LA" + "IKJ" -> "NG-LA-IKJ". Same pattern as
     * State — staff only type short_code.
     */
    protected static function booted(): void
    {
        static::saving(function (City $city) {
            if ($city->short_code) {
                $state = $city->relationLoaded('state') ? $city->state : State::find($city->state_id);
                $city->code = $state && $state->code ? strtoupper($state->code . '-' . $city->short_code) : strtoupper($city->short_code);
            }
        });
    }
}
