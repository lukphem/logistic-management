<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientSpecialTariffZonePrice extends Model
{
    protected $fillable = ['client_special_tariff_id', 'zone_id', 'charge', 'additional_charge', 'transit_days'];

    public function tariff(): BelongsTo
    {
        return $this->belongsTo(ClientSpecialTariff::class, 'client_special_tariff_id');
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }
}
