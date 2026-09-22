<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row from a bulk upload, persisted so it survives past the
 * request that uploaded it — accumulates across however many times
 * a batch's file gets uploaded, reviewed, and (for a bad row)
 * individually deleted, rather than living only in an ephemeral
 * token that vanished on the next request.
 */
class BulkShipmentBatchRow extends Model
{
    protected $fillable = [
        'bulk_shipment_batch_id', 'source_row_number', 'status',
        'receiver_name', 'row_data', 'display_data', 'errors',
    ];

    protected $casts = [
        'row_data' => 'array',
        'display_data' => 'array',
        'errors' => 'array',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(BulkShipmentBatch::class, 'bulk_shipment_batch_id');
    }
}
