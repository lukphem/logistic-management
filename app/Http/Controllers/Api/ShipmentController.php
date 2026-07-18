<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClientBillingProfile;
use App\Models\Shipment;
use App\Services\ShipmentPricingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ShipmentController extends Controller
{
    public function __construct(private ShipmentPricingService $pricingService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $query = Shipment::with(['originZone', 'destinationZone', 'assignedRider']);

        if ($request->filled('status')) {
            $query->where('current_status', $request->status);
        }

        if ($request->filled('sla_breached')) {
            $query->where('sla_breached', $request->boolean('sla_breached'));
        }

        return response()->json($query->latest()->paginate($request->integer('per_page', 20)));
    }

    /**
     * Staff-initiated (walk-in) booking — resolves pricing exactly the
     * way ClientShipmentController does. base_amount is currently always
     * 0 — see ShipmentPricingService.
     *
     * client_user_id is optional — a walk-in customer may not have a
     * portal account at all. When it IS provided and that client has a
     * Special billing profile, their discount still applies here, via
     * ClientBillingProfile::resolveForClientUser() rather than
     * resolveForRequest() — the requester here is the STAFF member, not
     * the client, so resolveForRequest() would resolve the wrong person.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $this->validateShipment($request);

        $billingProfile = ClientBillingProfile::resolveForClientUser($data['client_user_id'] ?? null);
        $pricing = $this->pricingService->priceShipment($data, $billingProfile);

        $shipment = Shipment::create([...$data, ...$pricing]);

        return response()->json($shipment, 201);
    }

    public function show(Shipment $shipment): JsonResponse
    {
        return response()->json($shipment->load(['scanEvents', 'originZone', 'destinationZone', 'assignedRider']));
    }

    public function update(Request $request, Shipment $shipment): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'current_status' => 'sometimes|string',
            'assigned_rider_id' => 'sometimes|nullable|exists:users,id',
            'current_hub_id' => 'sometimes|nullable|exists:hubs,id',
            'promised_delivery_at' => 'sometimes|nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $shipment->update($validator->validated());

        if ($request->input('current_status') === 'delivered') {
            $shipment->update(['delivered_at' => now()]);
        }

        return response()->json($shipment);
    }

    public function destroy(Shipment $shipment): JsonResponse
    {
        $shipment->delete();

        return response()->json(['message' => 'Shipment removed']);
    }

    private function validateShipment(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'service_type' => 'required|string',
            'client_user_id' => 'nullable|exists:users,id',
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
            abort(response()->json(['errors' => $validator->errors()], 422));
        }

        return $validator->validated();
    }
}
