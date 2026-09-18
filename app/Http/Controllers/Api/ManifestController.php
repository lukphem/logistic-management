<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Manifest;
use App\Models\ManifestTrip;
use App\Services\ManifestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Every operation here calls the exact same ManifestService methods
 * the web controllers use — create/dispatch/receive/eligibility all
 * behave identically whether triggered from the staff web app or a
 * mobile device, including the same access-control checks and the
 * same audit trail (scan events, notify_customer emails). This
 * controller only translates between JSON in/out and the service.
 */
class ManifestController extends Controller
{
    public function __construct(private ManifestService $manifests)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $trips = ManifestTrip::with(['originHub:id,name,code', 'originOutlet:id,name', 'manifests.destinationHub:id,name,code'])
            ->when(! $user->hasGlobalAccess(), function ($query) use ($user) {
                $query->where(function ($q) use ($user) {
                    $q->whereIn('origin_hub_id', $user->accessibleHubIds());
                    if ($user->hasOutletAccess()) {
                        $q->orWhere('origin_outlet_id', $user->outlet_id);
                    }
                });
            })
            ->latest()
            ->paginate(20);

        return response()->json($trips);
    }

    public function show(ManifestTrip $trip): JsonResponse
    {
        $trip->load(['originHub:id,name,code', 'originOutlet:id,name', 'vehicleType', 'dispatchedBy:id,name', 'manifests.destinationHub:id,name,code', 'manifests.manifestShipments.shipment:id,tracking_number,receiver_name']);

        return response()->json($trip);
    }

    /**
     * Same request shape as the web form's own fields — origin,
     * vehicle/carrier detail, first destination, and the list of
     * shipment IDs already resolved client-side via the eligibility
     * and lookup endpoints below.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'origin_hub_id' => 'nullable|exists:hubs,id',
            'origin_outlet_id' => 'nullable|exists:outlets,id',
            'transport_mode' => 'required|in:road,air,sea',
            'carrier_type' => 'required|in:company,third_party',
            'carrier_name' => 'required_if:carrier_type,third_party|nullable|string|max:255',
            'vehicle_type_id' => 'nullable|exists:vehicle_types,id',
            'vehicle_identifier' => 'nullable|string|max:255',
            'driver_name' => 'nullable|string|max:255',
            'driver_phone' => 'nullable|string|max:20',
            'notes' => 'nullable|string|max:2000',
            'destination_hub_id' => 'required|exists:hubs,id',
            'estimated_arrival_at' => 'nullable|date',
            'shipment_ids' => 'nullable|array',
            'shipment_ids.*' => 'exists:shipments,id',
        ]);

        $user = $request->user();
        $originHubId = $data['origin_hub_id'] ?? null;
        $originOutletId = $data['origin_outlet_id'] ?? null;

        if (($originHubId || $originOutletId) && ! $this->manifests->userCanAccessLocation($user, $originHubId, $originOutletId)) {
            return response()->json(['message' => "You don't have access to that origin."], 403);
        }

        $trip = $this->manifests->createTripWithManifest(
            tripData: [
                'origin_hub_id' => $originHubId,
                'origin_outlet_id' => $originOutletId,
                'transport_mode' => $data['transport_mode'],
                'carrier_type' => $data['carrier_type'],
                'carrier_name' => $data['carrier_type'] === 'third_party' ? $data['carrier_name'] : null,
                'vehicle_type_id' => $data['vehicle_type_id'] ?? null,
                'vehicle_identifier' => $data['vehicle_identifier'] ?? null,
                'driver_name' => $data['driver_name'] ?? null,
                'driver_phone' => $data['driver_phone'] ?? null,
                'notes' => $data['notes'] ?? null,
            ],
            manifestData: [
                'destination_hub_id' => $data['destination_hub_id'],
                'estimated_arrival_at' => $data['estimated_arrival_at'] ?? null,
            ],
            shipmentIds: $data['shipment_ids'] ?? []
        );

        $trip->load('manifests');

        return response()->json($trip, 201);
    }

    public function storeManifest(Request $request, ManifestTrip $trip): JsonResponse
    {
        if ($trip->isDispatched()) {
            return response()->json(['message' => 'This trip has already been dispatched.'], 403);
        }

        $data = $request->validate([
            'destination_hub_id' => 'required|exists:hubs,id',
            'estimated_arrival_at' => 'nullable|date',
            'shipment_ids' => 'nullable|array',
            'shipment_ids.*' => 'exists:shipments,id',
        ]);

        $manifest = $this->manifests->addManifestToTrip(
            $trip,
            [
                'destination_hub_id' => $data['destination_hub_id'],
                'estimated_arrival_at' => $data['estimated_arrival_at'] ?? null,
            ],
            $data['shipment_ids'] ?? []
        );

        return response()->json($manifest, 201);
    }

    public function dispatch(Request $request, ManifestTrip $trip): JsonResponse
    {
        $user = $request->user();

        if (($trip->origin_hub_id || $trip->origin_outlet_id) && ! $this->manifests->userCanAccessLocation($user, $trip->origin_hub_id, $trip->origin_outlet_id)) {
            return response()->json(['message' => "You don't have access to this trip's origin."], 403);
        }

        try {
            $this->manifests->dispatchTrip($trip, $user);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($trip->fresh(['manifests']));
    }

    public function receive(Request $request, Manifest $manifest): JsonResponse
    {
        $user = $request->user();

        if (! $this->manifests->userCanAccessLocation($user, $manifest->destination_hub_id, $manifest->destination_outlet_id)) {
            return response()->json(['message' => "You don't have access to this manifest's destination."], 403);
        }

        $data = $request->validate([
            'conditions' => 'required|array',
            'conditions.*' => 'required|in:received,damaged,missing',
            'notes' => 'nullable|array',
            'notes.*' => 'nullable|string|max:1000',
        ]);

        try {
            $this->manifests->receiveManifest($manifest, $data['conditions'], $data['notes'] ?? [], $user);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($manifest->fresh(['manifestShipments.shipment']));
    }

    public function eligibleShipments(Request $request): JsonResponse
    {
        $request->validate([
            'hub_id' => 'nullable|exists:hubs,id',
            'outlet_id' => 'nullable|exists:outlets,id',
        ]);

        $user = $request->user();
        $hubId = $request->input('hub_id');
        $outletId = $request->input('outlet_id');

        if (($hubId || $outletId) && ! $this->manifests->userCanAccessLocation($user, $hubId, $outletId)) {
            return response()->json(['message' => "You don't have access to that location."], 403);
        }

        return response()->json($this->manifests->eligibleShipmentsGrouped($hubId, $outletId));
    }

    public function lookupByTrackingNumber(Request $request): JsonResponse
    {
        $request->validate(['tracking_number' => 'required|string']);

        $result = $this->manifests->lookupByTrackingNumber($request->input('tracking_number'));

        if (! $result['found']) {
            return response()->json(['found' => false, 'message' => $result['message']], 404);
        }

        $shipment = $result['shipment'];

        return response()->json([
            'found' => true,
            'id' => $shipment->id,
            'tracking_number' => $shipment->tracking_number,
            'receiver_name' => $shipment->receiver_name,
            'destination_hub_id' => $shipment->destination_hub_id,
            'destination_hub_code' => $shipment->destinationHub?->code,
        ]);
    }
}
