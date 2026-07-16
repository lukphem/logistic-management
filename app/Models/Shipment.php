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
        'current_status', 'assigned_rider_id', 'current_hub_id', 'current_outlet_id',
        'sla_breached', 'promised_delivery_at', 'delivered_at',
    ];

    protected $casts = [
        'sla_breached' => 'boolean',
        'promised_delivery_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Shipment $shipment) {
            $shipment->tracking_number ??= 'LM' . now()->format('ymd') . strtoupper(Str::random(6));
        });
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
