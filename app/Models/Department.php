<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    protected $fillable = ['client_user_id', 'name'];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_user_id');
    }

    public function subUsers(): HasMany
    {
        return $this->hasMany(ClientProfile::class);
    }
}
