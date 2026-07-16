<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class City extends Model
{
    protected $fillable = ['state_id', 'name', 'short_code', 'code'];

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function hubs(): HasMany
    {
        return $this->hasMany(Hub::class);
    }

    public function districts(): HasMany
    {
        return $this->hasMany(District::class);
    }

    /**
     * `code` is auto-composed from the parent state's code + this city's
     * short_code — e.g. "NG-LA" + "IKJ" -> "NG-LA-IKJ". Same pattern as
     * State — staff only type short_code.
     */
    protected static function booted(): void
    {
        static::saving(function (City $city) {
            if ($city->short_code) {
                $state = $city->relationLoaded('state') ? $city->state : State::find($city->state_id);
                $city->code = $state && $state->code ? strtoupper($state->code . '-' . $city->short_code) : strtoupper($city->short_code);
            }
        });
    }
}
