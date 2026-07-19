<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceType extends Model
{
    protected $fillable = ['name', 'code', 'billing_model', 'route_type', 'trade_direction', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];
}
