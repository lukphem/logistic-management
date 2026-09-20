<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * The persisted record behind a printed transfer confirmation
 * (kind: transfer) or delivery sheet (kind: delivery) — created the
 * moment a Departure Scan batch confirms, so the reference number
 * printed on the document can be looked up and the same document
 * regenerated later, the same way a manifest or trip number already
 * can be.
 */
class ScanBatch extends Model
{
    protected $fillable = [
        'reference', 'kind', 'status_key',
        'origin_hub_id', 'origin_outlet_id', 'origin_unit_id',
        'destination_hub_id', 'destination_unit_id',
        'handed_to_user_id', 'created_by_user_id',
    ];

    public function originHub(): BelongsTo
    {
        return $this->belongsTo(Hub::class, 'origin_hub_id');
    }

    public function originOutlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class, 'origin_outlet_id');
    }

    public function originUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'origin_unit_id');
    }

    public function destinationHub(): BelongsTo
    {
        return $this->belongsTo(Hub::class, 'destination_hub_id');
    }

    public function destinationUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'destination_unit_id');
    }

    public function handedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handed_to_user_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function shipments(): BelongsToMany
    {
        return $this->belongsToMany(Shipment::class, 'scan_batch_shipments')->withTimestamps();
    }

    public static function generateReference(string $kind): string
    {
        $prefix = $kind === 'delivery' ? 'DEL' : 'TRF';

        do {
            $candidate = $prefix . '-' . now()->format('ymd') . '-' . strtoupper(\Illuminate\Support\Str::random(5));
        } while (static::where('reference', $candidate)->exists());

        return $candidate;
    }
}
