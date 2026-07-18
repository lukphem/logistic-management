<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClientBillingProfile;
use App\Models\RateCard;
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
     * Staff-initiated (walk-in) booking. Previously created the shipment
     * with no price at all — every billing feature (discounts,
     * onforwarding, zone-based rates, carton rates) only ever applied to
     * client-portal/API bookings via ClientShipmentController. This now
     * resolves pricing exactly the same way that controller does, so a
     * walk-in booking and a client-booked one for the same
     * origin/destination/weight/service produce the same price.
     *
     * client_user_id is optional — a walk-in customer may not have a
     * portal account at all. When it IS provided and that client has a
     * Special billing profile, their discount still applies here.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $this->validateShipment($request);

        $rateCard = ($data['rate_card_id'] ?? null)
            ? RateCard::find($data['rate_card_id'])
            : RateCard::where('service_type', $data['service_type'])->where('is_active', true)->orderByDesc('priority')->first();

        if (! $rateCard) {
            return response()->json(['message' => 'No active rate card configured for this service type'], 422);
        }

        $billingProfile = ClientBillingProfile::resolveForClientUser($data['client_user_id'] ?? null);
        $pricing = $this->pricingService->priceShipment($rateCard, $data, $billingProfile);

        $shipment = Shipment::create([
            ...$data,
            'rate_card_id' => $rateCard->id,
            ...$pricing,
        ]);

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
