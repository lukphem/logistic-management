<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdditionalServiceOption extends Model
{
    protected $fillable = ['additional_service_id', 'name', 'charge_type', 'reverse_service_type_id', 'reverse_weight_kg', 'price', 'is_active'];

    protected $casts = [
        'price' => 'float',
        'reverse_weight_kg' => 'float',
        'is_active' => 'boolean',
    ];

    public const CHARGE_TYPES = [
        'flat' => 'Flat amount',
        'percentage' => 'Percentage of freight',
        'percentage_of_reverse_shipment' => 'Percentage of a reverse shipment',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(AdditionalService::class, 'additional_service_id');
    }

    public function reverseServiceType(): BelongsTo
    {
        return $this->belongsTo(ServiceType::class, 'reverse_service_type_id');
    }

    /**
     * The actual Naira amount this option adds — $price directly for a
     * flat charge, or $price% of the given base freight amount for a
     * percentage charge. $baseAmount is the shipment's base_amount, the
     * same figure VAT is calculated from — not the running total, since
     * an additional service isn't meant to compound on top of other
     * additional services or discounts.
     *
     * Not used for 'percentage_of_reverse_shipment' — see
     * resolveReverseShipmentAmount() below, which needs the pricing
     * engine and the shipment's route, not just a base amount.
     */
    public function resolveAmount(float $baseAmount): float
    {
        return $this->charge_type === 'percentage'
            ? round($baseAmount * ($this->price / 100), 2)
            : round($this->price, 2);
    }

    /**
     * For "Acknowledgement"-style options: a signed document sent back
     * to origin is its own small shipment, not a cut of the outbound
     * freight. Prices that reverse leg for real — same route as the
     * outbound shipment, but this option's own configured service type
     * and weight — then takes $price% of THAT result.
     *
     * Degrades to 0 rather than throwing if the reverse leg can't be
     * priced (e.g. no tariff configured for that service type/weight
     * yet) — a missing reverse-rate setup shouldn't block pricing the
     * outbound shipment itself, the same graceful-degradation posture
     * used for a missing onforwarding classification.
     */
    public function resolveReverseShipmentAmount(\App\Services\PricingEngine $engine, array $originalContext): float
    {
        if (! $this->reverse_service_type_id || ! $this->reverse_weight_kg) {
            return 0.0;
        }

        $reverseContext = [
            ...$originalContext,
            'service_type_id' => $this->reverse_service_type_id,
            'weight_kg' => $this->reverse_weight_kg,
        ];

        try {
            $reverseQuote = $engine->quote($reverseContext);
        } catch (\App\Services\PricingUnavailableException) {
            return 0.0;
        }

        return round($reverseQuote['base_amount'] * ($this->price / 100), 2);
    }

    /**
     * "₦2,500.00" for a flat option, "2.5% of freight" for a percentage
     * one, "2.5% of [Express] reverse shipment (0.5kg)" for the reverse
     * type — for display wherever the option's price needs to be shown
     * without a specific shipment to resolve it against yet (the
     * options list, the Rate Checker's picker before a quote exists).
     */
    public function displayPrice(): string
    {
        $percentageLabel = rtrim(rtrim(number_format($this->price, 2), '0'), '.') . '%';

        return match ($this->charge_type) {
            'percentage' => "{$percentageLabel} of freight",
            'percentage_of_reverse_shipment' => "{$percentageLabel} of reverse shipment ({$this->reverse_weight_kg}kg)",
            default => number_format($this->price, 2),
        };
    }
}
