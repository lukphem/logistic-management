<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Hub extends Model
{
    protected $fillable = ['region_id', 'city_id', 'name', 'code', 'address', 'latitude', 'longitude', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    /**
     * The states this hub actually picks up from and delivers to —
     * usually broader than just its home city's state (city() above).
     * Many-to-many: a state can be covered by more than one hub too.
     */
    public function states(): BelongsToMany
    {
        return $this->belongsToMany(State::class, 'hub_state');
    }

    public function zones(): HasMany
    {
        return $this->hasMany(Zone::class);
    }

    public function outlets(): HasMany
    {
        return $this->hasMany(Outlet::class);
    }

    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }
}
