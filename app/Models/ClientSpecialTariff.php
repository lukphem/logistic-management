<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClientSpecialTariff extends Model
{
    protected $fillable = [
        'client_user_id', 'service_type_id',
        'min_weight', 'max_weight', 'max_weight_limit', 'additional_weight',
        'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function clientUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_user_id');
    }

    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(ServiceType::class);
    }

    public function zonePrices(): HasMany
    {
        return $this->hasMany(ClientSpecialTariffZonePrice::class);
    }
}
