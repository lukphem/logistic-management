<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IpWhitelist extends Model
{
    use HasFactory;

    protected $fillable = ['api_client_id', 'ip_or_cidr', 'label', 'added_at'];

    protected $casts = [
        'added_at' => 'datetime',
    ];

    public function apiClient(): BelongsTo
    {
        return $this->belongsTo(ApiClient::class);
    }

    /**
     * Supports exact IPv4 match or CIDR range (e.g. 197.210.5.0/24).
     */
    public function matches(string $ip): bool
    {
        if (! str_contains($this->ip_or_cidr, '/')) {
            return $this->ip_or_cidr === $ip;
        }

        [$subnet, $bits] = explode('/', $this->ip_or_cidr);
        $bits = (int) $bits;

        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);

        if ($ipLong === false || $subnetLong === false) {
            return false;
        }

        $mask = -1 << (32 - $bits);
        $subnetLong &= $mask;

        return ($ipLong & $mask) === $subnetLong;
    }
}
