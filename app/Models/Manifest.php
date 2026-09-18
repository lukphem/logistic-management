<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One destination batch within a ManifestTrip — this is what actually
 * gets dispatched-from and arrival-scanned-at a hub, independent of
 * whether that hub is the trip's last stop or just one of several.
 * Its own destination is never required to match a shipment's
 * eventual destination_hub_id — a manifest can drop shipments at any
 * intermediate hub for a further manifest onward, same as a real
 * linehaul network.
 */
class Manifest extends Model
{
    protected $fillable = [
        'manifest_number', 'manifest_trip_id',
        'destination_hub_id', 'destination_outlet_id',
        'status', 'estimated_arrival_at',
        'received_by_user_id', 'received_at', 'discrepancy_notes',
    ];

    protected $casts = [
        'estimated_arrival_at' => 'datetime',
        'received_at' => 'datetime',
    ];

    public function trip(): BelongsTo
    {
        return $this->belongsTo(ManifestTrip::class, 'manifest_trip_id');
    }

    public function destinationHub(): BelongsTo
    {
        return $this->belongsTo(Hub::class, 'destination_hub_id');
    }

    public function destinationOutlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class, 'destination_outlet_id');
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by_user_id');
    }

    public function manifestShipments(): HasMany
    {
        return $this->hasMany(ManifestShipment::class);
    }

    public function shipments(): BelongsToMany
    {
        return $this->belongsToMany(Shipment::class, 'manifest_shipments')
            ->withPivot(['condition', 'condition_notes', 'scanned_at'])
            ->withTimestamps();
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isDispatched(): bool
    {
        return $this->status === 'dispatched';
    }

    public function hasDiscrepancy(): bool
    {
        return $this->manifestShipments()->whereIn('condition', ['damaged', 'missing', 'over'])->exists();
    }

    public static function generateManifestNumber(): string
    {
        do {
            $candidate = 'MAN-' . now()->format('ymd') . '-' . strtoupper(\Illuminate\Support\Str::random(5));
        } while (static::where('manifest_number', $candidate)->exists());

        return $candidate;
    }
}
