<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ThirdPartyCountryMapping extends Model
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

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }

    /**
     * Same normalization as domestic ZoneMapping — a route between two
     * countries is one mapping regardless of direction, always stored
     * with the lower id first.
     */
    protected static function booted(): void
    {
        static::saving(function (ThirdPartyCountryMapping $mapping) {
            if ($mapping->country_a_id > $mapping->country_b_id) {
                [$mapping->country_a_id, $mapping->country_b_id] = [$mapping->country_b_id, $mapping->country_a_id];
            }
        });
    }

    public static function resolveZone(int $countryOneId, int $countryTwoId): ?Zone
    {
        [$a, $b] = $countryOneId <= $countryTwoId ? [$countryOneId, $countryTwoId] : [$countryTwoId, $countryOneId];

        return static::where('country_a_id', $a)->where('country_b_id', $b)->first()?->zone;
    }
}
