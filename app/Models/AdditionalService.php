<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdditionalService extends Model
{
    protected $fillable = ['name', 'kind', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public const KINDS = [
        'custom' => 'Custom',
        'packaging' => 'Packaging',
        'acknowledgement' => 'Acknowledgement',
    ];

    /**
     * Packaging and Acknowledgement are seeded once and protected —
     * every business using this system has both, so the UI never lets
     * either be renamed or deleted the way a custom service can be.
     */
    public function isProtected(): bool
    {
        return $this->kind !== 'custom';
    }

    public function options(): HasMany
    {
        return $this->hasMany(AdditionalServiceOption::class);
    }
}
