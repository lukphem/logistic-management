<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An explicit pivot model rather than a plain attach/sync, since this
 * carries real data — the condition a shipment was found in when this
 * particular manifest was received (pending/received/damaged/missing/
 * over), not just "is it on the manifest or not." One row per
 * (manifest, shipment) pair; a shipment gets a fresh row on every
 * different manifest it ever rides on, so its full multi-leg history
 * stays reconstructable.
 */
class ManifestShipment extends Model
{
    protected $table = 'manifest_shipments';

    protected $fillable = ['manifest_id', 'shipment_id', 'condition', 'condition_notes', 'scanned_at'];

    protected $casts = ['scanned_at' => 'datetime'];

    public function manifest(): BelongsTo
    {
        return $this->belongsTo(Manifest::class);
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }
}
