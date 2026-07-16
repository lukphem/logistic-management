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
        'current_status', 'assigned_rider_id', 'current_hub_id', 'current_outlet_id', 'origin_hub_id', 'destination_hub_id',
        'sla_breached', 'promised_delivery_at', 'delivered_at',
    ];

    protected $casts = [
        'sla_breached' => 'boolean',
        'promised_delivery_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    /**
     * The waybill/tracking number is coded with BOTH the originating and
     * destination hub's operational codes — e.g. a shipment from hub
     * "LOS" to hub "PHC" gets a tracking number starting "LOS-PHC-...".
     * Both are fixed at booking and never change even after the shipment
     * physically moves (current_hub_id/current_outlet_id do that job).
     *
     * Each hub is resolved independently, in this order:
     *  1. explicitly set on the shipment already (client/staff picked it)
     *  2. a hub whose home city matches the corresponding origin/
     *     destination city
     *  3. any hub that operationally covers that city's state
     *     (Hub::states())
     *  4. none found for either side — falls back to the old generic
     *     "LM" prefix
     */
    protected static function booted(): void
    {
        static::creating(function (Shipment $shipment) {
            if (! $shipment->origin_hub_id && $shipment->origin_city_id) {
                $shipment->origin_hub_id = static::resolveHubForCity($shipment->origin_city_id)?->id;
            }

            if (! $shipment->destination_hub_id && $shipment->destination_city_id) {
                $shipment->destination_hub_id = static::resolveHubForCity($shipment->destination_city_id)?->id;
            }

            if (! $shipment->tracking_number) {
                $originHub = $shipment->origin_hub_id ? Hub::find($shipment->origin_hub_id) : null;
                $destinationHub = $shipment->destination_hub_id ? Hub::find($shipment->destination_hub_id) : null;

                $shipment->tracking_number = static::composeTrackingNumber($originHub, $destinationHub);
            }
        });
    }

    private static function composeTrackingNumber(?Hub $originHub, ?Hub $destinationHub): string
    {
        $originCode = $originHub ? preg_replace('/[^A-Z0-9]/', '', strtoupper($originHub->code)) : null;
        $destinationCode = $destinationHub ? preg_replace('/[^A-Z0-9]/', '', strtoupper($destinationHub->code)) : null;

        $prefix = match (true) {
            $originCode && $destinationCode && $originCode !== $destinationCode => "{$originCode}-{$destinationCode}-",
            $originCode => "{$originCode}-",
            default => 'LM',
        };

        return $prefix . now()->format('ymd') . strtoupper(Str::random(6));
    }

    private static function resolveHubForCity(int $cityId): ?Hub
    {
        if ($hub = Hub::where('city_id', $cityId)->first()) {
            return $hub;
        }

        $city = City::find($cityId);

        if (! $city) {
            return null;
        }

        return Hub::whereHas('states', fn ($q) => $q->where('states.id', $city->state_id))->first();
    }

    public function originHub(): BelongsTo
    {
        return $this->belongsTo(Hub::class, 'origin_hub_id');
    }

    public function destinationHub(): BelongsTo
    {
        return $this->belongsTo(Hub::class, 'destination_hub_id');
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
