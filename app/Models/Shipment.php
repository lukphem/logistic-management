<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Shipment extends Model
{
    protected $fillable = [
        'tracking_number', 'client_user_id', 'client_account_id', 'api_client_id', 'is_test', 'service_type_id', 'shipping_type',
        'sender_name', 'sender_phone', 'sender_email', 'receiver_name', 'receiver_phone', 'receiver_alternate_phone', 'receiver_email',
        'package_description', 'special_instructions',
        'origin_address', 'origin_zone_id', 'origin_city_id', 'origin_district_id', 'destination_address', 'destination_zone_id', 'destination_city_id', 'destination_district_id', 'distance_km',
        'weight_kg', 'length_cm', 'width_cm', 'height_cm', 'chargeable_weight_kg', 'quantity', 'carton_size',
        'is_cod', 'cod_amount', 'cod_remitted_at', 'is_pickup_requested', 'pickup_amount',
        'payment_status', 'payment_reference', 'paid_at',
        'base_amount', 'surcharge_amount', 'onforwarding_amount', 'discount_amount', 'vat_amount', 'insurance_amount', 'total_amount',
        'current_status', 'assigned_rider_id', 'current_hub_id', 'current_outlet_id', 'origin_hub_id', 'destination_hub_id',
        'sla_breached', 'promised_delivery_at', 'delivered_at',
    ];

    protected $casts = [
        'sla_breached' => 'boolean',
        'is_test' => 'boolean',
        'is_cod' => 'boolean',
        'is_pickup_requested' => 'boolean',
        'cod_remitted_at' => 'datetime',
        'paid_at' => 'datetime',
        'promised_delivery_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    /**
     * The waybill/tracking number is coded with BOTH the originating and
     * destination hub's operational codes — e.g. a shipment from hub
     * "LOS" to hub "PHC" gets a tracking number starting "LOS-PHC-...".
     * Both are fixed at booking and never change even after the shipment
     * physically moves (current_hub_id/current_outlet_id do that job).
     *
     * Each hub is resolved independently, in this order:
     *  1. explicitly set on the shipment already (client/staff picked it)
     *  2. a hub whose home city matches the corresponding origin/
     *     destination city
     *  3. any hub that operationally covers that city's state
     *     (Hub::states())
     *  4. none found for either side — falls back to the old generic
     *     "LM" prefix
     */
    protected static function booted(): void
    {
        static::creating(function (Shipment $shipment) {
            if (! $shipment->origin_hub_id && $shipment->origin_city_id) {
                $shipment->origin_hub_id = static::resolveHubForCity($shipment->origin_city_id)?->id;
            }

            if (! $shipment->destination_hub_id && $shipment->destination_city_id) {
                $shipment->destination_hub_id = static::resolveHubForCity($shipment->destination_city_id)?->id;
            }

            if (! $shipment->tracking_number) {
                $originHub = $shipment->origin_hub_id ? Hub::find($shipment->origin_hub_id) : null;
                $destinationHub = $shipment->destination_hub_id ? Hub::find($shipment->destination_hub_id) : null;
                $serviceType = $shipment->service_type_id ? ServiceType::find($shipment->service_type_id) : null;

                $shipment->tracking_number = static::composeTrackingNumber($originHub, $destinationHub, $serviceType);
            }
        });
    }

    private static function composeTrackingNumber(?Hub $originHub, ?Hub $destinationHub, ?ServiceType $serviceType = null): string
    {
        $format = Setting::current()->tracking_number_format;

        if ($format) {
            return static::renderTrackingNumberFormat($format, $originHub, $destinationHub, $serviceType);
        }

        // No custom format configured — original, hardcoded behavior,
        // unchanged, so an existing deployment's numbering never shifts
        // just from the format column existing.
        $originCode = $originHub ? preg_replace('/[^A-Z0-9]/', '', strtoupper($originHub->code)) : null;
        $destinationCode = $destinationHub ? preg_replace('/[^A-Z0-9]/', '', strtoupper($destinationHub->code)) : null;

        $prefix = match (true) {
            $originCode && $destinationCode && $originCode !== $destinationCode => "{$originCode}-{$destinationCode}-",
            $originCode => "{$originCode}-",
            default => 'LM',
        };

        return $prefix . now()->format('ymd') . strtoupper(Str::random(6));
    }

    /**
     * Parses {token} / {token:param} placeholders left to right — see
     * Setting::TRACKING_NUMBER_TOKENS for the full list and what each
     * one renders. A hub/service-type token with nothing to resolve
     * renders as an empty string rather than failing the whole
     * shipment just for an unusual route; leftover doubled/leading/
     * trailing separators from that are cleaned up afterward so a
     * missing token doesn't leave "--" or a dangling "-" in the result.
     */
    private static function renderTrackingNumberFormat(string $format, ?Hub $originHub, ?Hub $destinationHub, ?ServiceType $serviceType): string
    {
        $originCode = $originHub ? preg_replace('/[^A-Z0-9]/', '', strtoupper($originHub->code)) : '';
        $destinationCode = $destinationHub ? preg_replace('/[^A-Z0-9]/', '', strtoupper($destinationHub->code)) : '';
        $serviceCode = $serviceType ? preg_replace('/[^A-Z0-9]/', '', strtoupper($serviceType->code)) : '';

        $result = preg_replace_callback('/\{([a-z_]+)(?::([^}]+))?\}/i', function ($m) use ($originCode, $destinationCode, $serviceCode) {
            $token = strtolower($m[1]);
            $param = $m[2] ?? null;

            return match ($token) {
                'service_code' => $serviceCode,
                'origin_hub' => $originCode,
                'destination_hub' => $destinationCode,
                'date' => now()->format($param ?: 'ymd'),
                'seq' => str_pad((string) Setting::current()->claimNextTrackingSequence(), (int) ($param ?: 5), '0', STR_PAD_LEFT),
                'random' => strtoupper(Str::random((int) ($param ?: 6))),
                default => $m[0],
            };
        }, $format);

        // Collapse any run of separators (from an unresolved token
        // leaving nothing behind) and trim a leading/trailing one.
        $result = preg_replace('/([-_])\1+/', '$1', $result);

        return trim($result, '-_');
    }

    /**
     * Resolution order: (1) the city's explicit operational_hub_id
     * override — the only way to disambiguate when a state is covered by
     * more than one hub; (2) a hub whose home city matches; (3) any hub
     * covering the city's state, which is ambiguous if more than one
     * exists and picks whichever comes first — set operational_hub_id on
     * the city to make that deterministic instead.
     */
    private static function resolveHubForCity(int $cityId): ?Hub
    {
        $city = City::find($cityId);

        if ($city?->operational_hub_id) {
            return $city->operationalHub;
        }

        if ($hub = Hub::where('city_id', $cityId)->first()) {
            return $hub;
        }

        if (! $city) {
            return null;
        }

        return Hub::whereHas('states', fn ($q) => $q->where('states.id', $city->state_id))->first();
    }

    public function originHub(): BelongsTo
    {
        return $this->belongsTo(Hub::class, 'origin_hub_id');
    }

    public function destinationHub(): BelongsTo
    {
        return $this->belongsTo(Hub::class, 'destination_hub_id');
    }

    public function scanEvents(): HasMany
    {
        return $this->hasMany(ScanEvent::class)->orderBy('scanned_at');
    }

    public function originZone(): BelongsTo
    {
        return $this->belongsTo(Zone::class, 'origin_zone_id');
    }

    public function destinationZone(): BelongsTo
    {
        return $this->belongsTo(Zone::class, 'destination_zone_id');
    }

    public function originCity(): BelongsTo
    {
        return $this->belongsTo(City::class, 'origin_city_id');
    }

    public function destinationCity(): BelongsTo
    {
        return $this->belongsTo(City::class, 'destination_city_id');
    }

    public function originDistrict(): BelongsTo
    {
        return $this->belongsTo(District::class, 'origin_district_id');
    }

    public function destinationDistrict(): BelongsTo
    {
        return $this->belongsTo(District::class, 'destination_district_id');
    }

    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(ServiceType::class);
    }

    public function clientUser(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'client_user_id');
    }

    public function clientAccount(): BelongsTo
    {
        return $this->belongsTo(\App\Models\ClientAccount::class);
    }

    public function apiClient(): BelongsTo
    {
        return $this->belongsTo(\App\Models\ApiClient::class);
    }

    public function assignedRider(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'assigned_rider_id');
    }

    public function currentHub(): BelongsTo
    {
        return $this->belongsTo(Hub::class, 'current_hub_id');
    }

    public function currentOutlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class, 'current_outlet_id');
    }
}
