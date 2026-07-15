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
