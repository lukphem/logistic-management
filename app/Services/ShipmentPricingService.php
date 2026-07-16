<?php

namespace App\Services;

use App\Models\ClientBillingProfile;
use App\Models\RateCard;

class ShipmentPricingService
{
    public function __construct(private RateEngine $rateEngine)
    {
    }

    /**
     * Returns the full pricing breakdown for a shipment quote.
     * $context should include the raw shipment attributes (see RateEngine::calculateBase)
     * plus optional 'insurance_value' and 'vat_percentage' overrides.
     *
     * $billingProfile is optional — pass the requester's
     * ClientBillingProfile (see ClientBillingProfile::resolveForRequest)
     * to apply a 'special' discount. Standard clients (the default, and
     * anyone with no profile at all) pass null or a 'standard' profile
     * and get the plain rate-card price with no adjustment.
     *
     * The discount is always applied against whatever the standard rate
     * resolves to right now — it is never a frozen number. Raise the
     * standard rate later and every special client's price moves with
     * it automatically; only the discount_percentage itself is a
     * separate, deliberate edit.
     */
    public function priceShipment(RateCard $rateCard, array $context, ?ClientBillingProfile $billingProfile = null): array
    {
        $baseAmount = $this->rateEngine->calculateBase($rateCard, $context);
        $surchargeAmount = $this->calculateSurcharges($context);

        $discountFraction = $billingProfile?->discountFraction() ?? 0.0;
        $discountAmount = round(($baseAmount + $surchargeAmount) * $discountFraction, 2);

        // Discount applies to freight + surcharges only — insurance is a
        // pass-through cost tied to declared value, not a negotiated rate,
        // so it is never discounted.
        $discountedFreight = ($baseAmount + $surchargeAmount) - $discountAmount;
        $insuranceAmount = $this->calculateInsurance($context);

        $vatPercentage = (float) ($context['vat_percentage'] ?? config('branding.vat_percentage', 0));
        $taxableAmount = $discountedFreight + $insuranceAmount;
        $vatAmount = round($taxableAmount * ($vatPercentage / 100), 2);

        $total = round($taxableAmount + $vatAmount, 2);

        return [
            'base_amount' => round($baseAmount, 2),
            'surcharge_amount' => round($surchargeAmount, 2),
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

    private function calculateInsurance(array $context): float
    {
        if (empty($context['insured']) || empty($context['declared_value'])) {
            return 0.0;
        }

        $rate = (float) ($context['insurance_rate_percentage'] ?? 1); // default 1% of declared value

        return round(((float) $context['declared_value']) * ($rate / 100), 2);
    }
}
