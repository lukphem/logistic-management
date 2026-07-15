<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RateCard extends Model
{
    protected $fillable = ['name', 'service_type', 'billing_model', 'model_config', 'is_active', 'priority'];

    protected $casts = [
        'model_config' => 'array',
        'is_active' => 'boolean',
    ];

    public function zoneMatrixEntries(): HasMany
    {
        return $this->hasMany(ZoneRateMatrix::class);
    }
}
