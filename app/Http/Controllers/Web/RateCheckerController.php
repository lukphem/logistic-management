<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Country;
use App\Models\ServiceType;
use App\Models\State;
use App\Models\Zone;
use App\Services\PricingEngine;
use App\Services\PricingUnavailableException;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RateCheckerController extends Controller
{
    public function __construct(private PricingEngine $pricingEngine)
    {
    }

    /**
     * A single form covering whichever billing model the chosen service
     * type actually uses — nothing here is Standard-Billing-specific.
     * PricingEngine::quote() is exactly the same call booking makes, so
     * the checker can never drift out of sync with what a real booking
     * would actually charge, and it automatically supports every future
     * billing model the moment ServiceType.billing_model points a
     * service type at one — no changes needed here.
     */
    public function index(Request $request): View
    {
        $result = null;
        $error = null;

        if ($request->filled('service_type_id') && $request->filled('weight_kg')) {
            try {
                $context = [
                    'service_type_id' => $request->integer('service_type_id'),
                    'weight_kg' => (float) $request->weight_kg,
                    'origin_city_id' => $request->filled('origin_city_id') ? $request->integer('origin_city_id') : null,
                    'destination_city_id' => $request->filled('destination_city_id') ? $request->integer('destination_city_id') : null,
                    'destination_country_id' => $request->filled('destination_country_id') ? $request->integer('destination_country_id') : null,
                ];

                $quote = $this->pricingEngine->quote($context);
                $zone = Zone::find($quote['zone_id']);

                $result = [
                    'base_amount' => $quote['base_amount'],
                    'transit_days' => $quote['transit_days'],
                    'shipping_type' => $quote['shipping_type'],
                    'zone_name' => $zone?->name,
                ];
            } catch (PricingUnavailableException $e) {
                $error = $e->getMessage();
            }
        }

        return view('rate-checker.index', [
            'result' => $result,
            'error' => $error,
            'serviceTypes' => ServiceType::where('is_active', true)->orderBy('name')->get(),
            'states' => State::with('country')->orderBy('name')->get(),
            'cities' => City::with('state')->orderBy('name')->get(),
            'countries' => Country::where('code', '!=', 'NG')->orderBy('name')->get(),
        ]);
    }
}
