<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ZoneMapping extends Model
{
    protected $fillable = ['state_a_id', 'state_b_id', 'zone_id'];

    public function stateA(): BelongsTo
    {
        return $this->belongsTo(State::class, 'state_a_id');
    }

    public function stateB(): BelongsTo
    {
        return $this->belongsTo(State::class, 'state_b_id');
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }

    /**
     * A route between two states equates to one zone regardless of
     * direction — "Abuja to Lagos" and "Lagos to Abuja" are the same
     * mapping. Always stores/looks up with the lower ID first so one row
     * covers the route both ways, instead of needing two mirrored rows
     * or an OR-based query at lookup time.
     */
    protected static function booted(): void
    {
        static::saving(function (ZoneMapping $mapping) {
            if ($mapping->state_a_id > $mapping->state_b_id) {
                [$mapping->state_a_id, $mapping->state_b_id] = [$mapping->state_b_id, $mapping->state_a_id];
            }
        });
    }

    public static function resolveZone(int $stateOneId, int $stateTwoId): ?Zone
    {
        [$a, $b] = $stateOneId <= $stateTwoId ? [$stateOneId, $stateTwoId] : [$stateTwoId, $stateOneId];

        return static::where('state_a_id', $a)->where('state_b_id', $b)->first()?->zone;
    }
}
