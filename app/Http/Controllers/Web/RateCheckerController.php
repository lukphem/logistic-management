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
        $resolvedAccount = null;

        // A typed account number resolves directly to that specific
        // Account — not just "this client's Default Account" the way
        // picking a client from a list would. A client can have
        // several accounts now (Lagos, Abuja, E-commerce...), each
        // with its own special tariff/discount; this is the only way
        // to check a NON-default one's rate without switching to it
        // first on the Client Hub.
        if ($request->filled('account_number')) {
            $resolvedAccount = \App\Models\ClientAccount::where('account_number', $request->input('account_number'))
                ->with('client')
                ->first();

            if (! $resolvedAccount) {
                $error = "No client account found with number \"{$request->input('account_number')}\".";
            }
        }

        if ($request->filled('service_type_id') && $request->filled('weight_kg') && ! $error) {
            try {
                $serviceTypeId = $request->integer('service_type_id');

                $context = [
                    'service_type_id' => $serviceTypeId,
                    'client_account_id' => $resolvedAccount?->id,
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
                    'vehicle_type_id' => $request->filled('vehicle_type_id') ? $request->integer('vehicle_type_id') : null,
                    'is_empty_return' => $request->boolean('is_empty_return'),
                    'additional_service_option_ids' => $request->input('additional_service_option_ids', []),
                ];

                $quote = $this->pricingEngine->quote($context);
                $context['base_amount'] = $quote['base_amount'];
                // Some billing models (currently only Fleet Billing)
                // compute their own labeled surcharges — fuel, empty
                // return — as part of resolving the quote itself, since
                // they need the tariff to compute the amounts.
                // ShipmentPricingService's surcharge mechanism was
                // already generic (Increment ?, calculateSurcharges())
                // but nothing populated it until now.
                $context['surcharges'] = array_merge($context['surcharges'] ?? [], $quote['surcharges'] ?? []);

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
                    'account_name' => $resolvedAccount?->account_name,
                    'account_client_name' => $resolvedAccount?->client?->name,
                ];
            } catch (PricingUnavailableException $e) {
                $error = $e->getMessage();
            }
        }

        // Once an account is identified, never offer a billing model or
        // service type it isn't actually set up to use — this is what
        // prevents a rate being checked (or a shipment later booked)
        // against something that would fail or trigger a billing
        // dispute at invoicing time. Absence of a subscription row
        // means "available by default", matching the exact same
        // semantics the Billing Setup tab already uses.
        $billingModels = Setting::current()->supportedBillingModels();
        $serviceTypes = ServiceType::where('is_active', true)->orderBy('name')->get();

        if ($resolvedAccount) {
            $billingModels = collect($billingModels)->filter(fn ($label, $key) => $resolvedAccount->usesBillingModel($key))->all();

            $disabledServiceTypeIds = \App\Models\ClientServiceSubscription::where('client_account_id', $resolvedAccount->id)
                ->where('is_active', false)
                ->pluck('service_type_id');

            $serviceTypes = $serviceTypes
                ->filter(fn ($st) => $resolvedAccount->usesBillingModel($st->billing_model))
                ->reject(fn ($st) => $disabledServiceTypeIds->contains($st->id))
                ->values();
        }

        return view('rate-checker.index', [
            'result' => $result,
            'error' => $error,
            'resolvedAccount' => $resolvedAccount,
            'billingModels' => $billingModels,
            'serviceTypes' => $serviceTypes,
            'states' => State::with('country')->orderBy('name')->get(),
            'cities' => City::with('state')->orderBy('name')->get(),
            'districts' => District::with('city')->orderBy('name')->get(),
            'countries' => Country::where('code', '!=', 'NG')->orderBy('name')->get(),
            'volumetricDivisor' => Setting::current()->volumetric_divisor,
            'vehicleTypes' => \App\Models\VehicleType::where('is_active', true)->orderBy('name')->get(),
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
