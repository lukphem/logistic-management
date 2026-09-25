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
        private \App\Services\ShipmentCreationService $creationService,
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

        $shipment->load(['scanEvents.handler', 'scanEvents.outlet', 'serviceType', 'originZone', 'destinationZone', 'originCity', 'destinationCity', 'originDistrict', 'destinationDistrict', 'assignedRider', 'currentOutlet', 'currentHub', 'originHub', 'destinationHub', 'clientUser', 'clientAccount', 'apiClient']);

        return view('shipments.show', compact('shipment'));
    }

    /**
     * Deliberately limited to fields that don't touch pricing — sender/
     * receiver contact info, addresses, package description, special
     * instructions, packaging. Weight/dimensions/service type are
     * excluded on purpose: those are exactly what the frozen
     * base_amount/surcharge_amount/total_amount were calculated from,
     * and changing them here without re-running the whole pricing
     * pipeline would silently leave the shipment's price wrong. A
     * shipment that needs re-pricing is a cancel-and-rebook, not an
     * edit.
     *
     * Blocked once a shipment has reached a terminal status — same
     * "delivered"/"returned" list RiderController::assignedOrders()
     * uses for "no longer active" — editing sender/receiver details on
     * a shipment that's already been delivered has no real use and
     * would just quietly rewrite history on the record.
     */
    /**
     * One of three layouts (Classic/Modern/Compact), each adaptive to
     * both thermal sizes (4×6 gets full content, 2×1 drops to
     * essentials only — same template, not a separate file per size,
     * so the three styles can't drift out of sync with each other).
     * Chosen deployment-wide via Settings → Shipping label, never
     * per-shipment — every label a company prints should look
     * consistent.
     *
     * The code payload is just the tracking number either way (QR or
     * 1D barcode, per Settings → Code on label), same value a rider
     * or hub scans in manually, so any generic scanner — not just
     * this app's own scan flow — reads it back correctly.
     *
     * A registered client's own logo (ClientAccount::logo_url) prints
     * alongside the company's own — nothing to configure per
     * shipment, it's just whatever that specific booking account has
     * uploaded, or absent entirely for a walk-in customer.
     */
    /**
     * One of three layouts (Classic/Modern/Compact), each adaptive to
     * both thermal sizes (4×6 gets full content, 2×1 drops to
     * essentials only — same template, not a separate file per size,
     * so the three styles can't drift out of sync with each other).
     * Which DESIGN prints is deployment-wide (Settings → Shipping
     * label), but which SIZE prints is chosen right here, at print
     * time — a hub might genuinely need either size for the same
     * shipment depending on what's loaded in the printer that day, so
     * this isn't locked to Settings' own default the way the design
     * choice is. Settings' value is only the pre-selected starting
     * point when no ?size= is given.
     *
     * The code payload is just the tracking number either way (QR or
     * 1D barcode, per Settings → Code on label), same value a rider
     * or hub scans in manually, so any generic scanner — not just
     * this app's own scan flow — reads it back correctly.
     *
     * A registered client's own logo (ClientAccount::logo_url) prints
     * alongside the company's own — nothing to configure per
     * shipment, it's just whatever that specific booking account has
     * uploaded, or absent entirely for a walk-in customer.
     */
    /**
     * One of three layouts (Classic/Modern/Compact), each adaptive to
     * both thermal sizes (4×6 gets full content, 2×1 drops to
     * essentials only — same template, not a separate file per size,
     * so the three styles can't drift out of sync with each other).
     * Which DESIGN prints is deployment-wide (Settings → Shipping
     * label), but which SIZE prints is chosen right here, at print
     * time — a hub might genuinely need either size for the same
     * shipment depending on what's loaded in the printer that day, so
     * this isn't locked to Settings' own default the way the design
     * choice is. Settings' value is only the pre-selected starting
     * point when no ?size= is given.
     *
     * For a multi-piece shipment (quantity > 1), one label prints per
     * piece — each carries its own code (tracking number + piece
     * suffix, e.g. "LM260913ULRDZ1-2/3"), not just a repeat of the
     * shipment's own tracking number, so a piece that gets separated
     * from the rest of its shipment (lost, mis-routed, opened for
     * inspection) can still be identified as specifically piece 2 of
     * 3, not just "part of shipment X" with no way to tell which
     * part. Each piece gets its own printed page (CSS page-break),
     * one continuous print job rather than requiring a separate trip
     * to this page per piece.
     *
     * A registered client's own logo (ClientAccount::logo_url) prints
     * alongside the company's own — nothing to configure per
     * shipment, it's just whatever that specific booking account has
     * uploaded, or absent entirely for a walk-in customer.
     */
    public function label(Request $request, Shipment $shipment): View
    {
        abort_unless(auth()->user()->canAccessShipment($shipment), 403, "This shipment isn't somewhere you have access to.");
        abort_if($shipment->isPaymentPending(), 403, 'This shipment is awaiting Paystack payment and cannot be printed until it\'s confirmed paid.');

        $shipment->load(['serviceType', 'originCity', 'destinationCity', 'originHub', 'destinationHub', 'clientAccount']);
        $settings = Setting::current();

        $printSize = in_array($request->query('size'), ['4x6', '2x1'], true)
            ? $request->query('size')
            : $settings->waybill_thermal_size;

        $totalPieces = max((int) ($shipment->quantity ?? 1), 1);

        $pieces = [];
        for ($i = 1; $i <= $totalPieces; $i++) {
            $pieceCode = $totalPieces > 1 ? "{$shipment->tracking_number}-{$i}/{$totalPieces}" : $shipment->tracking_number;

            $codeSvg = null;
            if ($settings->waybill_show_qr) {
                $codeSvg = $settings->label_barcode_type === 'barcode'
                    ? (new \Picqer\Barcode\BarcodeGeneratorSVG())->getBarcode($pieceCode, \Picqer\Barcode\BarcodeGeneratorSVG::TYPE_CODE_128)
                    : \SimpleSoftwareIO\QrCode\Facades\QrCode::size(160)->generate($pieceCode);
            }

            $pieces[] = ['number' => $i, 'total' => $totalPieces, 'code' => $pieceCode, 'codeSvg' => $codeSvg];
        }

        $design = in_array($settings->label_design, ['classic', 'modern', 'compact'], true) ? $settings->label_design : 'classic';
        $clientLogoUrl = $shipment->clientAccount?->logo_url;

        // 4×6 and 2×1 are genuinely separate templates per design, not
        // one adaptive template — a 4×6 has room for a proper visual
        // hierarchy (FROM/TO boxes, a details table, a service-type
        // banner); a 2×1 has to be built from the ground up around
        // "receiver + tracking only fits," not a shrunk-down copy of
        // the bigger layout.
        return view("shipments.label.{$design}-{$printSize}", compact('shipment', 'settings', 'clientLogoUrl', 'printSize', 'pieces'));
    }

    /**
     * The comprehensive contract/receipt — legally distinct from the
     * label above, which is only the routing sticker. Full sender/
     * receiver declaration, the complete billing breakdown already
     * shown on the shipment page, and whatever terms & conditions the
     * company has entered (Settings → Waybill document → Terms &
     * conditions) — printed exactly as entered, since the specific
     * wording of a liability/claims clause is a legal decision this
     * system has no business making for anyone.
     */
    /**
     * The comprehensive contract/receipt — legally distinct from the
     * label above, which is only the routing sticker. Full sender/
     * receiver declaration, the complete billing breakdown already
     * shown on the shipment page, and whatever terms & conditions the
     * company has entered (Settings → Waybill document → Terms &
     * conditions) — printed exactly as entered, since the specific
     * wording of a liability/claims clause is a legal decision this
     * system has no business making for anyone.
     *
     * One of three designs (Settings → Waybill document → Waybill
     * design), same deployment-wide-choice pattern as the label — a
     * company issuing Waybills that looked different shipment to
     * shipment would look unprofessional/inconsistent.
     */
    public function waybillDocument(Shipment $shipment): View
    {
        abort_unless(auth()->user()->canAccessShipment($shipment), 403, "This shipment isn't somewhere you have access to.");
        abort_if($shipment->isPaymentPending(), 403, 'This shipment is awaiting Paystack payment and cannot be printed until it\'s confirmed paid.');

        $shipment->load(['serviceType', 'originCity', 'destinationCity', 'originHub', 'destinationHub', 'clientUser', 'clientAccount']);
        $settings = Setting::current();

        $design = in_array($settings->waybill_design, ['classic', 'modern', 'compact'], true) ? $settings->waybill_design : 'classic';

        return view("shipments.waybill.{$design}", compact('shipment', 'settings'));
    }


    /**
     * Deliberately limited to fields that don't touch pricing — sender/
     * receiver contact info, addresses, package description, special
     * instructions, packaging. Weight/dimensions/service type are
     * excluded on purpose: those are exactly what the frozen
     * base_amount/surcharge_amount/total_amount were calculated from,
     * and changing them here without re-running the whole pricing
     * pipeline would silently leave the shipment's price wrong. A
     * shipment that needs re-pricing is a cancel-and-rebook, not an
     * edit.
     *
     * Blocked once a shipment has reached a terminal status — same
     * "delivered"/"returned" list RiderController::assignedOrders()
     * uses for "no longer active" — editing sender/receiver details on
     * a shipment that's already been delivered has no real use and
     * would just quietly rewrite history on the record.
     */
    public function edit(Shipment $shipment): RedirectResponse|View
    {
        abort_unless(auth()->user()->canAccessShipment($shipment), 403, "This shipment isn't somewhere you have access to.");

        if (in_array($shipment->current_status, ['delivered', 'returned'], true)) {
            return redirect()->route('shipments.show', $shipment)->withErrors(['shipment' => 'This shipment has already been delivered/returned and can no longer be edited.']);
        }

        return view('shipments.edit', compact('shipment'));
    }

    /**
     * Cancels, never hard-deletes — a tracking number, once issued,
     * needs to stay resolvable ("Cancelled," not a confusing "not
     * found"), the booking/cancellation pattern is itself useful
     * data, and a wrong cancellation can be undone where a hard
     * delete never could be. Only allowed while a shipment is still
     * exactly 'booked' — the same boundary used everywhere else in
     * this app for "never actually entered the company's
     * possession" — since anything with real scan history, payment
     * collected, or manifest/batch involvement has operational and
     * financial weight that a delete action shouldn't be able to
     * erase.
     */
    public function destroy(Shipment $shipment): RedirectResponse
    {
        abort_unless(auth()->user()->canAccessShipment($shipment), 403, "This shipment isn't somewhere you have access to.");

        if ($shipment->current_status !== 'booked') {
            return redirect()->route('shipments.show', $shipment)->withErrors(['shipment' => "This shipment can't be cancelled — it's already been picked up, dropped off, or otherwise processed."]);
        }

        // Cancelling a shipment that's already been paid for would
        // silently erase the fact that the company is holding real
        // money for something that no longer exists — that has to be
        // resolved as an actual refund first, through the normal
        // finance process, not quietly forgotten by a status change.
        if ($shipment->hasCollectedPayment()) {
            return redirect()->route('shipments.show', $shipment)->withErrors(['shipment' => "This shipment has already been paid for and can't be cancelled directly — the payment needs to be refunded first through the normal finance process."]);
        }

        $shipment->update(['current_status' => 'cancelled']);

        return redirect()->route('shipments.index')->with('status', "Shipment {$shipment->tracking_number} cancelled.");
    }

    /**
     * The other half of destroy()'s guard: once payment has actually
     * been collected, plain cancellation is blocked outright, and
     * this is the only way past that block. There's no in-app refund
     * processing here (no Paystack refund API call, no cash-
     * settlement reversal) — that's a separate, substantial feature
     * of its own. This is the minimal, audit-safe interim: staff
     * explicitly record how the refund was actually handled outside
     * the app (cash handed back, a Paystack refund issued through
     * their dashboard), and only then does the cancellation proceed
     * — so a paid shipment still can't be cancelled with no trace of
     * what happened to the money, it just requires that trace to
     * exist first instead of being silently skipped.
     */
    public function refundAndCancel(Request $request, Shipment $shipment): RedirectResponse
    {
        abort_unless(auth()->user()->canAccessShipment($shipment), 403, "This shipment isn't somewhere you have access to.");

        if ($shipment->current_status !== 'booked') {
            return redirect()->route('shipments.show', $shipment)->withErrors(['shipment' => "This shipment can't be cancelled — it's already been picked up, dropped off, or otherwise processed."]);
        }

        if (! $shipment->hasCollectedPayment()) {
            return redirect()->route('shipments.show', $shipment)->withErrors(['shipment' => "This shipment hasn't been paid for — use the regular Cancel Shipment action instead."]);
        }

        $data = $request->validate([
            'refund_note' => 'required|string|max:255',
        ]);

        $shipment->update([
            'current_status' => 'cancelled',
            'refunded_at' => now(),
            'refunded_by_user_id' => auth()->id(),
            'refund_note' => $data['refund_note'],
        ]);

        return redirect()->route('shipments.index')->with('status', "Shipment {$shipment->tracking_number} cancelled and refund recorded.");
    }

    public function update(Request $request, Shipment $shipment): RedirectResponse
    {
        abort_unless(auth()->user()->canAccessShipment($shipment), 403, "This shipment isn't somewhere you have access to.");

        if (in_array($shipment->current_status, ['delivered', 'returned'], true)) {
            return redirect()->route('shipments.show', $shipment)->withErrors(['shipment' => 'This shipment has already been delivered/returned and can no longer be edited.']);
        }

        $data = $request->validate([
            'sender_name' => self::NAME_RULE,
            'sender_phone' => self::PHONE_RULE,
            'sender_email' => 'nullable|email|max:255',
            'origin_address' => 'required|string|max:150',
            'receiver_name' => self::NAME_RULE,
            'receiver_phone' => self::PHONE_RULE,
            'receiver_alternate_phone' => self::OPTIONAL_PHONE_RULE,
            'receiver_email' => 'nullable|email|max:255',
            'destination_address' => 'required|string|max:150',
            'package_description' => 'required|string|max:225',
            'special_instructions' => 'nullable|string|max:500',
            'carton_size' => 'nullable|in:small,medium,large',
            'quantity' => 'required|integer|min:1|max:200',
        ]);

        $shipment->update($data);

        return redirect()->route('shipments.show', $shipment)->with('status', 'Shipment details updated.');
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
            // Powers the searchable account picker — by number AND by
            // name/client, since staff usually know who they're
            // booking for before they know (or even see) the account's
            // own reference number.
            'accountOptions' => \App\Models\ClientAccount::with('client:id,name')
                ->orderBy('account_name')
                ->get(['id', 'client_user_id', 'account_number', 'account_name']),
            // Gates which payment-method options show at all — an
            // outlet-scoped staff member's own outlet decides whether
            // Cash is even offered here; hub/global staff have no
            // single outlet to check against, so Cash is available by
            // default for them (nothing to restrict it).
            'bookingOutlet' => auth()->user()->outlet_id ? \App\Models\Outlet::find(auth()->user()->outlet_id) : null,
            'paystackEnabled' => Setting::current()->paystack_enabled,
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
            // Gates the COD checkbox — only ever shown for a real,
            // registered account that's had Cash on Delivery
            // explicitly turned on for it (Accounts tab → Account
            // Details → Managerial services), never for a walk-in
            // customer with no account at all.
            'cod_enabled' => (bool) $account->cod_enabled,
            'is_pickup_chargeable' => (bool) $account->is_pickup_chargeable,
            'pickup_charge' => $account->pickup_charge,
            // A credit account is invoiced later, not paid at booking
            // — the payment-method section only makes sense for a
            // walk-in or a non-credit account actually paying now.
            'is_credit_account' => $account->isCreditAccount(),
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

        if ($request->filled('account_number')) {
            $data['account_number'] = $request->input('account_number');
        }

        try {
            $shipment = $this->creationService->createShipment($data);
        } catch (\RuntimeException $e) {
            return redirect()->route('shipments.create')->withErrors(['shipment' => $e->getMessage()])->withInput();
        }

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

        // Same cross-account restriction createShipment() enforces on
        // the regular booking path — this quote-based path has its
        // own separate creation code, so it needs the same check
        // applied explicitly here rather than inheriting it for free.
        if ($resolvedClientAccountId) {
            $resolvedAccountForScopeCheck = \App\Models\ClientAccount::find($resolvedClientAccountId);

            if ($resolvedAccountForScopeCheck && ! auth()->user()->canBookForAccount($resolvedAccountForScopeCheck)) {
                return redirect()->route('shipments.create')->withErrors(['quote_number' => "\"{$resolvedAccountForScopeCheck->account_name}\" belongs to a different outlet — you don't have permission to book shipments against it."])->withInput();
            }
        }

        // Pickup, same reasoning as insurance just above — a booking-time
        // add-on, never part of a quote's frozen context, layered on
        // top rather than recalculated through the whole pipeline.
        $pickupAmount = 0.0;
        if (! empty($data['is_pickup_requested'])) {
            $pickupAmount = $this->pricingService->calculatePickupFee([
                'is_pickup_requested' => true,
                'client_account_id' => $resolvedClientAccountId,
            ]);

            if ($pickupAmount > 0) {
                $vatPercentage = (float) Setting::current()->vat_percentage;
                $vatOnPickup = round($pickupAmount * ($vatPercentage / 100), 2);

                $result['vat_amount'] = round(($result['vat_amount'] ?? 0) + $vatOnPickup, 2);
                $result['total_amount'] = round(($result['total_amount'] ?? 0) + $pickupAmount + $vatOnPickup, 2);
            }
        }

        // Same balance-check-before-anything-is-created rule as the
        // main createShipment() path — an insufficient wallet must
        // never get as far as a half-created shipment or a used-up
        // quote.
        $wallet = null;
        if (($data['payment_method'] ?? null) === 'wallet') {
            $resolvedAccount = $resolvedClientAccountId ? \App\Models\ClientAccount::find($resolvedClientAccountId) : null;
            $wallet = $this->creationService->resolveWallet($data, $resolvedAccount);

            if ($wallet->balance < ($result['total_amount'] ?? 0)) {
                return redirect()->route('shipments.create')->withErrors(['payment_method' => 'Insufficient wallet balance — this wallet has ' . number_format($wallet->balance, 2) . ' but ' . number_format($result['total_amount'] ?? 0, 2) . ' is needed.'])->withInput();
            }
        }

        $shipment = \Illuminate\Support\Facades\DB::transaction(function () use ($data, $context, $result, $resolvedClientUserId, $resolvedClientAccountId, $pickupAmount, $wallet, $quote) {
            $shipment = Shipment::create([
            'client_user_id' => $resolvedClientUserId,
            'client_account_id' => $resolvedClientAccountId,
            'sender_name' => $data['sender_name'],
            'sender_phone' => $data['sender_phone'],
            'sender_email' => $data['sender_email'] ?? null,
            'receiver_name' => $data['receiver_name'],
            'receiver_phone' => $data['receiver_phone'],
            'receiver_alternate_phone' => $data['receiver_alternate_phone'] ?? null,
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
            'is_pickup_requested' => $data['is_pickup_requested'] ?? false,
            'pickup_amount' => $pickupAmount,
            'base_amount' => $result['base_amount'] ?? 0,
            'surcharge_amount' => $result['surcharge_amount'] ?? 0,
            'onforwarding_amount' => $result['onforwarding_amount'] ?? 0,
            'discount_amount' => $result['discount_amount'] ?? 0,
            'insurance_amount' => $result['insurance_amount'] ?? 0,
            'vat_amount' => $result['vat_amount'] ?? 0,
            'total_amount' => $result['total_amount'] ?? 0,
            'promised_delivery_at' => null,
            'transit_days' => $result['transit_days'] ?? null,
            ...$this->resolveCollectionMethod($data, $wallet),
            ]);

            if ($wallet) {
                $wallet->debit(
                    amount: (float) ($result['total_amount'] ?? 0),
                    reference: $shipment->tracking_number,
                    description: 'Shipment payment',
                    recordedByUserId: auth()->id(),
                );
            }

            $quote->update([
                'status' => 'used',
                'used_by_shipment_id' => $shipment->id,
                'used_at' => now(),
            ]);

            return $shipment;
        });

        return redirect()->route('shipments.show', $shipment)->with('status', "Shipment {$shipment->tracking_number} created from quote {$quote->quote_number}.");
    }

    /**
     * Cash chosen at booking means cash was physically handed over
     * right then — collection_method/cash_collected_at set
     * immediately, making the shipment eligible for the Reconciliation
     * page the moment it's created. Paystack chosen (or nothing
     * chosen — e.g. an account-based shipment, where this selector
     * doesn't apply) leaves both null; payment happens later through
     * the existing "Pay with Paystack" button on the shipment page,
     * same as it already did before this feature existed.
     */
    private function resolveCollectionMethod(array $data, ?\App\Models\AccountWallet $wallet = null): array
    {
        if (($data['payment_method'] ?? null) === 'cash') {
            return ['collection_method' => 'cash', 'cash_collected_at' => now()];
        }

        if (($data['payment_method'] ?? null) === 'paystack') {
            return ['collection_method' => 'paystack'];
        }

        if (($data['payment_method'] ?? null) === 'wallet' && $wallet) {
            return ['collection_method' => 'wallet', 'account_wallet_id' => $wallet->id];
        }

        return [];
    }

    /**
     * Shared across sender/receiver name and phone fields, and the
     * two API controllers that validate the same shipment payload —
     * kept as class constants so the three copies can't drift apart
     * the way the address max: rule did before it was added anywhere.
     *
     * PHONE_RULE: digits, with an optional leading +, and optional
     * spaces/hyphens/parens for however someone naturally formats a
     * number (mobile, landline, or +234 international) — 7-15 digits
     * covers the shortest real phone numbers up to full E.164, not
     * tied to one country's specific format. Catches things like
     * "080" (3 characters — not enough digits to be a real number)
     * that a bare "required|string" rule let straight through before.
     */
    private const PHONE_RULE = 'required|string|regex:/^\+?[0-9\s\-()]{7,20}$/';
    private const OPTIONAL_PHONE_RULE = 'nullable|string|regex:/^\+?[0-9\s\-()]{7,20}$/';
    private const NAME_RULE = 'required|string|max:255|regex:/^[\p{L}\s\-\'.]+$/u';

    private function validateShipment(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'quote_number' => 'nullable|string|max:32',
            'service_type_id' => 'required_without:quote_number|nullable|exists:service_types,id',
            'client_user_id' => 'nullable|exists:users,id',
            'account_number' => 'nullable|string|max:255',
            'sender_name' => self::NAME_RULE,
            'sender_phone' => self::PHONE_RULE,
            'sender_email' => 'nullable|email|max:255',
            'origin_address' => 'required|string|max:150',
            'origin_zone_id' => 'nullable|exists:zones,id',
            'origin_city_id' => 'nullable|exists:cities,id',
            'origin_district_id' => 'nullable|exists:districts,id',
            'origin_country_id' => 'nullable|exists:countries,id',
            'origin_state_id' => 'nullable|exists:states,id',
            'origin_hub_id' => 'nullable|exists:hubs,id',
            'destination_hub_id' => 'nullable|exists:hubs,id',
            'receiver_name' => self::NAME_RULE,
            'receiver_phone' => self::PHONE_RULE,
            'receiver_alternate_phone' => self::OPTIONAL_PHONE_RULE,
            'receiver_email' => 'nullable|email|max:255',
            'package_description' => 'required|string|max:225',
            'special_instructions' => 'nullable|string|max:500',
            'destination_address' => 'required|string|max:150',
            'destination_zone_id' => 'nullable|exists:zones,id',
            'destination_city_id' => 'nullable|exists:cities,id',
            'destination_district_id' => 'nullable|exists:districts,id',
            'destination_country_id' => 'nullable|exists:countries,id',
            'destination_state_id' => 'nullable|exists:states,id',
            'distance_km' => 'nullable|numeric|min:0',
            'weight_kg' => 'nullable|numeric|min:0|max:50000',
            'quantity' => 'required|integer|min:1|max:200',
            'carton_size' => 'nullable|in:small,medium,large',
            'length_cm' => 'nullable|numeric|min:0|max:10000',
            'width_cm' => 'nullable|numeric|min:0|max:10000',
            'height_cm' => 'nullable|numeric|min:0|max:10000',
            'additional_service_option_ids' => 'nullable|array',
            'vehicle_type_id' => 'nullable|exists:vehicle_types,id',
            'is_empty_return' => 'sometimes|boolean',
            'is_cod' => 'sometimes|boolean',
            'is_pickup_requested' => 'sometimes|boolean',
            'cod_amount' => 'nullable|numeric|min:0',
            'insured' => 'sometimes|boolean',
            'declared_value' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|in:cash,paystack,wallet,deferred',
            'wallet_source' => 'nullable|in:client,outlet',
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
        $data['is_pickup_requested'] = $request->boolean('is_pickup_requested');

        return $data;
    }
}
