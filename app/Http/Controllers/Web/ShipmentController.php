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
use Illuminate\Http\JsonResponse;
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
            'billingModels' => Setting::current()->supportedBillingModels(),
            'serviceTypes' => ServiceType::where('is_active', true)->orderBy('name')->get(),
            'states' => State::with('country')->orderBy('name')->get(),
            'cities' => City::with('state')->orderBy('name')->get(),
            'districts' => District::with('city')->orderBy('name')->get(),
            'countries' => Country::where('code', '!=', 'NG')->orderBy('name')->get(),
            'volumetricDivisor' => Setting::current()->volumetric_divisor,
            'vehicleTypes' => \App\Models\VehicleType::where('is_active', true)->orderBy('name')->get(),
            'additionalServices' => AdditionalService::where('is_active', true)
                ->with(['options' => fn ($q) => $q->where('is_active', true)->orderBy('name')])
                ->orderBy('name')->get()->filter(fn ($s) => $s->options->isNotEmpty()),
            'clients' => User::where('user_type', 'client')->orderBy('name')->get(['id', 'name', 'email']),
        ]);
    }

    /**
     * Live "Check Price" preview, called from the form before submission
     * — same PricingEngine + ShipmentPricingService pipeline as an
     * actual booking, so the number shown here is never a rough
     * estimate that could differ from what create() actually charges.
     * Nothing is persisted (no Quote row, no Shipment) - purely a
     * read of what the current form state would cost right now.
     */
    /**
     * Fetched via JS as soon as an account number is typed on Create
     * Shipment, so the Billing model / Service type dropdowns can be
     * filtered client-side to only what this specific account is
     * actually set up to use — never offering something that would
     * fail (or worse, silently price wrong) at booking time. Same
     * "absence means available" semantics as Billing Setup and Rate
     * Checker's server-side version of this same filtering.
     */
    public function accountBillingOptions(Request $request): JsonResponse
    {
        $account = $request->filled('account_number')
            ? \App\Models\ClientAccount::where('account_number', $request->input('account_number'))->first()
            : null;

        if (! $account) {
            return response()->json(['found' => false]);
        }

        $enabledModels = collect(\App\Models\Setting::current()->supportedBillingModels())
            ->filter(fn ($label, $key) => $account->usesBillingModel($key))
            ->keys()->values();

        $disabledServiceTypeIds = \App\Models\ClientServiceSubscription::where('client_account_id', $account->id)
            ->where('is_active', false)
            ->pluck('service_type_id');

        $allowedServiceTypeIds = \App\Models\ServiceType::where('is_active', true)
            ->whereIn('billing_model', $enabledModels)
            ->whereNotIn('id', $disabledServiceTypeIds)
            ->pluck('id');

        return response()->json([
            'found' => true,
            'account_name' => $account->account_name,
            'client_name' => $account->client?->name,
            'billing_models' => $enabledModels,
            'service_type_ids' => $allowedServiceTypeIds,
        ]);
    }

    public function previewPrice(Request $request): JsonResponse
    {
        if ($request->filled('quote_number')) {
            $quote = Quote::where('quote_number', strtoupper(trim($request->input('quote_number'))))->first();

            if (! $quote || ! $quote->isUsable()) {
                return response()->json(['message' => 'Quote not found or no longer usable.'], 422);
            }

            $result = $quote->result;

            if ($request->boolean('insured') && $request->filled('declared_value')) {
                $insuranceAmount = $this->pricingService->calculateInsurance([
                    'insured' => true,
                    'declared_value' => $request->input('declared_value'),
                ]);
                $vatPercentage = (float) Setting::current()->vat_percentage;
                $vatOnInsurance = round($insuranceAmount * ($vatPercentage / 100), 2);

                $result['insurance_amount'] = round($insuranceAmount, 2);
                $result['vat_amount'] = round(($result['vat_amount'] ?? 0) + $vatOnInsurance, 2);
                $result['total_amount'] = round(($result['total_amount'] ?? 0) + $insuranceAmount + $vatOnInsurance, 2);
            }

            return response()->json(['result' => $result, 'from_quote' => true]);
        }

        $context = [
            'service_type_id' => $request->integer('service_type_id'),
            'client_user_id' => $request->filled('client_user_id') ? $request->integer('client_user_id') : null,
            'client_account_id' => $request->filled('account_number')
                ? \App\Models\ClientAccount::where('account_number', $request->input('account_number'))->value('id')
                : null,
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
            'insured' => $request->boolean('insured'),
            'declared_value' => $request->input('declared_value'),
        ];

        try {
            $quote = $this->pricingEngine->quote($context);
        } catch (PricingUnavailableException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $context['base_amount'] = $quote['base_amount'];
        $context['used_special_rate'] = $quote['used_special_rate'] ?? false;
        $context['surcharges'] = array_merge($context['surcharges'] ?? [], $quote['surcharges'] ?? []);

        $billingProfile = ClientBillingProfile::resolveForClientUser($request->input('client_user_id'));
        $result = [
            ...$this->pricingService->priceShipment($context, $billingProfile),
            'transit_days' => $quote['transit_days'],
            'shipping_type' => $quote['shipping_type'],
            'chargeable_weight_kg' => $quote['chargeable_weight_kg'] ?? null,
            'billed_weight_kg' => $quote['billed_weight_kg'] ?? null,
        ];

        return response()->json(['result' => $result, 'from_quote' => false]);
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

        // A typed account number resolves directly to that specific
        // Account and wins over whatever's in the client dropdown —
        // same reasoning as Rate Checker/Quote generation: a client
        // can have several accounts, each with its own special tariff/
        // discount, and this is the only way to book against a
        // NON-default one without switching to it first on the Client
        // Hub.
        if ($request->filled('account_number')) {
            $account = \App\Models\ClientAccount::where('account_number', $request->input('account_number'))->first();

            if (! $account) {
                return redirect()->route('shipments.create')->withErrors(['account_number' => "No client account found with number \"{$request->input('account_number')}\"."])->withInput();
            }

            $data['client_account_id'] = $account->id;
            $data['client_user_id'] = $account->client_user_id;
        }

        try {
            $quote = $this->pricingEngine->quote($data);
        } catch (PricingUnavailableException $e) {
            // Explicit route, not back() - back() falls through to the
            // site root when it can't resolve a previous URL (missing
            // Referer header, session edge cases), and this app's root
            // route redirects straight to the dashboard - silently
            // swallowing this error message along the way. An explicit
            // destination means the error is never lost regardless of
            // why back() would have failed.
            return redirect()->route('shipments.create')->withErrors(['service_type_id' => $e->getMessage()])->withInput();
        }

        $data['base_amount'] = $quote['base_amount'];
        $data['used_special_rate'] = $quote['used_special_rate'] ?? false;
        // Same Fleet Billing surcharges merge as QuoteController/
        // RateCheckerController - keeps this walk-in path priced
        // identically to what Rate Checker would show for the same
        // inputs, per this page's own guarantee.
        $data['surcharges'] = array_merge($data['surcharges'] ?? [], $quote['surcharges'] ?? []);

        $billingProfile = ClientBillingProfile::resolveForClientUser($data['client_user_id'] ?? null);
        $pricing = $this->pricingService->priceShipment($data, $billingProfile);

        // Same resolution PricingEngine/ShipmentPricingService already
        // used to price this shipment (client_account_id if known,
        // else that client's Default Account) - stamped onto the
        // shipment itself so Shipment -> Account -> Business Manager
        // is traceable later, for commission/performance reporting.
        $data['client_account_id'] = $data['client_account_id']
            ?? (! empty($data['client_user_id']) ? \App\Models\ClientAccount::where('client_user_id', $data['client_user_id'])->where('is_default', true)->value('id') : null);

        // Checked against whichever account this shipment ultimately
        // resolved to, not just the one explicitly typed in — a
        // suspended account shouldn't be able to book through its own
        // Default fallback either.
        if ($data['client_account_id']) {
            $resolvedAccount = \App\Models\ClientAccount::find($data['client_account_id']);

            if ($resolvedAccount?->isSuspended()) {
                return redirect()->route('shipments.create')->withErrors(['account_number' => "\"{$resolvedAccount->account_name}\" is suspended and can't book new shipments." . ($resolvedAccount->suspension_reason ? " Reason: {$resolvedAccount->suspension_reason}" : '')])->withInput();
            }
        }

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
            return redirect()->route('shipments.create')->withErrors(['quote_number' => "No quote found with ID \"{$data['quote_number']}\"."])->withInput();
        }

        if (! $quote->isUsable()) {
            $message = $quote->status === 'used'
                ? 'This quote has already been used to book a shipment.'
                : 'This quote has expired — check the rate again to get a new one.';

            return redirect()->route('shipments.create')->withErrors(['quote_number' => $message])->withInput();
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

        // The quote's OWN frozen context is authoritative for which
        // account the price was actually computed against — not
        // whatever's selected in the booking form's client field.
        // Falls back to that selection only when the quote itself
        // never had an account resolved (no account-number lookup was
        // used when it was generated), so this never contradicts the
        // account whose special tariff/discount actually produced the
        // frozen price being booked.
        $resolvedClientAccountId = $context['client_account_id']
            ?? (! empty($data['client_user_id']) ? \App\Models\ClientAccount::where('client_user_id', $data['client_user_id'])->where('is_default', true)->value('id') : null);
        $resolvedClientUserId = $resolvedClientAccountId
            ? \App\Models\ClientAccount::find($resolvedClientAccountId)?->client_user_id
            : ($data['client_user_id'] ?? null);

        $shipment = Shipment::create([
            'client_user_id' => $resolvedClientUserId,
            'client_account_id' => $resolvedClientAccountId,
            'sender_name' => $data['sender_name'],
            'sender_phone' => $data['sender_phone'],
            'sender_email' => $data['sender_email'] ?? null,
            'receiver_name' => $data['receiver_name'],
            'receiver_phone' => $data['receiver_phone'],
            'receiver_email' => $data['receiver_email'] ?? null,
            'package_description' => $data['package_description'],
            'special_instructions' => $data['special_instructions'] ?? null,
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
            'account_number' => 'nullable|string|max:255',
            'sender_name' => 'required|string|max:255',
            'sender_phone' => 'required|string|max:255',
            'sender_email' => 'nullable|email|max:255',
            'origin_address' => 'required|string',
            'origin_zone_id' => 'nullable|exists:zones,id',
            'origin_city_id' => 'nullable|exists:cities,id',
            'origin_district_id' => 'nullable|exists:districts,id',
            'origin_country_id' => 'nullable|exists:countries,id',
            'origin_state_id' => 'nullable|exists:states,id',
            'origin_hub_id' => 'nullable|exists:hubs,id',
            'destination_hub_id' => 'nullable|exists:hubs,id',
            'receiver_name' => 'required|string|max:255',
            'receiver_phone' => 'required|string|max:255',
            'receiver_email' => 'nullable|email|max:255',
            'package_description' => 'required|string|max:255',
            'special_instructions' => 'nullable|string',
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
            'vehicle_type_id' => 'nullable|exists:vehicle_types,id',
            'is_empty_return' => 'sometimes|boolean',
            'is_cod' => 'sometimes|boolean',
            'cod_amount' => 'nullable|numeric',
            'insured' => 'sometimes|boolean',
            'declared_value' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            // Same explicit-route reasoning as the two back() calls
            // above - never rely on back()'s previous-URL resolution
            // for a page the person needs to actually see the errors on.
            abort(redirect()->route('shipments.create')->withErrors($validator)->withInput());
        }

        $data = $validator->validated();
        $data['is_cod'] = $request->boolean('is_cod');
        $data['insured'] = $request->boolean('insured');
        $data['is_empty_return'] = $request->boolean('is_empty_return');

        return $data;
    }
}
