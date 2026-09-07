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

    /**
     * For contexts where the requester isn't the client themselves —
     * staff booking a walk-in shipment on a known client's behalf, where
     * $request->user() is the STAFF member, not the client.
     * resolveForRequest() would resolve the wrong person's profile here;
     * this looks up the client directly by the ID staff provided.
     */
    public static function resolveForClientUser(?int $clientUserId): ?self
    {
        if (! $clientUserId) {
            return null;
        }

        return static::where('client_user_id', $clientUserId)->first();
    }

    public function discountFraction(): float
    {
        if ($this->billing_type !== 'special') {
            return 0.0;
        }

        return $this->discount_percentage / 100;
    }

    /**
     * A per-service-type discount (client_service_discounts) takes
     * priority over the flat discount above when one exists for this
     * exact service type — "discount on each service type agreed and
     * subscribed for", not a blanket discount just because one service
     * type has one. Falls back to the flat discount for any service
     * type without its own row, so an existing 'special' client with
     * only the flat discount configured keeps working unchanged.
     */
    public function discountFractionForServiceType(int $serviceTypeId): float
    {
        $specific = ClientServiceDiscount::where('client_user_id', $this->client_user_id)
            ->where('service_type_id', $serviceTypeId)
            ->first();

        if ($specific) {
            return $specific->discount_percentage / 100;
        }

        return $this->discountFraction();
    }
}
