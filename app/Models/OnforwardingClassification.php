<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OnforwardingClassification extends Model
{
    protected $fillable = ['name', 'surcharge_amount', 'is_default'];

    protected $casts = [
        'surcharge_amount' => 'float',
        'is_default' => 'boolean',
    ];

    public function cities(): HasMany
    {
        return $this->hasMany(City::class);
    }

    public function districts(): HasMany
    {
        return $this->hasMany(District::class);
    }
}
