<?php

namespace App\Services;

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
     */
    public function priceShipment(RateCard $rateCard, array $context): array
    {
        $baseAmount = $this->rateEngine->calculateBase($rateCard, $context);

        $surchargeAmount = $this->calculateSurcharges($context);
        $insuranceAmount = $this->calculateInsurance($context);
        $vatPercentage = (float) ($context['vat_percentage'] ?? config('branding.vat_percentage', 0));

        $taxableAmount = $baseAmount + $surchargeAmount + $insuranceAmount;
        $vatAmount = round($taxableAmount * ($vatPercentage / 100), 2);

        $total = round($taxableAmount + $vatAmount, 2);

        return [
            'base_amount' => round($baseAmount, 2),
            'surcharge_amount' => round($surchargeAmount, 2),
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
