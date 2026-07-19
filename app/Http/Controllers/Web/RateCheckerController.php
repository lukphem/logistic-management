<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AdditionalService;
use App\Models\City;
use App\Models\Country;
use App\Models\District;
use App\Models\ServiceType;
use App\Models\Setting;
use App\Models\State;
use App\Models\Zone;
use App\Services\PricingEngine;
use App\Services\PricingUnavailableException;
use App\Services\ShipmentPricingService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RateCheckerController extends Controller
{
    public function __construct(
        private PricingEngine $pricingEngine,
        private ShipmentPricingService $pricingService,
    ) {
    }

    /**
     * The rest of the form only appears once a Billing Model is picked —
     * different models will need different fields entirely (Standard
     * Billing needs origin/destination/weight; a future flat-rate or
     * contract model might need none of that), so there's nothing
     * sensible to show before that choice is made. Currently only
     * 'standard_billing' is implemented; picking any other shows a
     * plain "not built yet" message instead of a form that can't work.
     *
     * PricingEngine::quote() is exactly the same call booking makes, so
     * the checker can never drift out of sync with what a real booking
     * would actually charge. Runs the FULL pricing pipeline (base ->
     * surcharge -> onforwarding -> additional services -> insurance ->
     * VAT), not just the base freight.
     */
    public function index(Request $request): View
    {
        $result = null;
        $error = null;

        if ($request->filled('service_type_id') && $request->filled('weight_kg')) {
            try {
                $serviceTypeId = $request->integer('service_type_id');

                $context = [
                    'service_type_id' => $serviceTypeId,
                    'weight_kg' => (float) $request->weight_kg,
                    'length_cm' => $request->filled('length_cm') ? (float) $request->length_cm : null,
                    'width_cm' => $request->filled('width_cm') ? (float) $request->width_cm : null,
                    'height_cm' => $request->filled('height_cm') ? (float) $request->height_cm : null,
                    'origin_state_id' => $request->filled('origin_state_id') ? $request->integer('origin_state_id') : null,
                    'destination_state_id' => $request->filled('destination_state_id') ? $request->integer('destination_state_id') : null,
                    'origin_city_id' => $request->filled('origin_city_id') ? $request->integer('origin_city_id') : null,
                    'destination_city_id' => $request->filled('destination_city_id') ? $request->integer('destination_city_id') : null,
                    'origin_district_id' => $request->filled('origin_district_id') ? $request->integer('origin_district_id') : null,
                    'destination_district_id' => $request->filled('destination_district_id') ? $request->integer('destination_district_id') : null,
                    'origin_country_id' => $request->filled('origin_country_id') ? $request->integer('origin_country_id') : null,
                    'destination_country_id' => $request->filled('destination_country_id') ? $request->integer('destination_country_id') : null,
                    'additional_service_option_ids' => $request->input('additional_service_option_ids', []),
                ];

                $quote = $this->pricingEngine->quote($context);
                $context['base_amount'] = $quote['base_amount'];

                $breakdown = $this->pricingService->priceShipment($context);
                $zone = Zone::find($quote['zone_id']);

                $result = [
                    ...$breakdown,
                    'transit_days' => $quote['transit_days'],
                    'shipping_type' => $quote['shipping_type'],
                    'billed_weight_kg' => $quote['billed_weight_kg'] ?? null,
                    'chargeable_weight_kg' => $quote['chargeable_weight_kg'] ?? null,
                    'zone_name' => $zone?->name,
                    // Echoed back so the quote is self-contained — what
                    // was actually asked for, next to what it costs.
                    'service_type_name' => ServiceType::find($serviceTypeId)?->name,
                    'origin_label' => $request->filled('origin_country_id')
                        ? Country::find($request->origin_country_id)?->name
                        : $this->locationLabel($request->origin_state_id ?? null, $request->origin_city_id ?? null),
                    'destination_label' => $request->filled('destination_country_id')
                        ? Country::find($request->destination_country_id)?->name
                        : $this->locationLabel($request->destination_state_id ?? null, $request->destination_city_id ?? null),
                    'weight_kg' => (float) $request->weight_kg,
                ];
            } catch (PricingUnavailableException $e) {
                $error = $e->getMessage();
            }
        }

        return view('rate-checker.index', [
            'result' => $result,
            'error' => $error,
            'billingModels' => Setting::BILLING_MODELS,
            'serviceTypes' => ServiceType::where('is_active', true)->orderBy('name')->get(),
            'states' => State::with('country')->orderBy('name')->get(),
            'cities' => City::with('state')->orderBy('name')->get(),
            'districts' => District::with('city')->orderBy('name')->get(),
            'countries' => Country::where('code', '!=', 'NG')->orderBy('name')->get(),
            'additionalServices' => AdditionalService::where('is_active', true)
                ->with(['options' => fn ($q) => $q->where('is_active', true)->orderBy('name')])
                ->orderBy('name')->get()->filter(fn ($s) => $s->options->isNotEmpty()),
        ]);
    }

    private function locationLabel(?string $stateId, ?string $cityId): ?string
    {
        $city = $cityId ? City::find($cityId) : null;

        if ($city) {
            return "{$city->name}, {$city->state->name}";
        }

        return $stateId ? State::find($stateId)?->name : null;
    }
}
