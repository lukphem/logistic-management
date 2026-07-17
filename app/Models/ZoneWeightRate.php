<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ZoneWeightRate extends Model
{
    protected $fillable = [
        'rate_card_id', 'zone_id',
        'min_weight', 'max_weight', 'service_type', 'price',
        'transit_days', 'extra_amount_per_extra_kg',
    ];

    public function rateCard(): BelongsTo
    {
        return $this->belongsTo(RateCard::class);
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }
}
