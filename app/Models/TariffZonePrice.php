<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TariffZonePrice extends Model
{
    protected $fillable = ['tariff_id', 'zone_id', 'charge', 'additional_charge', 'transit_days'];

    public function tariff(): BelongsTo
    {
        return $this->belongsTo(StandardBillingTariff::class, 'tariff_id');
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }
}
