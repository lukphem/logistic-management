<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AccountNumberSequence extends Model
{
    protected $fillable = ['state_id', 'outlet_id', 'staff_user_id', 'next_sequence'];

    /**
     * Same lockForUpdate()-inside-a-transaction pattern as
     * Setting::claimNextTrackingSequence() - claims and advances the
     * counter for this exact (state, outlet, staff) combination
     * atomically, so two simultaneous account creations for the same
     * combination can never end up with the same sequence number.
     */
    /**
     * Same lockForUpdate()-inside-a-transaction pattern as
     * Setting::claimNextTrackingSequence() - claims and advances the
     * counter for this exact (state, outlet, staff) combination
     * atomically, so two simultaneous account creations for the same
     * combination can never end up with the same sequence number.
     *
     * The counter row itself is created lazily, on first use for a
     * given combination — firstOrCreate isn't safe against two
     * simultaneous FIRST claims for a brand-new combination both
     * trying to insert the row (they'd collide on the unique
     * constraint), so that specific case retries once rather than
     * bubbling up a database error.
     */
    public static function claimNext(?int $stateId, ?int $outletId, int $staffUserId, bool $isRetry = false): int
    {
        try {
            return DB::transaction(function () use ($stateId, $outletId, $staffUserId) {
                $row = static::where('state_id', $stateId)
                    ->where('outlet_id', $outletId)
                    ->where('staff_user_id', $staffUserId)
                    ->lockForUpdate()
                    ->first();

                if (! $row) {
                    $row = static::create([
                        'state_id' => $stateId,
                        'outlet_id' => $outletId,
                        'staff_user_id' => $staffUserId,
                        'next_sequence' => 1,
                    ]);
                }

                $current = $row->next_sequence;
                $row->update(['next_sequence' => $current + 1]);

                return $current;
            });
        } catch (\Illuminate\Database\QueryException $e) {
            if (! $isRetry && str_contains($e->getMessage(), 'ans_state_outlet_staff_unique')) {
                return static::claimNext($stateId, $outletId, $staffUserId, isRetry: true);
            }

            throw $e;
        }
    }
}
