<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClientBillingProfile;
use App\Models\Shipment;
use App\Services\ShipmentPricingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ClientShipmentController extends Controller
{
    public function __construct(private ShipmentPricingService $pricingService)
    {
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
     * base_amount is currently always 0 — see ShipmentPricingService.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'service_type' => 'required|string',
            'origin_address' => 'required|string',
            'origin_zone_id' => 'nullable|exists:zones,id',
            'origin_city_id' => 'nullable|exists:cities,id',
            'origin_district_id' => 'nullable|exists:districts,id',
            'origin_hub_id' => 'nullable|exists:hubs,id',
            'destination_hub_id' => 'nullable|exists:hubs,id',
            'destination_address' => 'required|string',
            'destination_zone_id' => 'nullable|exists:zones,id',
            'destination_city_id' => 'nullable|exists:cities,id',
            'destination_district_id' => 'nullable|exists:districts,id',
            'distance_km' => 'nullable|numeric',
            'weight_kg' => 'nullable|numeric',
            'quantity' => 'nullable|integer|min:1',
            'carton_size' => 'nullable|in:small,medium,large',
            'length_cm' => 'nullable|numeric',
            'width_cm' => 'nullable|numeric',
            'height_cm' => 'nullable|numeric',
            'is_cod' => 'sometimes|boolean',
            'cod_amount' => 'nullable|numeric',
            'insured' => 'sometimes|boolean',
            'declared_value' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        $pricing = $this->pricingService->priceShipment($data, ClientBillingProfile::resolveForRequest($request));

        $shipment = Shipment::create([
            ...$data,
            'client_user_id' => $request->user()?->id,
            'api_client_id' => $request->attributes->get('api_client')?->id,
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
