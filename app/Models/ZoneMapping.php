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

    /**
     * The default domestic zone tier for a state pair, before any manual
     * override. Checked in this order:
     *
     *  1 — same state
     *  2 — different states, same territory
     *  3 — different territories, both states have an airport
     *  4 — different territories, at least one state has no airport
     *
     * This is only ever a STARTING POINT — ZoneMappingController::generateDomestic()
     * uses it to pre-fill newly generated pairs, and any individual pair
     * can still be reassigned to a different zone afterward via the
     * normal inline picker on the Zone Mapping screen. Recomputing this
     * never touches a pair that's already been assigned.
     */
    public static function determineDefaultZoneTier(State $stateA, State $stateB): int
    {
        if ($stateA->id === $stateB->id) {
            return 1;
        }

        if ($stateA->territory_id && $stateA->territory_id === $stateB->territory_id) {
            return 2;
        }

        if ($stateA->has_airport && $stateB->has_airport) {
            return 3;
        }

        return 4;
    }
}
