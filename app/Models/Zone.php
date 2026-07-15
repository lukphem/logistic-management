<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Zone extends Model
{
    protected $fillable = ['name', 'code', 'hub_id', 'geofence'];

    protected $casts = ['geofence' => 'array'];

    public function hub(): BelongsTo
    {
        return $this->belongsTo(Hub::class);
    }
}
