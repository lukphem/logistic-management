<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScanStatus extends Model
{
    protected $fillable = ['key', 'label', 'sort_order', 'is_terminal'];

    protected $casts = ['is_terminal' => 'boolean'];

    protected static function booted(): void
    {
        static::addGlobalScope('ordered', fn ($query) => $query->orderBy('sort_order'));
    }
}
