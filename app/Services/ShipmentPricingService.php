<?php

namespace App\Services;

use App\Models\ClientBillingProfile;

class ShipmentPricingService
{
    public function __construct(private PricingEngine $pricingEngine)
    {
    }

    /**
     * Returns the full pricing breakdown for a shipment quote.
     *
     * base_amount is read from $context['base_amount'] rather than
     * calculated here — the billing-model calculation layer (previously
     * RateEngine/RateCard) was cleared to be rebuilt one model at a time.
     * Until a given billing model is rebuilt and its result placed into
     * the context by the caller, base_amount is 0 — everything else
     * (discount, insurance, onforwarding, VAT) still works correctly in
     * the meantime, since none of that depends on how the base freight
     * charge was calculated.
     *
     * $billingProfile is optional — pass the requester's
     * ClientBillingProfile (see ClientBillingProfile::resolveForRequest
     * or ::resolveForClientUser) to apply a 'special' discount. Standard
     * clients (the default, and anyone with no profile at all) pass null
     * and get the plain price with no adjustment.
     */
    public function priceShipment(array $context, ?ClientBillingProfile $billingProfile = null): array
    {
        $baseAmount = (float) ($context['base_amount'] ?? 0);
        $surchargeAmount = $this->calculateSurcharges($context);

        $discountFraction = $billingProfile?->discountFraction() ?? 0.0;
        $discountAmount = round(($baseAmount + $surchargeAmount) * $discountFraction, 2);

        // Discount applies to freight + surcharges only — insurance and
        // onforwarding are pass-through costs, not part of the negotiated
        // rate, so neither is ever discounted.
        $discountedFreight = ($baseAmount + $surchargeAmount) - $discountAmount;
        $insuranceAmount = $this->calculateInsurance($context);
        $onforwardingAmount = $this->calculateOnforwarding($context);
        $additionalServicesAmount = $this->calculateAdditionalServices($context, $baseAmount);

        $vatPercentage = (float) ($context['vat_percentage'] ?? config('branding.vat_percentage', 0));
        $taxableAmount = $discountedFreight + $insuranceAmount + $onforwardingAmount + $additionalServicesAmount;
        $vatAmount = round($taxableAmount * ($vatPercentage / 100), 2);

        $total = round($taxableAmount + $vatAmount, 2);

        return [
            'base_amount' => round($baseAmount, 2),
            'surcharge_amount' => round($surchargeAmount, 2),
            'onforwarding_amount' => round($onforwardingAmount, 2),
            'additional_services_amount' => round($additionalServicesAmount, 2),
            'discount_amount' => $discountAmount,
            'insurance_amount' => round($insuranceAmount, 2),
            'vat_amount' => $vatAmount,
            'total_amount' => $total,
        ];
    }

    private function calculateSurcharges(array $context): float
    {
        $total = 0.0;
        $surcharges = $context['surcharges'] ?? []; // e.g. ['fuel' => 200, 'remote_area' => 500]

        foreach ($surcharges as $amount) {
            $total += (float) $amount;
        }

        return $total;
    }

    /**
     * Optional add-ons (packaging, fragile handling, "Acknowledgement",
     * etc.) — pass whichever AdditionalServiceOption IDs were selected
     * via $context['additional_service_option_ids']. Each option can
     * have multiple priced variants (Packaging: Small/Medium/Large Box),
     * and each variant resolves differently depending on its
     * charge_type: a flat amount, a percentage of this shipment's
     * freight, or (for something like "Acknowledgement" — a document
     * sent back to origin) a percentage of a SEPARATE reverse
     * shipment's rate, priced through the real pricing engine using
     * that option's own configured service type and weight. Same
     * treatment as insurance and onforwarding either way: a real extra
     * service, not part of the negotiated freight rate, so never
     * discounted but still taxable.
     */
    private function calculateAdditionalServices(array $context, float $baseAmount): float
    {
        $ids = $context['additional_service_option_ids'] ?? [];

        if (empty($ids)) {
            return 0.0;
        }

        return \App\Models\AdditionalServiceOption::whereIn('id', $ids)->where('is_active', true)
            ->get()
            ->sum(fn ($option) => $option->charge_type === 'percentage_of_reverse_shipment'
                ? $option->resolveReverseShipmentAmount($this->pricingEngine, $context)
                : $option->resolveAmount($baseAmount));
    }

    private function calculateInsurance(array $context): float
    {
        if (empty($context['insured']) || empty($context['declared_value'])) {
            return 0.0;
        }

        $rate = (float) ($context['insurance_rate_percentage'] ?? 1); // default 1% of declared value

        return round(((float) $context['declared_value']) * ($rate / 100), 2);
    }

    /**
     * Sums whichever onforwarding fee applies on each side of the
     * shipment — origin and destination are checked independently, so a
     * shipment onforwarding on both ends is charged for both. District
     * classification takes priority over city classification when a
     * district is set, since it's the more specific match.
     */
    private function calculateOnforwarding(array $context): float
    {
        return $this->resolveOnforwardingFee($context['origin_district_id'] ?? null, $context['origin_city_id'] ?? null)
            + $this->resolveOnforwardingFee($context['destination_district_id'] ?? null, $context['destination_city_id'] ?? null);
    }

    private function resolveOnforwardingFee(?int $districtId, ?int $cityId): float
    {
        if ($districtId) {
            $district = \App\Models\District::with('onforwardingClassification')->find($districtId);

            if ($district?->onforwardingClassification) {
                return (float) $district->onforwardingClassification->surcharge_amount;
            }
        }

        if ($cityId) {
            $city = \App\Models\City::with('onforwardingClassification')->find($cityId);

            if ($city?->onforwardingClassification) {
                return (float) $city->onforwardingClassification->surcharge_amount;
            }
        }

        return 0.0;
    }
}
