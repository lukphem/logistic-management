<?php

namespace App\Services;

/**
 * Thrown whenever a shipment genuinely can't be priced — no billing
 * model assigned to the service type, no zone mapping for the route, or
 * no tariff configured for the weight/service combination. Per the
 * Standard Billing spec: "the shipment should not be rated, and the
 * user should receive an error indicating that the route or tariff has
 * not been configured" — never silently price at 0 or guess.
 */
class PricingUnavailableException extends \RuntimeException
{
}
