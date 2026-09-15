<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A batch of cash-collected shipments (walk-in cash at an outlet, or
 * COD cash a rider collected on delivery — same mechanism for both)
 * being settled to the company in one Paystack transaction. The batch
 * itself, not each shipment individually, is what Paystack charges —
 * one reference, one checkout, one webhook confirmation, which then
 * cascades "paid" to every shipment inside it.
 */
class CashSettlement extends Model
{
    protected $fillable = ['initiated_by_user_id', 'total_amount', 'payment_reference', 'status', 'paid_at'];

    protected $casts = [
        'total_amount' => 'float',
        'paid_at' => 'datetime',
    ];

    public function initiatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by_user_id');
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }
}
