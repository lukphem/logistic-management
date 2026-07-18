<?php

namespace App\Services;

use App\Models\RateCard;
use App\Models\Shipment;
use App\Models\ZoneRateMatrix;
use InvalidArgumentException;

class RateEngine
{
    /**
     * Calculate the base amount for a shipment against a given rate card.
     * Surcharges, VAT, and insurance are applied separately (see
     * SurchargeCalculator / VatCalculator) — this returns the base freight
     * charge only.
     *
     * @param  RateCard  $rateCard
     * @param  array  $context  Shipment attributes needed for calculation:
     *   distance_km, weight_kg, chargeable_weight_kg, origin_zone_id,
     *   destination_zone_id, is_peak (bool), is_weekend (bool)
     */
    public function calculateBase(RateCard $rateCard, array $context): float
    {
        return match ($rateCard->billing_model) {
            'flat' => $this->flat($rateCard),
            'distance' => $this->distance($rateCard, $context),
            'zone_to_zone' => $this->zoneToZone($rateCard, $context),
            'weight' => $this->weight($rateCard, $context),
            'volumetric' => $this->volumetric($rateCard, $context),
            'hybrid' => $this->hybrid($rateCard, $context),
            'service_multiplier' => $this->serviceMultiplier($rateCard, $context),
            'time_surcharge' => $this->timeSurcharge($rateCard, $context),
            'contract' => $this->contract($rateCard),
            'origin_destination_weight' => $this->originDestinationWeight($rateCard, $context),
            'truckload' => $this->perUnit($rateCard, $context, 'rate_per_truckload'),
            'carton_rate' => $this->cartonRate($rateCard, $context),
            default => throw new InvalidArgumentException("Unsupported billing model: {$rateCard->billing_model}"),
        };
    }

    private function flat(RateCard $rateCard): float
    {
        return (float) ($rateCard->model_config['amount'] ?? 0);
    }

    private function distance(RateCard $rateCard, array $context): float
    {
        $km = (float) ($context['distance_km'] ?? 0);
        $config = $rateCard->model_config ?? [];

        if (! empty($config['tiers'])) {
            return $this->applyTieredRate($km, $config['tiers'], 'upto_km', 'per_km');
        }

        return $km * (float) ($config['per_km'] ?? 0);
    }

    private function zoneToZone(RateCard $rateCard, array $context): float
    {
        $entry = ZoneRateMatrix::where('rate_card_id', $rateCard->id)
            ->where('origin_zone_id', $context['origin_zone_id'] ?? null)
            ->where('destination_zone_id', $context['destination_zone_id'] ?? null)
            ->first();

        if (! $entry) {
            throw new InvalidArgumentException('No zone-to-zone rate configured for this origin/destination pair.');
        }

        return (float) $entry->price;
    }

    private function weight(RateCard $rateCard, array $context): float
    {
        $kg = (float) ($context['chargeable_weight_kg'] ?? $context['weight_kg'] ?? 0);
        $config = $rateCard->model_config ?? [];

        if (! empty($config['tiers'])) {
            return $this->applyTieredRate($kg, $config['tiers'], 'upto_kg', 'per_kg');
        }

        return $kg * (float) ($config['per_kg'] ?? 0);
    }

    private function volumetric(RateCard $rateCard, array $context): float
    {
        $config = $rateCard->model_config ?? [];
        $divisor = (float) ($config['divisor'] ?? 5000);

        $volumetricWeight = (
            ($context['length_cm'] ?? 0) *
            ($context['width_cm'] ?? 0) *
            ($context['height_cm'] ?? 0)
        ) / $divisor;

        $chargeableWeight = max($volumetricWeight, (float) ($context['weight_kg'] ?? 0));

        return $chargeableWeight * (float) ($config['per_kg'] ?? 0);
    }

    private function hybrid(RateCard $rateCard, array $context): float
    {
        $config = $rateCard->model_config ?? [];

        $base = (float) ($config['base_fare'] ?? 0);
        $distanceCharge = (float) ($context['distance_km'] ?? 0) * (float) ($config['per_km'] ?? 0);
        $weightCharge = (float) ($context['chargeable_weight_kg'] ?? $context['weight_kg'] ?? 0) * (float) ($config['per_kg'] ?? 0);

        return $base + $distanceCharge + $weightCharge;
    }

    private function serviceMultiplier(RateCard $rateCard, array $context): float
    {
        $config = $rateCard->model_config ?? [];
        $underlyingBase = (float) ($context['underlying_base_amount'] ?? 0);
        $multiplier = (float) ($config['multiplier'] ?? 1);

        return $underlyingBase * $multiplier;
    }

    private function timeSurcharge(RateCard $rateCard, array $context): float
    {
        $config = $rateCard->model_config ?? [];
        $underlyingBase = (float) ($context['underlying_base_amount'] ?? 0);

        $multiplier = 1.0;
        if (! empty($context['is_peak'])) {
            $multiplier = (float) ($config['peak_multiplier'] ?? $multiplier);
        } elseif (! empty($context['is_weekend'])) {
            $multiplier = (float) ($config['weekend_multiplier'] ?? $multiplier);
        }

        return $underlyingBase * $multiplier;
    }

    private function contract(RateCard $rateCard): float
    {
        return (float) ($rateCard->model_config['fixed_amount'] ?? 0);
    }

    /**
     * Resolves origin/destination city → state → the ONE zone that
     * route maps to (ZoneMapping, bidirectional), then finds the
     * matching zone_weight_rates row for that (zone, service_type)
     * combination whose weight band contains the shipment's chargeable
     * weight. Weight beyond the matched band's max_weight is charged at
     * that row's extra_amount_per_extra_kg.
     */
    private function originDestinationWeight(RateCard $rateCard, array $context): float
    {
        $zoneId = $this->resolveZoneIdForRoute(
            $context['origin_city_id'] ?? null,
            $context['destination_city_id'] ?? null
        );

        if (! $zoneId) {
            throw new InvalidArgumentException('This origin/destination state pair has no zone mapping yet (see Setups → Billing → Zone Mapping).');
        }

        $weight = (float) ($context['chargeable_weight_kg'] ?? $context['weight_kg'] ?? 0);
        $serviceType = $context['service_type'] ?? $rateCard->service_type;

        $rate = \App\Models\ZoneWeightRate::where('rate_card_id', $rateCard->id)
            ->where('zone_id', $zoneId)
            ->where('service_type', $serviceType)
            ->where('min_weight', '<=', $weight)
            ->where('max_weight', '>=', $weight)
            ->first();

        // Falls back to the highest-weight band for this zone/service if
        // nothing matched (i.e. the shipment is heavier than every
        // defined band) — that band's extra-per-kg rate then covers the
        // overage rather than the shipment having no price at all.
        if (! $rate) {
            $rate = \App\Models\ZoneWeightRate::where('rate_card_id', $rateCard->id)
                ->where('zone_id', $zoneId)
                ->where('service_type', $serviceType)
                ->orderByDesc('max_weight')
                ->first();
        }

        if (! $rate) {
            throw new InvalidArgumentException('No rate configured for this zone and service type.');
        }

        $overage = max(0, $weight - (float) $rate->max_weight);

        return (float) $rate->price + ($overage * (float) $rate->extra_amount_per_extra_kg);
    }

    /**
     * A route between two states equates to exactly one zone, regardless
     * of direction (see ZoneMapping::resolveZone). Resolves each city to
     * its state first, since that's the level zone mapping actually
     * operates at — not the city itself.
     */
    private function resolveZoneIdForRoute(?int $originCityId, ?int $destinationCityId): ?int
    {
        if (! $originCityId || ! $destinationCityId) {
            return null;
        }

        $originStateId = \App\Models\City::find($originCityId)?->state_id;
        $destinationStateId = \App\Models\City::find($destinationCityId)?->state_id;

        if (! $originStateId || ! $destinationStateId) {
            return null;
        }

        return \App\Models\ZoneMapping::resolveZone($originStateId, $destinationStateId)?->id;
    }

    /**
     * Resolves the shipment's zone (same resolution as
     * originDestinationWeight — city → state → ZoneMapping), then looks
     * up this rate card's CartonRate row for (zone, carton_size), and
     * multiplies by quantity (number of cartons). Falls back to zero
     * rather than throwing if no matching row exists, since a missing
     * carton-rate row is a setup gap, not something that should hard-fail
     * a booking — same "degrade gracefully" posture as
     * originDestinationWeight's weight-band fallback.
     */
    private function cartonRate(RateCard $rateCard, array $context): float
    {
        $zoneId = $this->resolveZoneIdForRoute(
            $context['origin_city_id'] ?? null,
            $context['destination_city_id'] ?? null
        );

        $cartonSize = $context['carton_size'] ?? null;
        $quantity = (float) ($context['quantity'] ?? 0);

        if (! $zoneId || ! $cartonSize || $quantity <= 0) {
            return 0.0;
        }

        $rate = \App\Models\CartonRate::where('rate_card_id', $rateCard->id)
            ->where('zone_id', $zoneId)
            ->where('carton_size', $cartonSize)
            ->first();

        return $rate ? $quantity * (float) $rate->price_per_carton : 0.0;
    }

    /**
     * Used by 'truckload' — a flat quantity × per-unit rate from the
     * rate card's own model_config. carton_rate used to share this too,
     * but now has its own zone/size-aware calculation above.
     */
    private function perUnit(RateCard $rateCard, array $context, string $rateConfigKey): float
    {
        $quantity = (float) ($context['quantity'] ?? 0);
        $rate = (float) ($rateCard->model_config[$rateConfigKey] ?? 0);

        return $quantity * $rate;
    }

    /**
     * Generic tiered-band calculator, shared by distance and weight models.
     * Tiers: [{"upto_km": 10, "per_km": 100}, {"upto_km": 50, "per_km": 80}, ...]
     * The last tier's upper bound is treated as unbounded.
     */
    private function applyTieredRate(float $quantity, array $tiers, string $upToKey, string $rateKey): float
    {
        $remaining = $quantity;
        $total = 0.0;
        $previousUpTo = 0.0;

        foreach ($tiers as $tier) {
            $upTo = $tier[$upToKey] ?? null;
            $rate = (float) ($tier[$rateKey] ?? 0);
            $bandSize = $upTo === null ? $remaining : min($remaining, $upTo - $previousUpTo);

            if ($bandSize <= 0) {
                continue;
            }

            $total += $bandSize * $rate;
            $remaining -= $bandSize;
            $previousUpTo = $upTo ?? $previousUpTo;

            if ($remaining <= 0) {
                break;
            }
        }

        return $total;
    }
}
