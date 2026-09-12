<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Quote;
use App\Models\Setting;
use App\Services\PricingEngine;
use App\Services\PricingUnavailableException;
use App\Services\ShipmentPricingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuoteController extends Controller
{
    public function __construct(
        private PricingEngine $pricingEngine,
        private ShipmentPricingService $pricingService,
    ) {
    }

    /**
     * Called from the Rate Checker's "Generate Quote ID" button, once a
     * rate has already been checked — re-runs the exact same pricing
     * pipeline server-side (never trusts a price the browser sends back)
     * and freezes the result into a Quote row. context is stored as
     * given so Create Shipment can later repopulate the same form
     * fields; result is stored so booking against this quote later never
     * needs to touch PricingEngine again — the price is locked the
     * moment the quote is generated, not at booking time.
     */
    public function store(Request $request): JsonResponse
    {
        $context = $this->contextFromRequest($request);

        try {
            $quote = $this->pricingEngine->quote($context);
        } catch (PricingUnavailableException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $context['base_amount'] = $quote['base_amount'];
        // Fleet Billing computes its own labeled surcharges (fuel, empty
        // return) as part of resolving the quote itself, since it needs
        // the tariff to compute the amounts — merged in the same way
        // RateCheckerController does, so a quote generated here prices
        // identically to what Rate Checker would have shown.
        $context['surcharges'] = array_merge($context['surcharges'] ?? [], $quote['surcharges'] ?? []);
        $breakdown = $this->pricingService->priceShipment($context);

        $result = [
            ...$breakdown,
            'transit_days' => $quote['transit_days'],
            'shipping_type' => $quote['shipping_type'],
            'zone_id' => $quote['zone_id'],
            'chargeable_weight_kg' => $quote['chargeable_weight_kg'] ?? null,
            'billed_weight_kg' => $quote['billed_weight_kg'] ?? null,
        ];

        $validityDays = max(1, (int) Setting::current()->quote_validity_days);

        $record = Quote::create([
            'quote_number' => Quote::generateNumber(),
            'service_type_id' => $context['service_type_id'],
            'context' => $context,
            'result' => $result,
            'created_by' => $request->user()->id,
            'status' => 'active',
            'expires_at' => now()->addDays($validityDays),
        ]);

        return response()->json([
            'quote_number' => $record->quote_number,
            'expires_at' => $record->expires_at->toIso8601String(),
            'expires_at_human' => $record->expires_at->format('j M Y, g:ia'),
        ], 201);
    }

    /**
     * Looked up by the Create Shipment page when a quote ID is entered —
     * returns exactly what's needed to repopulate that form (context)
     * and show the locked-in price (result), or a 422 explaining why the
     * quote can't be used (expired, already used, or simply doesn't
     * exist) rather than a bare 404 that leaves staff guessing.
     */
    public function show(string $quoteNumber): JsonResponse
    {
        $quote = Quote::where('quote_number', strtoupper(trim($quoteNumber)))->first();

        if (! $quote) {
            return response()->json(['message' => "No quote found with ID \"{$quoteNumber}\"."], 404);
        }

        if ($quote->status === 'used') {
            return response()->json(['message' => 'This quote has already been used to book a shipment.'], 422);
        }

        if ($quote->isExpired()) {
            if ($quote->status !== 'expired') {
                $quote->update(['status' => 'expired']);
            }

            return response()->json(['message' => 'This quote expired on ' . $quote->expires_at->format('j M Y, g:ia') . '.'], 422);
        }

        return response()->json([
            'quote_number' => $quote->quote_number,
            'expires_at' => $quote->expires_at->toIso8601String(),
            'context' => $quote->context,
            'result' => $quote->result,
        ]);
    }

    private function contextFromRequest(Request $request): array
    {
        $account = $request->filled('account_number')
            ? \App\Models\ClientAccount::where('account_number', $request->input('account_number'))->first()
            : null;

        return [
            'service_type_id' => $request->integer('service_type_id'),
            'client_account_id' => $account?->id,
            'weight_kg' => (float) $request->input('weight_kg'),
            'length_cm' => $request->filled('length_cm') ? (float) $request->input('length_cm') : null,
            'width_cm' => $request->filled('width_cm') ? (float) $request->input('width_cm') : null,
            'height_cm' => $request->filled('height_cm') ? (float) $request->input('height_cm') : null,
            'origin_state_id' => $request->filled('origin_state_id') ? $request->integer('origin_state_id') : null,
            'destination_state_id' => $request->filled('destination_state_id') ? $request->integer('destination_state_id') : null,
            'origin_city_id' => $request->filled('origin_city_id') ? $request->integer('origin_city_id') : null,
            'destination_city_id' => $request->filled('destination_city_id') ? $request->integer('destination_city_id') : null,
            'origin_district_id' => $request->filled('origin_district_id') ? $request->integer('origin_district_id') : null,
            'destination_district_id' => $request->filled('destination_district_id') ? $request->integer('destination_district_id') : null,
            'origin_country_id' => $request->filled('origin_country_id') ? $request->integer('origin_country_id') : null,
            'destination_country_id' => $request->filled('destination_country_id') ? $request->integer('destination_country_id') : null,
            'additional_service_option_ids' => $request->input('additional_service_option_ids', []),
            'vehicle_type_id' => $request->filled('vehicle_type_id') ? $request->integer('vehicle_type_id') : null,
            'is_empty_return' => $request->boolean('is_empty_return'),
        ];
    }
}
