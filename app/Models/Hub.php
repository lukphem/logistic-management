<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Hub extends Model
{
    protected $fillable = ['region_id', 'name', 'code', 'address', 'latitude', 'longitude', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function zones(): HasMany
    {
        return $this->hasMany(Zone::class);
    }

    public function outlets(): HasMany
    {
        return $this->hasMany(Outlet::class);
    }
}
