<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScanEvent extends Model
{
    protected $fillable = [
        'shipment_id', 'status', 'handled_by', 'hub_id', 'outlet_id', 'destination_hub_id', 'destination_unit_id',
        'latitude', 'longitude', 'photo_path', 'signature_path', 'receiver_name', 'handed_to_user_id', 'scanned_at',
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

    public function handedTo(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'handed_to_user_id');
    }

    public function hub(): BelongsTo
    {
        return $this->belongsTo(Hub::class);
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function destinationHub(): BelongsTo
    {
        return $this->belongsTo(Hub::class, 'destination_hub_id');
    }

    public function destinationUnit(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Unit::class, 'destination_unit_id');
    }
}
