<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Region extends Model
{
    protected $fillable = ['name', 'code'];

    public function hubs(): HasMany
    {
        return $this->hasMany(Hub::class);
    }
}
