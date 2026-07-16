<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Unit extends Model
{
    protected $fillable = ['hub_id', 'name', 'code'];

    public function hub(): BelongsTo
    {
        return $this->belongsTo(Hub::class);
    }
}
