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
     *   base_amount          float
     *   transit_days         ?int
     *   shipping_type        'domestic'|'international'|'third_party'
     *   zone_id              ?int — null for a billing model that
     *                        doesn't resolve through a zone at all
     *                        (Origin to Destination prices a route
     *                        directly)
     *   chargeable_weight_kg ?float — the greater of actual weight
     *                        and volumetric weight (L×W×H ÷ the
     *                        configured divisor), before rounding
     *   billed_weight_kg     ?float — chargeable_weight_kg rounded up
     *                        to the matched tariff's own
     *                        additional_weight increment (every
     *                        weight-band model sets these two keys —
     *                        Standard Billing, Origin to Destination,
     *                        Fleet Billing; a future non-weight-based
     *                        model just wouldn't)
     *   surcharges           array<string, float> — labeled surcharge
     *                        amounts computed by the billing model
     *                        itself (currently only Fleet Billing's
     *                        fuel surcharge / empty-return charge);
     *                        absent for models with nothing to add.
     *                        The caller merges this into
     *                        $context['surcharges'] before calling
     *                        ShipmentPricingService::priceShipment()
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
            'origin_destination_billing' => $this->originDestinationBilling($serviceType, $context),
            'fleet_billing' => $this->fleetBilling($serviceType, $context),
            default => throw new PricingUnavailableException("Billing model \"{$serviceType->billing_model}\" isn't implemented yet."),
        };
    }

    /**
     * Standard Billing pricing:
     *  1. resolve the route to a zone (domestic state-pair or
     *     international country mapping — see resolveZoneAndType())
     *  2. find the tariff for this service type whose weight band
     *     (min_weight to max_weight_limit) contains the shipment weight
     *  3. find that tariff's price row for the resolved zone
     *  4. the zone's charge covers the tariff up to max_weight —
     *     weight above that is charged in additional_weight-sized
     *     increments at the zone's additional_charge, continuing up
     *     through max_weight_limit (and beyond, for the "heavier than
     *     every configured band" fallback below). max_weight is
     *     independently settable from min_weight/max_weight_limit — it
     *     need not equal either.
     */
    private function standardBilling(ServiceType $serviceType, array $context): array
    {
        [$zone, $shippingType] = $this->resolveZoneAndType($context);

        if (! $zone) {
            throw new PricingUnavailableException('No zone mapping configured for this route yet (Billing → Zone Mapping).');
        }

        $chargeableWeight = $this->resolveChargeableWeight($context);

        // orderBy makes this deterministic if two tariffs for the same
        // service type ever have overlapping weight bands (a setup
        // mistake nothing currently prevents) — picks the narrowest/
        // lowest-starting band rather than depending on database row
        // order, which would otherwise vary by engine.
        $tariff = StandardBillingTariff::where('service_type_id', $serviceType->id)
            ->where('is_active', true)
            ->where('min_weight', '<=', $chargeableWeight)
            ->where('max_weight_limit', '>=', $chargeableWeight)
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
                ->orderByDesc('max_weight_limit')
                ->first();
        }

        if (! $tariff) {
            throw new PricingUnavailableException('No tariff configured for this service type and weight (Billing → Standard Billing → Zoning and Weight).');
        }

        $zonePrice = TariffZonePrice::where('tariff_id', $tariff->id)->where('zone_id', $zone->id)->first();

        if (! $zonePrice) {
            throw new PricingUnavailableException("No price configured for {$zone->name} on this tariff.");
        }

        $result = $this->calculateWeightBasedCharge(
            (float) $zonePrice->charge,
            (float) $zonePrice->additional_charge,
            $chargeableWeight,
            (float) ($tariff->max_weight ?? $tariff->min_weight),
            (float) $tariff->additional_weight
        );

        return [
            'base_amount' => round($result['amount'], 2),
            'chargeable_weight_kg' => round($chargeableWeight, 2),
            'billed_weight_kg' => $result['billed_weight'],
            'transit_days' => $zonePrice->transit_days,
            'shipping_type' => $shippingType,
            'zone_id' => $zone->id,
        ];
    }

    /**
     * Origin to Destination pricing — a second, genuinely different
     * billing model from Standard Billing: a direct route lookup, no
     * Zone/ZoneMapping involved. Each OriginDestinationTariff prices one
     * specific route directly; the weight-band matching, max_weight
     * overage reference, and rounding are identical in shape to
     * Standard Billing (see calculateWeightBasedCharge()), just applied
     * to a route-matched tariff instead of a zone-matched one.
     *
     * Either side can be a Nigeria state (+ optional city) or a
     * country — mirroring Standard Billing's Domestic/International
     * split, driven the same way by the selected service type's
     * route_type/trade_direction (Export: Nigeria origin, foreign
     * country destination; Import: the reverse). shipping_type is
     * 'international' whenever either side is country-based.
     *
     * A shipment's origin/destination city, if given, is matched
     * against a city-specific tariff row first — a state-wide row
     * (origin_city_id/destination_city_id null) is the fallback that
     * always applies unless a more specific one exists. See
     * resolveOriginDestinationTariff() for exactly how specificity is
     * scored.
     */
    private function originDestinationBilling(ServiceType $serviceType, array $context): array
    {
        $originCountryId = $context['origin_country_id'] ?? null;
        $destinationCountryId = $context['destination_country_id'] ?? null;

        $originStateId = $originCountryId ? null : ($context['origin_state_id']
            ?? (! empty($context['origin_city_id']) ? City::find($context['origin_city_id'])?->state_id : null));
        $originCityId = $originCountryId ? null : ($context['origin_city_id'] ?? null);

        $destinationStateId = $destinationCountryId ? null : ($context['destination_state_id']
            ?? (! empty($context['destination_city_id']) ? City::find($context['destination_city_id'])?->state_id : null));
        $destinationCityId = $destinationCountryId ? null : ($context['destination_city_id'] ?? null);

        if ((! $originStateId && ! $originCountryId) || (! $destinationStateId && ! $destinationCountryId)) {
            throw new PricingUnavailableException('Origin and destination are required for this service type.');
        }

        $shippingType = ($originCountryId || $destinationCountryId) ? 'international' : 'domestic';

        $chargeableWeight = $this->resolveChargeableWeight($context);

        $tariff = $this->resolveOriginDestinationTariff(
            $serviceType->id, $originStateId, $originCityId, $originCountryId,
            $destinationStateId, $destinationCityId, $destinationCountryId, $chargeableWeight
        );

        // Heavier than every configured band for this exact route? Fall
        // back to the highest band on that SAME route rather than
        // failing outright — matches Standard Billing's posture.
        if (! $tariff) {
            $tariff = $this->resolveOriginDestinationTariff(
                $serviceType->id, $originStateId, $originCityId, $originCountryId,
                $destinationStateId, $destinationCityId, $destinationCountryId, null
            );
        }

        if (! $tariff) {
            throw new PricingUnavailableException('No rate configured for this route yet (Billing → Standard Billing → Origin to Destination).');
        }

        $result = $this->calculateWeightBasedCharge(
            (float) $tariff->base_charge,
            (float) $tariff->additional_charge,
            $chargeableWeight,
            (float) $tariff->max_weight,
            (float) $tariff->additional_weight
        );

        return [
            'base_amount' => round($result['amount'], 2),
            'chargeable_weight_kg' => round($chargeableWeight, 2),
            'billed_weight_kg' => $result['billed_weight'],
            'transit_days' => $tariff->transit_days,
            'shipping_type' => $shippingType,
            'zone_id' => null,
        ];
    }

    /**
     * Fleet Billing — cost-based freight rating, simplified per
     * explicit direction to just the weight charge (Base Haul Rate,
     * Distance Charge, and the Minimum Trip Charge floor were removed
     * entirely):
     *
     *   freight = weight_charge
     *   fuel_surcharge = freight × fuel_surcharge_percentage
     *   empty_return    = flat or % of freight, ONLY when the shipper
     *                     marks this trip as empty-return at booking/
     *                     quote time — not part of every quote
     *
     * weight_charge reuses the exact same weight-band mechanism as
     * Standard Billing / Origin to Destination (calculateWeightBasedCharge()) —
     * base_charge/additional_weight/additional_charge on the matched
     * tariff.
     *
     * Before matching a tariff at all, the shipment's chargeable weight
     * is checked against the selected VehicleType's own
     * max_weight_capacity — a hard, quote-time check independent of
     * whatever any tariff's own weight band says. A tariff's own Max
     * weight limit is separately validated at configuration time
     * (FleetBillingTariffController) not to exceed this same figure,
     * but that only stops a bad tariff from being SAVED — this stops a
     * shipment from being PRICED past the vehicle's real capacity
     * regardless of how a tariff happens to be configured.
     *
     * Route/lane matching is identical in shape to Origin to
     * Destination (see resolveRouteTariff()), with one more condition:
     * vehicle type must match too.
     *
     * fuel_surcharge and empty_return are returned as a 'surcharges'
     * array rather than folded into base_amount — the same generic,
     * labeled mechanism ShipmentPricingService::calculateSurcharges()
     * already accepts (previously unused by any billing model). The
     * caller merges this into $context['surcharges'] before calling
     * priceShipment(), so both show as their own line items on a
     * quote rather than one opaque number.
     */
    private function fleetBilling(ServiceType $serviceType, array $context): array
    {
        if (empty($context['vehicle_type_id'])) {
            throw new PricingUnavailableException('A vehicle type is required for this service type.');
        }

        $originCountryId = $context['origin_country_id'] ?? null;
        $destinationCountryId = $context['destination_country_id'] ?? null;

        $originStateId = $originCountryId ? null : ($context['origin_state_id']
            ?? (! empty($context['origin_city_id']) ? City::find($context['origin_city_id'])?->state_id : null));
        $originCityId = $originCountryId ? null : ($context['origin_city_id'] ?? null);

        $destinationStateId = $destinationCountryId ? null : ($context['destination_state_id']
            ?? (! empty($context['destination_city_id']) ? City::find($context['destination_city_id'])?->state_id : null));
        $destinationCityId = $destinationCountryId ? null : ($context['destination_city_id'] ?? null);

        if ((! $originStateId && ! $originCountryId) || (! $destinationStateId && ! $destinationCountryId)) {
            throw new PricingUnavailableException('Origin and destination are required for this service type.');
        }

        $shippingType = ($originCountryId || $destinationCountryId) ? 'international' : 'domestic';

        $chargeableWeight = $this->resolveChargeableWeight($context);
        $vehicleTypeId = (int) $context['vehicle_type_id'];
        $vehicleType = \App\Models\VehicleType::find($vehicleTypeId);

        // A hard check against the vehicle's own real capacity —
        // independent of whatever a tariff's weight band says. A
        // tariff's Max weight limit is validated not to exceed this at
        // configuration time (FleetBillingTariffController), but this
        // catches it at quote time too, regardless of how any tariff
        // was configured.
        if ($vehicleType?->max_weight_capacity && $chargeableWeight > (float) $vehicleType->max_weight_capacity) {
            throw new PricingUnavailableException("This shipment ({$chargeableWeight}kg) exceeds {$vehicleType->name}'s capacity ({$vehicleType->max_weight_capacity}kg) — choose a larger vehicle type.");
        }

        $tariff = $this->resolveFleetBillingTariff(
            $serviceType->id, $vehicleTypeId, $originStateId, $originCityId, $originCountryId,
            $destinationStateId, $destinationCityId, $destinationCountryId, $chargeableWeight
        );

        if (! $tariff) {
            $tariff = $this->resolveFleetBillingTariff(
                $serviceType->id, $vehicleTypeId, $originStateId, $originCityId, $originCountryId,
                $destinationStateId, $destinationCityId, $destinationCountryId, null
            );
        }

        if (! $tariff) {
            throw new PricingUnavailableException('No fleet rate configured for this route and vehicle type yet (Billing → Standard Billing → Fleet Billing).');
        }

        $weightResult = $this->calculateWeightBasedCharge(
            (float) $tariff->base_charge,
            (float) $tariff->additional_charge,
            $chargeableWeight,
            (float) $tariff->max_weight,
            (float) $tariff->additional_weight
        );

        $freight = $weightResult['amount'];

        $surcharges = [];

        $fuelSurcharge = round($freight * ((float) $tariff->fuel_surcharge_percentage / 100), 2);
        if ($fuelSurcharge > 0) {
            $surcharges['Fuel surcharge'] = $fuelSurcharge;
        }

        if (! empty($context['is_empty_return'])) {
            $emptyReturnCharge = $tariff->resolveEmptyReturnCharge($freight);
            if ($emptyReturnCharge > 0) {
                $surcharges['Empty return charge'] = $emptyReturnCharge;
            }
        }

        return [
            'base_amount' => round($freight, 2),
            'chargeable_weight_kg' => round($chargeableWeight, 2),
            'billed_weight_kg' => $weightResult['billed_weight'],
            'transit_days' => $tariff->transit_days,
            'shipping_type' => $shippingType,
            'zone_id' => null,
            'surcharges' => $surcharges,
        ];
    }

    /**
     * Route to the shared resolveRouteTariff() helper — see its own
     * docblock for exactly how matching/specificity works.
     */
    private function resolveOriginDestinationTariff(
        int $serviceTypeId,
        ?int $originStateId, ?int $originCityId, ?int $originCountryId,
        ?int $destinationStateId, ?int $destinationCityId, ?int $destinationCountryId,
        ?float $chargeableWeight
    ): ?\App\Models\OriginDestinationTariff {
        return $this->resolveRouteTariff(
            \App\Models\OriginDestinationTariff::class, [],
            $serviceTypeId, $originStateId, $originCityId, $originCountryId,
            $destinationStateId, $destinationCityId, $destinationCountryId, $chargeableWeight
        );
    }

    private function resolveFleetBillingTariff(
        int $serviceTypeId, int $vehicleTypeId,
        ?int $originStateId, ?int $originCityId, ?int $originCountryId,
        ?int $destinationStateId, ?int $destinationCityId, ?int $destinationCountryId,
        ?float $chargeableWeight
    ): ?\App\Models\FleetBillingTariff {
        return $this->resolveRouteTariff(
            \App\Models\FleetBillingTariff::class, ['vehicle_type_id' => $vehicleTypeId],
            $serviceTypeId, $originStateId, $originCityId, $originCountryId,
            $destinationStateId, $destinationCityId, $destinationCountryId, $chargeableWeight
        );
    }

    /**
     * Finds the most specific ACTIVE tariff for an exact origin/
     * destination pair, on whichever model class is given — shared by
     * Origin to Destination and Fleet Billing, since both price a
     * direct route the same way, just with different extra columns
     * ($extraConditions — e.g. Fleet Billing also matches on
     * vehicle_type_id). Each side matched by country when a country id
     * is given, or by state (+ optional city specificity) otherwise.
     * When $chargeableWeight is given, only bands actually containing
     * that weight are considered; passing null (the "heavier than
     * everything" fallback case) considers every band for the route and
     * picks the highest one.
     *
     * Specificity: for a state-based side, a row's city field must be
     * either null (state-wide, always eligible) or match the
     * shipment's actual city. Among eligible rows, the one with the
     * MOST non-null city matches wins — a row specific to both cities
     * beats one specific to only origin, which beats a fully
     * state-wide row. A country-based side has no such refinement —
     * it's an exact country match or it isn't eligible at all.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     * @param class-string<TModel> $modelClass
     * @return TModel|null
     */
    private function resolveRouteTariff(
        string $modelClass, array $extraConditions,
        int $serviceTypeId,
        ?int $originStateId, ?int $originCityId, ?int $originCountryId,
        ?int $destinationStateId, ?int $destinationCityId, ?int $destinationCountryId,
        ?float $chargeableWeight
    ) {
        $query = $modelClass::where('service_type_id', $serviceTypeId)
            ->where('is_active', true);

        foreach ($extraConditions as $column => $value) {
            $query->where($column, $value);
        }

        if ($originCountryId) {
            $query->where('origin_country_id', $originCountryId);
        } else {
            $query->where('origin_state_id', $originStateId)->whereNull('origin_country_id');
        }

        if ($destinationCountryId) {
            $query->where('destination_country_id', $destinationCountryId);
        } else {
            $query->where('destination_state_id', $destinationStateId)->whereNull('destination_country_id');
        }

        if ($chargeableWeight !== null) {
            $query->where('min_weight', '<=', $chargeableWeight)->where('max_weight_limit', '>=', $chargeableWeight);
        }

        return $query->get()
            ->filter(function ($tariff) use ($originCityId, $destinationCityId) {
                $originOk = is_null($tariff->origin_city_id) || $tariff->origin_city_id == $originCityId;
                $destinationOk = is_null($tariff->destination_city_id) || $tariff->destination_city_id == $destinationCityId;

                return $originOk && $destinationOk;
            })
            ->sortByDesc(fn ($tariff) => ($tariff->origin_city_id ? 1 : 0) + ($tariff->destination_city_id ? 1 : 0))
            ->when($chargeableWeight === null, fn ($collection) => $collection->sortByDesc('max_weight_limit'))
            ->first();
    }

    /**
     * The greater of actual weight and volumetric weight (L×W×H ÷ the
     * company's configured divisor, Company Settings → Billing
     * defaults) — only computed when all three dimensions are given. A
     * large-but-light package is priced by the space it takes up, not
     * just what it weighs on a scale, matching standard courier
     * practice. Shared by every weight-based billing model.
     */
    private function resolveChargeableWeight(array $context): float
    {
        $weight = (float) ($context['weight_kg'] ?? 0);

        $lengthCm = (float) ($context['length_cm'] ?? 0);
        $widthCm = (float) ($context['width_cm'] ?? 0);
        $heightCm = (float) ($context['height_cm'] ?? 0);
        $volumetricWeight = 0.0;

        if ($lengthCm > 0 && $widthCm > 0 && $heightCm > 0) {
            $divisor = max(1, (int) (\App\Models\Setting::current()->volumetric_divisor ?? 5000));
            $volumetricWeight = ($lengthCm * $widthCm * $heightCm) / $divisor;
        }

        return max($weight, $volumetricWeight);
    }

    /**
     * Rounds chargeable weight up to the tariff's own additional_weight
     * increment, then computes base + per-increment additional charge,
     * overage measured from $maxWeightLimit — shared by every
     * weight-band billing model so the epsilon-guarded rounding and
     * overage math live in exactly one place, not duplicated per model.
     *
     * A shipment is always billed in whole increments (0.6kg bills as
     * 1kg on a 0.5kg increment, 1.6–1.9kg both bill as 2kg), never a
     * fraction of one. A tiny epsilon before ceil() guards against
     * binary floating-point imprecision (e.g. 1.0 / 0.1 landing on
     * 9.999999999999998 instead of exactly 10) rounding a weight that's
     * genuinely an exact multiple up to one extra, unnecessary
     * increment — a real overcharge risk for financial math, not a
     * theoretical one.
     *
     * @return array{amount: float, billed_weight: float}
     */
    private function calculateWeightBasedCharge(float $baseCharge, float $additionalCharge, float $chargeableWeight, float $maxWeightLimit, float $additionalWeight): array
    {
        $additionalWeightUnit = max(0.01, $additionalWeight);
        $billedWeight = ceil(($chargeableWeight / $additionalWeightUnit) - 0.00001) * $additionalWeightUnit;

        $overageWeight = max(0, $billedWeight - $maxWeightLimit);
        $increments = $overageWeight > 0 ? (int) ceil($overageWeight / $additionalWeightUnit) : 0;

        return [
            'amount' => $baseCharge + ($increments * $additionalCharge),
            'billed_weight' => $billedWeight,
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
