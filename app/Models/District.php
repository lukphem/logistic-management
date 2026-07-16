<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class District extends Model
{
    protected $fillable = ['city_id', 'name', 'short_code', 'code'];

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    /**
     * `code` is auto-composed from the parent city's code + this
     * district's short_code — e.g. "NG-LA-IKJ" + "GRA" -> "NG-LA-IKJ-GRA".
     * Same pattern as State/City.
     */
    protected static function booted(): void
    {
        static::saving(function (District $district) {
            if ($district->short_code) {
                $city = $district->relationLoaded('city') ? $district->city : City::find($district->city_id);
                $district->code = $city && $city->code ? strtoupper($city->code . '-' . $district->short_code) : strtoupper($district->short_code);
            }
        });
    }
}
