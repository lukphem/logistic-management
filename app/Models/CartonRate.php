<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartonRate extends Model
{
    protected $fillable = ['rate_card_id', 'zone_id', 'carton_size', 'price_per_carton'];

    public const SIZES = ['small', 'medium', 'large'];

    public function rateCard(): BelongsTo
    {
        return $this->belongsTo(RateCard::class);
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }
}
