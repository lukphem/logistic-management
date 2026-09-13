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
        'client_user_id',
        'client_account_id',
        'mode',
        'access_level',
        'api_key',
        'api_secret_hash',
        'api_response_format',
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

    public function clientUser(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'client_user_id');
    }

    public function clientAccount(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(ClientAccount::class);
    }

    public function isTestMode(): bool
    {
        return $this->mode === 'test';
    }

    public function isReadOnly(): bool
    {
        return $this->access_level === 'read_only';
    }

    public function webhookSubscriptions(): HasMany
    {
        return $this->hasMany(WebhookSubscription::class);
    }

    /**
     * Generates a fresh key + secret pair for one specific account and
     * mode — an account can hold up to two of these (one Test, one
     * Live), matched by the (client_account_id, mode) unique
     * constraint, so calling this again for the same account+mode
     * regenerates rather than duplicates. The secret is returned only
     * here, in plaintext, for one-time display to whoever just
     * generated it (Integrations tab) - never stored or shown again
     * after this, only its hash. Regenerating replaces both; any code
     * the client had saved stops working immediately, same as
     * rotating any other API credential.
     *
     * @return array{api_client: self, plaintext_secret: string}
     */
    public static function generateFor(?int $clientUserId, ?int $clientAccountId, string $mode, string $name): array
    {
        $apiKey = 'lm_' . $mode . '_' . \Illuminate\Support\Str::random(28);
        $secret = \Illuminate\Support\Str::random(48);

        $apiClient = static::updateOrCreate(
            ['client_account_id' => $clientAccountId, 'mode' => $mode],
            [
                'client_user_id' => $clientUserId,
                'name' => $name,
                'api_key' => $apiKey,
                'api_secret_hash' => \Illuminate\Support\Facades\Hash::make($secret),
                'is_active' => true,
            ]
        );

        return ['api_client' => $apiClient, 'plaintext_secret' => $secret];
    }

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
