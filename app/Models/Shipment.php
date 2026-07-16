<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Shipment extends Model
{
    protected $fillable = [
        'tracking_number', 'client_user_id', 'api_client_id', 'service_type', 'rate_card_id',
        'origin_address', 'origin_zone_id', 'origin_city_id', 'destination_address', 'destination_zone_id', 'destination_city_id', 'distance_km',
        'weight_kg', 'length_cm', 'width_cm', 'height_cm', 'chargeable_weight_kg',
        'base_amount', 'surcharge_amount', 'discount_amount', 'vat_amount', 'insurance_amount', 'total_amount',
        'current_status', 'assigned_rider_id', 'current_hub_id', 'current_outlet_id', 'origin_hub_id',
        'sla_breached', 'promised_delivery_at', 'delivered_at',
    ];

    protected $casts = [
        'sla_breached' => 'boolean',
        'promised_delivery_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    /**
     * The waybill/tracking number is coded with the ORIGINATING hub's
     * operational code — e.g. a shipment picked up by hub "LOS-01" gets a
     * tracking number starting "LOS01...". This is fixed at booking and
     * never changes even after the shipment moves to other hubs
     * (current_hub_id does that job instead).
     *
     * origin_hub_id is resolved in this order:
     *  1. explicitly set on the shipment already (client/staff picked it)
     *  2. a hub whose home city matches the shipment's origin_city_id
     *  3. any hub that operationally covers the origin_city's state
     *     (Hub::states())
     *  4. none found — falls back to a generic "LM" prefix, same as
     *     before this feature existed
     */
    protected static function booted(): void
    {
        static::creating(function (Shipment $shipment) {
            if (! $shipment->origin_hub_id && $shipment->origin_city_id) {
                $shipment->origin_hub_id = static::resolveOriginHub($shipment->origin_city_id)?->id;
            }

            if (! $shipment->tracking_number) {
                $hub = $shipment->origin_hub_id ? Hub::find($shipment->origin_hub_id) : null;
                $hubCode = $hub ? preg_replace('/[^A-Z0-9]/', '', strtoupper($hub->code)) : null;
                $prefix = $hubCode ?: 'LM';

                $shipment->tracking_number = $prefix . now()->format('ymd') . strtoupper(Str::random(6));
            }
        });
    }

    private static function resolveOriginHub(int $originCityId): ?Hub
    {
        if ($hub = Hub::where('city_id', $originCityId)->first()) {
            return $hub;
        }

        $city = City::find($originCityId);

        if (! $city) {
            return null;
        }

        return Hub::whereHas('states', fn ($q) => $q->where('states.id', $city->state_id))->first();
    }

    public function originHub(): BelongsTo
    {
        return $this->belongsTo(Hub::class, 'origin_hub_id');
    }

    public function scanEvents(): HasMany
    {
        return $this->hasMany(ScanEvent::class)->orderBy('scanned_at');
    }

    public function rateCard(): BelongsTo
    {
        return $this->belongsTo(RateCard::class);
    }

    public function originZone(): BelongsTo
    {
        return $this->belongsTo(Zone::class, 'origin_zone_id');
    }

    public function destinationZone(): BelongsTo
    {
        return $this->belongsTo(Zone::class, 'destination_zone_id');
    }

    public function originCity(): BelongsTo
    {
        return $this->belongsTo(City::class, 'origin_city_id');
    }

    public function destinationCity(): BelongsTo
    {
        return $this->belongsTo(City::class, 'destination_city_id');
    }

    public function assignedRider(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'assigned_rider_id');
    }

    public function currentHub(): BelongsTo
    {
        return $this->belongsTo(Hub::class, 'current_hub_id');
    }

    public function currentOutlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class, 'current_outlet_id');
    }
}
