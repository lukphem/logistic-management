<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends Model
{
    protected $fillable = ['name', 'code', 'continent', 'country_region_id'];

    /**
     * Fixed, universally-agreed classification — not staff-managed data,
     * so this is a plain constant list, same treatment as Zone::TYPES.
     */
    public const CONTINENTS = ['Africa', 'Asia', 'Europe', 'North America', 'South America', 'Oceania', 'Antarctica'];

    public function states(): HasMany
    {
        return $this->hasMany(State::class);
    }

    public function countryRegion(): BelongsTo
    {
        return $this->belongsTo(CountryRegion::class);
    }
}
