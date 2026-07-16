<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ApiClient extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'api_key',
        'api_secret_hash',
        'is_active',
        'ip_whitelist_enabled',
        'rate_limit_per_minute',
        'last_used_at',
        'last_used_ip',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'ip_whitelist_enabled' => 'boolean',
        'last_used_at' => 'datetime',
    ];

    public function ipWhitelists(): HasMany
    {
        return $this->hasMany(IpWhitelist::class);
    }

    public function billingProfile(): HasOne
    {
        return $this->hasOne(ClientBillingProfile::class);
    }

    /**
     * True if the given IP matches an allowed entry (exact or CIDR),
     * or if whitelisting is disabled for this client.
     */
    public function isIpAllowed(string $ip): bool
    {
        if (! $this->ip_whitelist_enabled) {
            return true;
        }

        return $this->ipWhitelists->contains(
            fn (IpWhitelist $entry) => $entry->matches($ip)
        );
    }
}
