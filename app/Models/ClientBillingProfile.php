<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\Request;

class ClientBillingProfile extends Model
{
    protected $fillable = ['client_user_id', 'api_client_id', 'billing_type', 'discount_percentage', 'notes'];

    protected $casts = [
        'discount_percentage' => 'float',
    ];

    public function clientUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_user_id');
    }

    public function apiClient(): BelongsTo
    {
        return $this->belongsTo(ApiClient::class);
    }

    /**
     * Every client is 'standard' by default — a profile only needs to
     * exist once someone is put on 'special'. Returns null (not a default
     * model) when nothing has been configured, so callers can treat
     * "no profile" and "explicitly standard" identically: no discount.
     */
    public static function resolveForRequest(Request $request): ?self
    {
        if ($request->user()) {
            return static::where('client_user_id', $request->user()->id)->first();
        }

        if ($apiClient = $request->attributes->get('api_client')) {
            return static::where('api_client_id', $apiClient->id)->first();
        }

        return null;
    }

    public function discountFraction(): float
    {
        if ($this->billing_type !== 'special') {
            return 0.0;
        }

        return $this->discount_percentage / 100;
    }
}
