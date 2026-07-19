<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ZoneCountryMapping extends Model
{
    protected $fillable = ['country_a_id', 'country_b_id', 'zone_id'];

    public function countryA(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_a_id');
    }

    public function countryB(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_b_id');
    }

    /**
     * Kept as a convenience alias for countryB() — country_a_id is
     * always Nigeria in practice (see the migration that added it), so
     * "the country this mapping is really about" is still country_b in
     * every existing usage (PricingEngine's lookup, exports, etc.).
     */
    public function country(): BelongsTo
    {
        return $this->countryB();
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }
}
