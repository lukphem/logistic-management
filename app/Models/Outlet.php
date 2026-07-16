<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Outlet extends Model
{
    protected $fillable = ['hub_id', 'name', 'code', 'address', 'latitude', 'longitude', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function hub(): BelongsTo
    {
        return $this->belongsTo(Hub::class);
    }
}
