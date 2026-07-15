<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScanEvent extends Model
{
    protected $fillable = [
        'shipment_id', 'status', 'handled_by', 'hub_id',
        'latitude', 'longitude', 'photo_path', 'signature_path', 'scanned_at',
    ];

    protected $casts = ['scanned_at' => 'datetime'];

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'handled_by');
    }
}
