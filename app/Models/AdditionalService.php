<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdditionalService extends Model
{
    protected $fillable = ['name', 'price', 'is_active'];

    protected $casts = [
        'price' => 'float',
        'is_active' => 'boolean',
    ];
}
