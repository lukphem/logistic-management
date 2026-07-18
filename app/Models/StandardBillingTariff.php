<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StandardBillingTariff extends Model
{
    protected $fillable = ['service_type_id', 'min_weight', 'max_weight', 'additional_weight', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(ServiceType::class);
    }

    public function zonePrices(): HasMany
    {
        return $this->hasMany(TariffZonePrice::class, 'tariff_id');
    }
}
