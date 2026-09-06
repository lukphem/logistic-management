<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VehicleType extends Model
{
    protected $fillable = [
        'name', 'code',
        'max_weight_capacity', 'max_length_cm', 'max_width_cm', 'max_height_cm', 'is_open_body',
        'is_active',
    ];

    protected $casts = [
        'is_open_body' => 'boolean',
        'is_active' => 'boolean',
    ];
}
