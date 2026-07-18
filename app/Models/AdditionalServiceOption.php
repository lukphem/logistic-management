<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdditionalServiceOption extends Model
{
    protected $fillable = ['additional_service_id', 'name', 'price', 'is_active'];

    protected $casts = [
        'price' => 'float',
        'is_active' => 'boolean',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(AdditionalService::class, 'additional_service_id');
    }
}
