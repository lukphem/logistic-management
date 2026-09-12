<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Outlet extends Model
{
    protected $fillable = ['hub_id', 'name', 'code', 'short_code', 'address', 'latitude', 'longitude', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    protected static function booted(): void
    {
        static::creating(function (Outlet $outlet) {
            if (! $outlet->short_code) {
                $outlet->short_code = static::generateShortCode($outlet->name);
            }
        });
    }

    /**
     * First 3 letters of the name, uppercased; falls back to random
     * if that collides (a short pool, so collisions are plausible
     * once there are a few outlets with similar names).
     */
    public static function generateShortCode(string $name): string
    {
        $base = strtoupper(preg_replace('/[^A-Za-z]/', '', $name));
        $candidate = str_pad(substr($base, 0, 3), 3, 'X');

        while (static::where('short_code', $candidate)->exists()) {
            $candidate = strtoupper(\Illuminate\Support\Str::random(3));
        }

        return $candidate;
    }

    public function hub(): BelongsTo
    {
        return $this->belongsTo(Hub::class);
    }
}
