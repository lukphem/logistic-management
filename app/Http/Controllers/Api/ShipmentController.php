<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ShipmentController extends Controller
{
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

    public function store(Request $request): JsonResponse
    {
        // Staff-initiated (walk-in) booking. Client-originated bookings go
        // through ClientShipmentController, which also resolves pricing.
        $validated = $this->validateShipment($request);

        $shipment = Shipment::create($validated);

        return response()->json($shipment, 201);
    }

    public function show(Shipment $shipment): JsonResponse
    {
        return response()->json($shipment->load(['scanEvents', 'rateCard', 'originZone', 'destinationZone', 'assignedRider']));
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
            'rate_card_id' => 'nullable|exists:rate_cards,id',
            'origin_address' => 'required|string',
            'origin_zone_id' => 'nullable|exists:zones,id',
            'destination_address' => 'required|string',
            'destination_zone_id' => 'nullable|exists:zones,id',
            'distance_km' => 'nullable|numeric',
            'weight_kg' => 'nullable|numeric',
            'length_cm' => 'nullable|numeric',
            'width_cm' => 'nullable|numeric',
            'height_cm' => 'nullable|numeric',
            'is_cod' => 'sometimes|boolean',
            'cod_amount' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            abort(response()->json(['errors' => $validator->errors()], 422));
        }

        return $validator->validated();
    }
}
