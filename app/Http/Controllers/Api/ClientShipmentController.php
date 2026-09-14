<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClientBillingProfile;
use App\Models\Shipment;
use App\Services\PricingEngine;
use App\Services\PricingUnavailableException;
use App\Services\ShipmentPricingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ClientShipmentController extends Controller
{
    public function __construct(
        private ShipmentPricingService $pricingService,
        private PricingEngine $pricingEngine,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $query = Shipment::query();
        $this->scopeToRequester($request, $query);

        return response()->json($query->latest()->paginate($request->integer('per_page', 20)));
    }

    /**
     * Creates a shipment AND resolves its price in one step — this is the
     * endpoint external integrators hit to book a shipment (idempotency
     * key expected via the Idempotency-Key header; enforcement to be added
     * alongside the queue/webhook layer in a later increment).
     *
     * Refuses to book (422, no shipment created) when the route/tariff
     * isn't configured — never books at a guessed or zero price.
     */
    /**
     * Same rules, same reasoning, as Web\ShipmentController's copy —
     * see that file's constants for the phone/name pattern notes.
     */
    private const PHONE_RULE = 'required|string|regex:/^\+?[0-9\s\-()]{7,20}$/';
    private const OPTIONAL_PHONE_RULE = 'nullable|string|regex:/^\+?[0-9\s\-()]{7,20}$/';
    private const NAME_RULE = 'required|string|max:255|regex:/^[\p{L}\s\-\'.]+$/u';

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'service_type_id' => 'required|exists:service_types,id',
            'sender_name' => self::NAME_RULE,
            'sender_phone' => self::PHONE_RULE,
            'sender_email' => 'nullable|email|max:255',
            'receiver_name' => self::NAME_RULE,
            'receiver_phone' => self::PHONE_RULE,
            'receiver_alternate_phone' => self::OPTIONAL_PHONE_RULE,
            'receiver_email' => 'nullable|email|max:255',
            'package_description' => 'required|string|max:1000',
            'special_instructions' => 'nullable|string|max:2000',
            'origin_address' => 'required|string|max:2000',
            'origin_zone_id' => 'nullable|exists:zones,id',
            'origin_city_id' => 'nullable|exists:cities,id',
            'origin_district_id' => 'nullable|exists:districts,id',
            'origin_country_id' => 'nullable|exists:countries,id',
            'origin_hub_id' => 'nullable|exists:hubs,id',
            'destination_hub_id' => 'nullable|exists:hubs,id',
            'destination_address' => 'required|string|max:2000',
            'destination_zone_id' => 'nullable|exists:zones,id',
            'destination_city_id' => 'nullable|exists:cities,id',
            'destination_district_id' => 'nullable|exists:districts,id',
            'destination_country_id' => 'nullable|exists:countries,id',
            'distance_km' => 'nullable|numeric|min:0',
            'weight_kg' => 'nullable|numeric|min:0|max:50000',
            'quantity' => 'required|integer|min:1|max:200',
            'carton_size' => 'nullable|in:small,medium,large',
            'length_cm' => 'nullable|numeric|min:0|max:10000',
            'width_cm' => 'nullable|numeric|min:0|max:10000',
            'height_cm' => 'nullable|numeric|min:0|max:10000',
            'is_cod' => 'sometimes|boolean',
            'is_pickup_requested' => 'sometimes|boolean',
            'cod_amount' => 'nullable|numeric|min:0',
            'insured' => 'sometimes|boolean',
            'declared_value' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        // Same reasoning as Api\ClientController::quote() - a logged-in
        // client gets their own special tariff applied automatically.
        // For a pure API-key request (no session user at all), resolve
        // both from the api_client the CheckIpWhitelist middleware
        // already matched instead — its client_account_id is what lets
        // pricing correctly reach this specific account's own rates.
        $apiClient = $request->attributes->get('api_client');
        $data['client_user_id'] = $request->user()?->id ?? $apiClient?->client_user_id;
        $data['client_account_id'] = $apiClient?->client_account_id
            ?? (! empty($data['client_user_id']) ? \App\Models\ClientAccount::where('client_user_id', $data['client_user_id'])->where('is_default', true)->value('id') : null);

        if ($data['client_account_id']) {
            $resolvedAccount = \App\Models\ClientAccount::find($data['client_account_id']);

            if ($resolvedAccount?->isSuspended()) {
                return response()->json(['message' => "This account is suspended and can't book new shipments."], 403);
            }
        }

        try {
            $quote = $this->pricingEngine->quote($data);
        } catch (PricingUnavailableException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $data['base_amount'] = $quote['base_amount'];
        $pricing = $this->pricingService->priceShipment($data, ClientBillingProfile::resolveForRequest($request));

        $shipment = Shipment::create([
            ...$data,
            'client_user_id' => $data['client_user_id'],
            'api_client_id' => $apiClient?->id,
            // A shipment booked through a Test-mode key is real enough
            // to exercise the integration end-to-end, but is never a
            // real, billable, operational shipment — see the
            // migration's note on everywhere that distinction matters.
            'is_test' => $apiClient?->isTestMode() ?? false,
            'shipping_type' => $quote['shipping_type'],
            'promised_delivery_at' => $quote['transit_days'] ? now()->addDays($quote['transit_days']) : null,
            ...$pricing,
        ]);

        return response()->json($shipment, 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $query = Shipment::where('id', $id);
        $this->scopeToRequester($request, $query);

        return response()->json($query->firstOrFail());
    }

    public function track(Request $request, int $id): JsonResponse
    {
        $query = Shipment::with('scanEvents')->where('id', $id);
        $this->scopeToRequester($request, $query);

        $shipment = $query->firstOrFail();

        return response()->json([
            'tracking_number' => $shipment->tracking_number,
            'current_status' => $shipment->current_status,
            'sla_breached' => $shipment->sla_breached,
            'history' => $shipment->scanEvents,
        ]);
    }

    public function cancel(Request $request, int $id): JsonResponse
    {
        $query = Shipment::where('id', $id);
        $this->scopeToRequester($request, $query);

        $shipment = $query->firstOrFail();

        if (in_array($shipment->current_status, ['delivered', 'out_for_delivery'])) {
            return response()->json(['message' => 'Shipment is too far along to cancel'], 422);
        }

        $shipment->update(['current_status' => 'cancelled']);

        return response()->json($shipment);
    }

    /**
     * Restricts the query to the requesting client — a client-portal user
     * (JWT) or an external api_client (API key, set by CheckIpWhitelist
     * middleware) — so no client can see another's shipments.
     */
    private function scopeToRequester(Request $request, $query): void
    {
        if ($request->user()) {
            $query->where('client_user_id', $request->user()->id);
        } elseif ($apiClient = $request->attributes->get('api_client')) {
            $query->where('api_client_id', $apiClient->id);
        } else {
            $query->whereRaw('1 = 0'); // no identifiable requester — return nothing
        }
    }
}
