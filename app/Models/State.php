<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class State extends Model
{
    protected $fillable = ['country_id', 'name', 'short_code', 'code', 'postal_code'];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function cities(): HasMany
    {
        return $this->hasMany(City::class);
    }

    /**
     * Hubs that pick up from / deliver to this state — see
     * Hub::states() for the fuller explanation.
     */
    public function hubs(): BelongsToMany
    {
        return $this->belongsToMany(Hub::class, 'hub_state');
    }

    /**
     * `code` (the client-API-facing identifier) is always auto-composed
     * from the parent country's code + this state's short_code — e.g.
     * "NG" + "LA" -> "NG-LA". Staff only ever type short_code; `code` is
     * recomputed on every save so it never drifts if the country or
     * short_code changes later.
     */
    protected static function booted(): void
    {
        static::saving(function (State $state) {
            if ($state->short_code) {
                $country = $state->relationLoaded('country') ? $state->country : Country::find($state->country_id);
                $state->code = $country ? strtoupper($country->code . '-' . $state->short_code) : strtoupper($state->short_code);
            }
        });
    }
}
