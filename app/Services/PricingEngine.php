<?php

namespace App\Services;

use App\Models\City;
use App\Models\Country;
use App\Models\ServiceType;
use App\Models\StandardBillingTariff;
use App\Models\TariffZonePrice;
use App\Models\ThirdPartyCountryMapping;
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
     *   base_amount      float
     *   transit_days     ?int
     *   shipping_type    'domestic'|'international'
     *   zone_id          int
     *   billed_weight_kg ?float — the actual weight rounded up to the
     *                     matched tariff's own additional_weight
     *                     increment (Standard Billing only; a future
     *                     non-weight-based model just wouldn't set
     *                     this key)
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
        //
        // The actual weight is rounded UP to the tariff's own
        // additional_weight increment before this math runs — a
        // shipment is always billed in whole increments (0.6kg bills as
        // 1kg on a 0.5kg increment, 1.6–1.9kg both bill as 2kg), never a
        // fraction of one. This only affects the charge calculation —
        // which tariff band the shipment matched above was decided by
        // the real, unrounded weight, so a shipment doesn't jump into
        // the wrong band just from rounding.
        $additionalWeightUnit = max(0.01, (float) $tariff->additional_weight);
        // A tiny epsilon before ceil() guards against binary
        // floating-point imprecision (e.g. 1.0 / 0.1 landing on
        // 9.999999999999998 instead of exactly 10) rounding a weight
        // that's genuinely an exact multiple up to one extra,
        // unnecessary increment — a real overcharge risk for financial
        // math, not a theoretical one.
        $chargeableWeight = ceil(($weight / $additionalWeightUnit) - 0.00001) * $additionalWeightUnit;

        $overageWeight = max(0, $chargeableWeight - (float) $tariff->min_weight);
        $increments = $overageWeight > 0 ? (int) ceil($overageWeight / $additionalWeightUnit) : 0;

        $baseAmount = (float) $zonePrice->charge + ($increments * (float) $zonePrice->additional_charge);

        return [
            'base_amount' => round($baseAmount, 2),
            'billed_weight_kg' => $chargeableWeight,
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
        $originCountryId = $context['origin_country_id'] ?? null;
        $destinationCountryId = $context['destination_country_id'] ?? null;

        // A genuine third-party route: both sides are given as
        // countries, and NEITHER is Nigeria — e.g. US to Congo, a
        // shipment this business arranges without touching Nigeria at
        // all. Checked before the Nigeria-anchored logic below, since
        // that logic would otherwise match on whichever side happens to
        // look "foreign" and silently price this as if Nigeria were
        // involved, which it isn't.
        if ($originCountryId && $destinationCountryId
            && $originCountryId != $domesticCountryId && $destinationCountryId != $domesticCountryId) {
            $zone = ThirdPartyCountryMapping::resolveZone($originCountryId, $destinationCountryId);

            if ($zone) {
                return [$zone, 'third_party'];
            }

            throw new PricingUnavailableException('No route configured for this country pair yet (Billing → Zone Mapping → Third-Party).');
        }

        $foreignCountryId = null;

        if (! empty($destinationCountryId) && $destinationCountryId != $domesticCountryId) {
            $foreignCountryId = $destinationCountryId;
        } elseif (! empty($originCountryId) && $originCountryId != $domesticCountryId) {
            $foreignCountryId = $originCountryId;
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
