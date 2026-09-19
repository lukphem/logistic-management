<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScanStatus extends Model
{
    protected $fillable = ['key', 'label', 'sort_order', 'is_terminal', 'is_delivery_attempt', 'notify_customer', 'is_first_touch'];

    protected $casts = ['is_terminal' => 'boolean', 'is_delivery_attempt' => 'boolean', 'notify_customer' => 'boolean', 'is_first_touch' => 'boolean'];

    protected static function booted(): void
    {
        static::addGlobalScope('ordered', fn ($query) => $query->orderBy('sort_order'));
    }
}
