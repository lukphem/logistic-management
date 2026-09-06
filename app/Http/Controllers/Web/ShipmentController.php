<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AdditionalService;
use App\Models\City;
use App\Models\ClientBillingProfile;
use App\Models\Country;
use App\Models\District;
use App\Models\Quote;
use App\Models\ServiceType;
use App\Models\Setting;
use App\Models\Shipment;
use App\Models\State;
use App\Models\User;
use App\Services\PricingEngine;
use App\Services\PricingUnavailableException;
use App\Services\ShipmentPricingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class ShipmentController extends Controller
{
    public function __construct(
        private PricingEngine $pricingEngine,
        private ShipmentPricingService $pricingService,
    ) {
    }

    public function index(Request $request): View
    {
        $query = Shipment::with(['serviceType', 'originZone', 'destinationZone', 'originCity', 'destinationCity', 'assignedRider', 'currentOutlet']);

        $user = auth()->user();

        // Outlet-scoped users see shipments physically at their outlet
        // specifically (current_outlet_id) — a narrower check than the
        // hub-level accessibleHubIds() filter everyone else uses. Skipped
        // entirely for global users so their query never carries an
        // unnecessary WHERE for no reason.
        if ($user->hasOutletAccess()) {
            $query->where('current_outlet_id', $user->outlet_id);
        } elseif (! $user->hasGlobalAccess()) {
            $query->whereIn('current_hub_id', $user->accessibleHubIds());
        }

        if ($request->filled('status')) {
            $query->where('current_status', $request->status);
        }

        if ($request->filled('search')) {
            $query->where('tracking_number', 'like', "%{$request->search}%");
        }

        $shipments = $query->latest()->paginate(15)->withQueryString();

        return view('shipments.index', compact('shipments'));
    }

    public function show(Shipment $shipment): View
    {
        abort_unless(auth()->user()->canAccessShipment($shipment), 403, "This shipment isn't somewhere you have access to.");

        $shipment->load(['scanEvents.handler', 'scanEvents.outlet', 'serviceType', 'originZone', 'destinationZone', 'originCity', 'destinationCity', 'assignedRider', 'currentOutlet', 'originHub', 'destinationHub']);

        return view('shipments.show', compact('shipment'));
    }

    /**
     * Same field set and cascading Route -> Type -> Service Type flow as
     * the Rate Checker (resources/views/rate-checker/index.blade.php) —
     * the two share the exact same JS and field names on purpose, so a
     * quote ID looked up here can drop its `context` straight into these
     * fields with no translation step.
     */
    public function create(): View
    {
        return view('shipments.create', [
            'billingModels' => Setting::BILLING_MODELS,
            'serviceTypes' => ServiceType::where('is_active', true)->orderBy('name')->get(),
            'states' => State::with('country')->orderBy('name')->get(),
            'cities' => City::with('state')->orderBy('name')->get(),
            'districts' => District::with('city')->orderBy('name')->get(),
            'countries' => Country::where('code', '!=', 'NG')->orderBy('name')->get(),
            'volumetricDivisor' => Setting::current()->volumetric_divisor,
            'additionalServices' => AdditionalService::where('is_active', true)
                ->with(['options' => fn ($q) => $q->where('is_active', true)->orderBy('name')])
                ->orderBy('name')->get()->filter(fn ($s) => $s->options->isNotEmpty()),
            'clients' => User::where('user_type', 'client')->orderBy('name')->get(['id', 'name', 'email']),
        ]);
    }

    /**
     * Two paths in here:
     *
     *  - quote_number given: the price is whatever that Quote already
     *    froze at generation time (QuoteController::store) — never
     *    recalculated, that's the whole point of a quote. Insurance is
     *    the one exception, since declared_value is entered here, at
     *    booking time, never part of a Rate Checker quote.
     *  - no quote_number: prices fresh, exactly like the API's walk-in
     *    booking path (Api\ShipmentController::store) — same
     *    PricingEngine + ShipmentPricingService call, so this can never
     *    drift from what Rate Checker would have shown for the same
     *    inputs.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateShipment($request);

        if (! empty($data['quote_number'])) {
            return $this->storeFromQuote($request, $data);
        }

        try {
            $quote = $this->pricingEngine->quote($data);
        } catch (PricingUnavailableException $e) {
            return back()->withErrors(['service_type_id' => $e->getMessage()])->withInput();
        }

        $data['base_amount'] = $quote['base_amount'];

        $billingProfile = ClientBillingProfile::resolveForClientUser($data['client_user_id'] ?? null);
        $pricing = $this->pricingService->priceShipment($data, $billingProfile);

        $shipment = Shipment::create([
            ...$data,
            'shipping_type' => $quote['shipping_type'],
            'promised_delivery_at' => $quote['transit_days'] ? now()->addDays($quote['transit_days']) : null,
            ...$pricing,
        ]);

        return redirect()->route('shipments.show', $shipment)->with('status', "Shipment {$shipment->tracking_number} created.");
    }

    private function storeFromQuote(Request $request, array $data): RedirectResponse
    {
        $quote = Quote::where('quote_number', strtoupper(trim($data['quote_number'])))->first();

        if (! $quote) {
            return back()->withErrors(['quote_number' => "No quote found with ID \"{$data['quote_number']}\"."])->withInput();
        }

        if (! $quote->isUsable()) {
            $message = $quote->status === 'used'
                ? 'This quote has already been used to book a shipment.'
                : 'This quote has expired — check the rate again to get a new one.';

            return back()->withErrors(['quote_number' => $message])->withInput();
        }

        $context = $quote->context;
        $result = $quote->result;

        // Insurance is entered here, at booking time — never part of a
        // Rate Checker quote's frozen context — so it's layered on top
        // of the already-frozen freight/surcharge/VAT rather than
        // re-running the whole pricing pipeline.
        if (! empty($data['insured']) && ! empty($data['declared_value'])) {
            $insuranceAmount = $this->pricingService->calculateInsurance([
                'insured' => true,
                'declared_value' => $data['declared_value'],
            ]);
            $vatPercentage = (float) Setting::current()->vat_percentage;
            $vatOnInsurance = round($insuranceAmount * ($vatPercentage / 100), 2);

            $result['insurance_amount'] = round($insuranceAmount, 2);
            $result['vat_amount'] = round(($result['vat_amount'] ?? 0) + $vatOnInsurance, 2);
            $result['total_amount'] = round(($result['total_amount'] ?? 0) + $insuranceAmount + $vatOnInsurance, 2);
        }

        $shipment = Shipment::create([
            'client_user_id' => $data['client_user_id'] ?? null,
            'service_type_id' => $context['service_type_id'] ?? null,
            'shipping_type' => $result['shipping_type'] ?? null,
            'origin_address' => $data['origin_address'],
            'origin_city_id' => $context['origin_city_id'] ?? null,
            'origin_district_id' => $context['origin_district_id'] ?? null,
            'destination_address' => $data['destination_address'],
            'destination_city_id' => $context['destination_city_id'] ?? null,
            'destination_district_id' => $context['destination_district_id'] ?? null,
            'weight_kg' => $context['weight_kg'] ?? null,
            'length_cm' => $context['length_cm'] ?? null,
            'width_cm' => $context['width_cm'] ?? null,
            'height_cm' => $context['height_cm'] ?? null,
            'chargeable_weight_kg' => $result['chargeable_weight_kg'] ?? null,
            'quantity' => $data['quantity'] ?? null,
            'carton_size' => $data['carton_size'] ?? null,
            'is_cod' => $data['is_cod'] ?? false,
            'cod_amount' => $data['cod_amount'] ?? 0,
            'base_amount' => $result['base_amount'] ?? 0,
            'surcharge_amount' => $result['surcharge_amount'] ?? 0,
            'onforwarding_amount' => $result['onforwarding_amount'] ?? 0,
            'discount_amount' => $result['discount_amount'] ?? 0,
            'insurance_amount' => $result['insurance_amount'] ?? 0,
            'vat_amount' => $result['vat_amount'] ?? 0,
            'total_amount' => $result['total_amount'] ?? 0,
            'promised_delivery_at' => ($result['transit_days'] ?? null) ? now()->addDays($result['transit_days']) : null,
        ]);

        $quote->update([
            'status' => 'used',
            'used_by_shipment_id' => $shipment->id,
            'used_at' => now(),
        ]);

        return redirect()->route('shipments.show', $shipment)->with('status', "Shipment {$shipment->tracking_number} created from quote {$quote->quote_number}.");
    }

    private function validateShipment(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'quote_number' => 'nullable|string|max:32',
            'service_type_id' => 'required_without:quote_number|nullable|exists:service_types,id',
            'client_user_id' => 'nullable|exists:users,id',
            'origin_address' => 'required|string',
            'origin_zone_id' => 'nullable|exists:zones,id',
            'origin_city_id' => 'nullable|exists:cities,id',
            'origin_district_id' => 'nullable|exists:districts,id',
            'origin_country_id' => 'nullable|exists:countries,id',
            'origin_state_id' => 'nullable|exists:states,id',
            'origin_hub_id' => 'nullable|exists:hubs,id',
            'destination_hub_id' => 'nullable|exists:hubs,id',
            'destination_address' => 'required|string',
            'destination_zone_id' => 'nullable|exists:zones,id',
            'destination_city_id' => 'nullable|exists:cities,id',
            'destination_district_id' => 'nullable|exists:districts,id',
            'destination_country_id' => 'nullable|exists:countries,id',
            'destination_state_id' => 'nullable|exists:states,id',
            'distance_km' => 'nullable|numeric',
            'weight_kg' => 'nullable|numeric',
            'quantity' => 'nullable|integer|min:1',
            'carton_size' => 'nullable|in:small,medium,large',
            'length_cm' => 'nullable|numeric',
            'width_cm' => 'nullable|numeric',
            'height_cm' => 'nullable|numeric',
            'additional_service_option_ids' => 'nullable|array',
            'is_cod' => 'sometimes|boolean',
            'cod_amount' => 'nullable|numeric',
            'insured' => 'sometimes|boolean',
            'declared_value' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            abort(back()->withErrors($validator)->withInput());
        }

        $data = $validator->validated();
        $data['is_cod'] = $request->boolean('is_cod');
        $data['insured'] = $request->boolean('insured');

        return $data;
    }
}
