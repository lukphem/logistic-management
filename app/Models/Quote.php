<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Quote extends Model
{
    protected $fillable = [
        'quote_number', 'service_type_id', 'context', 'result',
        'created_by', 'status', 'expires_at', 'used_by_shipment_id', 'used_at',
    ];

    protected $casts = [
        'context' => 'array',
        'result' => 'array',
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    /**
     * QT-<6 random uppercase alphanumeric chars>, e.g. QT-7K2XPB — short
     * enough to read over the phone, unlikely enough to collide that a
     * handful of retries is always sufficient rather than needing a
     * sequential counter.
     */
    public static function generateNumber(): string
    {
        do {
            $candidate = 'QT-' . strtoupper(Str::random(6));
        } while (static::where('quote_number', $candidate)->exists());

        return $candidate;
    }

    /**
     * True once past expiry, regardless of what the stored `status`
     * column currently says — status is set lazily (by the prune command
     * or the moment someone tries to use it), so a quote can be
     * chronologically expired for a while before anything actually
     * flips the column. Anything checking whether a quote is usable
     * right now should call this, not read status directly.
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isUsable(): bool
    {
        return $this->status === 'active' && ! $this->isExpired();
    }

    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(ServiceType::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function usedByShipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class, 'used_by_shipment_id');
    }
}
