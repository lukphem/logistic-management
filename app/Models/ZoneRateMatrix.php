<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ZoneRateMatrix extends Model
{
    protected $table = 'zone_rate_matrix';

    protected $fillable = ['rate_card_id', 'origin_zone_id', 'destination_zone_id', 'price'];

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
}
