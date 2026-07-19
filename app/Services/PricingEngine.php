<?php

namespace App\Services;

use App\Models\City;
use App\Models\Country;
use App\Models\ServiceType;
use App\Models\StandardBillingTariff;
use App\Models\TariffZonePrice;
use App\Models\Zone;
use App\Models\ZoneCountryMapping;
use App\Models\ZoneMapping;

class PricingEngine
{
    /**
     * Entry point for every quote/booking. Looks up the requested
     * ServiceType, dispatches to whichever billing model it's assigned
     * to (see ServiceType::billing_model / Setting::BILLING_MODELS), and
     * returns:
     *   base_amount   float
     *   transit_days  ?int
     *   shipping_type 'domestic'|'international'
     *   zone_id       int
     *
     * Throws PricingUnavailableException — never returns a guessed or
     * zero price — whenever the service type has no model assigned, the
     * model isn't implemented yet, the route has no zone mapping, or no
     * tariff matches. Adding a second billing model later means adding
     * one more match arm below; nothing about this method's contract
     * changes for callers.
     */
    public function quote(array $context): array
    {
        $serviceType = ServiceType::find($context['service_type_id'] ?? null);

        if (! $serviceType) {
            throw new PricingUnavailableException('Unknown service type.');
        }

        if (! $serviceType->billing_model) {
            throw new PricingUnavailableException("The \"{$serviceType->name}\" service type has no billing model configured yet.");
        }

        return match ($serviceType->billing_model) {
            'standard_billing' => $this->standardBilling($serviceType, $context),
            default => throw new PricingUnavailableException("Billing model \"{$serviceType->billing_model}\" isn't implemented yet."),
        };
    }

    /**
     * Standard Billing pricing:
     *  1. resolve the route to a zone (domestic state-pair or
     *     international country mapping — see resolveZoneAndType())
     *  2. find the tariff for this service type whose weight band
     *     contains the shipment weight
     *  3. find that tariff's price row for the resolved zone
     *  4. the zone's charge covers the tariff's min_weight specifically
     *     — weight above min_weight is charged in additional_weight-
     *     sized increments at the zone's additional_charge, continuing
     *     up through max_weight (and beyond, for the "heavier than
     *     every configured band" fallback below)
     */
    private function standardBilling(ServiceType $serviceType, array $context): array
    {
        [$zone, $shippingType] = $this->resolveZoneAndType($context);

        if (! $zone) {
            throw new PricingUnavailableException('No zone mapping configured for this route yet (Billing → Zone Mapping).');
        }

        $weight = (float) ($context['weight_kg'] ?? 0);

        // orderBy makes this deterministic if two tariffs for the same
        // service type ever have overlapping weight bands (a setup
        // mistake nothing currently prevents) — picks the narrowest/
        // lowest-starting band rather than depending on database row
        // order, which would otherwise vary by engine.
        $tariff = StandardBillingTariff::where('service_type_id', $serviceType->id)
            ->where('is_active', true)
            ->where('min_weight', '<=', $weight)
            ->where('max_weight', '>=', $weight)
            ->orderBy('min_weight')
            ->first();

        // Heavier than every configured band? Fall back to the highest
        // band as the base, with overage still applied — a shipment
        // never fails to price just for being heavier than anticipated,
        // matching the same posture the earlier (now-cleared)
        // origin_destination_weight model used.
        if (! $tariff) {
            $tariff = StandardBillingTariff::where('service_type_id', $serviceType->id)
                ->where('is_active', true)
                ->orderByDesc('max_weight')
                ->first();
        }

        if (! $tariff) {
            throw new PricingUnavailableException('No tariff configured for this service type and weight (Pricing Engine → Standard Billing).');
        }

        $zonePrice = TariffZonePrice::where('tariff_id', $tariff->id)->where('zone_id', $zone->id)->first();

        if (! $zonePrice) {
            throw new PricingUnavailableException("No price configured for {$zone->name} on this tariff.");
        }

        // Base charge covers the tariff's min_weight specifically, not
        // the whole band — extra weight accrues from min_weight upward
        // (continuing past max_weight too, for the "heavier than every
        // configured band" fallback above), one additional_charge per
        // additional_weight increment.
        $overageWeight = max(0, $weight - (float) $tariff->min_weight);
        $additionalWeightUnit = max(0.01, (float) $tariff->additional_weight);
        $increments = $overageWeight > 0 ? (int) ceil($overageWeight / $additionalWeightUnit) : 0;

        $baseAmount = (float) $zonePrice->charge + ($increments * (float) $zonePrice->additional_charge);

        return [
            'base_amount' => round($baseAmount, 2),
            'transit_days' => $zonePrice->transit_days,
            'shipping_type' => $shippingType,
            'zone_id' => $zone->id,
        ];
    }

    /**
     * Domestic when both origin and destination resolve to a Nigerian
     * state — uses ZoneMapping (state-pairs), same as the Zone Mapping
     * screen. International when either side is a foreign country —
     * uses ZoneCountryMapping, checking destination first (the far more
     * common outbound case) then origin (inbound).
     *
     * A state can be given directly (origin_state_id/destination_state_id)
     * or derived from a city (origin_city_id/destination_city_id) — a
     * city is one way to arrive at a state for zone resolution, not the
     * only way. Explicit state_id takes priority when both are somehow
     * present.
     */
    private function resolveZoneAndType(array $context): array
    {
        $originStateId = $context['origin_state_id']
            ?? (! empty($context['origin_city_id']) ? City::find($context['origin_city_id'])?->state_id : null);
        $destinationStateId = $context['destination_state_id']
            ?? (! empty($context['destination_city_id']) ? City::find($context['destination_city_id'])?->state_id : null);

        if ($originStateId && $destinationStateId) {
            $zone = ZoneMapping::resolveZone($originStateId, $destinationStateId);

            if ($zone) {
                return [$zone, 'domestic'];
            }
        }

        $domesticCountryId = Country::where('code', 'NG')->value('id');
        $foreignCountryId = null;

        if (! empty($context['destination_country_id']) && $context['destination_country_id'] != $domesticCountryId) {
            $foreignCountryId = $context['destination_country_id'];
        } elseif (! empty($context['origin_country_id']) && $context['origin_country_id'] != $domesticCountryId) {
            $foreignCountryId = $context['origin_country_id'];
        }

        if ($foreignCountryId) {
            $mapping = ZoneCountryMapping::where('country_b_id', $foreignCountryId)->first();

            if ($mapping?->zone_id) {
                return [Zone::find($mapping->zone_id), 'international'];
            }
        }

        return [null, null];
    }
}
