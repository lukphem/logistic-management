<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The batch-level context for a bulk shipment upload — client
 * account (or walk-in, when null), billing model, service type,
 * sender details, and origin — set once in Step 1 and reused across
 * however many times the CSV itself gets uploaded and re-uploaded in
 * Step 2. Splitting these into two real steps (rather than one
 * combined submission) means a CSV full of errors can be fixed and
 * re-uploaded to this same batch without re-entering the shipper
 * details from scratch.
 */
class BulkShipmentBatch extends Model
{
    protected $fillable = [
        'batch_number', 'client_account_id', 'client_user_id', 'billing_model', 'service_type_id',
        'sender_name', 'sender_phone', 'sender_address', 'sender_email',
        'origin_hub_id', 'origin_outlet_id', 'created_by_user_id',
    ];

    public function clientAccount(): BelongsTo
    {
        return $this->belongsTo(ClientAccount::class);
    }

    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(ServiceType::class);
    }

    public function originHub(): BelongsTo
    {
        return $this->belongsTo(Hub::class, 'origin_hub_id');
    }

    public function originOutlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class, 'origin_outlet_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function shipments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Shipment::class);
    }

    public function rows(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(BulkShipmentBatchRow::class);
    }

    /**
     * Once a batch has any real, created shipments, uploading is
     * over for it — only printing is offered from here on. Checking
     * shipments directly (rather than a separate status flag) means
     * this can never drift out of sync with what actually happened.
     */
    public function hasCreatedShipments(): bool
    {
        return $this->shipments()->exists();
    }

    public function isWalkIn(): bool
    {
        return $this->client_account_id === null;
    }

    public static function generateBatchNumber(): string
    {
        do {
            $candidate = 'BULK-' . now()->format('ymd') . '-' . strtoupper(\Illuminate\Support\Str::random(5));
        } while (static::where('batch_number', $candidate)->exists());

        return $candidate;
    }
}
