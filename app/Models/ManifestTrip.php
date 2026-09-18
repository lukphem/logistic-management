<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One physical vehicle journey — one vehicle, one driver, one
 * dispatch event. Carries the full audit-trail fields asked for:
 * transport mode, carrier (company or 3PL + name), vehicle, driver.
 * A single trip can contain several Manifests if the vehicle makes
 * multiple drops along its route — see Manifest for the part that's
 * actually destination-specific and gets individually received.
 */
class ManifestTrip extends Model
{
    protected $fillable = [
        'trip_number', 'origin_hub_id', 'origin_outlet_id',
        'transport_mode', 'carrier_type', 'carrier_name',
        'vehicle_type_id', 'vehicle_identifier', 'driver_name', 'driver_phone',
        'dispatched_by_user_id', 'dispatched_at', 'notes',
    ];

    protected $casts = ['dispatched_at' => 'datetime'];

    public function originHub(): BelongsTo
    {
        return $this->belongsTo(Hub::class, 'origin_hub_id');
    }

    public function originOutlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class, 'origin_outlet_id');
    }

    public function vehicleType(): BelongsTo
    {
        return $this->belongsTo(VehicleType::class);
    }

    public function dispatchedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dispatched_by_user_id');
    }

    public function manifests(): HasMany
    {
        return $this->hasMany(Manifest::class);
    }

    public function isDispatched(): bool
    {
        return $this->dispatched_at !== null;
    }

    public static function generateTripNumber(): string
    {
        do {
            $candidate = 'TRIP-' . now()->format('ymd') . '-' . strtoupper(\Illuminate\Support\Str::random(5));
        } while (static::where('trip_number', $candidate)->exists());

        return $candidate;
    }
}
