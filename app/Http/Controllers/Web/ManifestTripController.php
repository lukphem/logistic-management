<?php

namespace App\Http\Controllers\Web;

use App\Models\Hub;
use App\Models\ManifestTrip;
use App\Models\Outlet;
use App\Models\VehicleType;
use App\Services\ManifestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * A Trip is one physical vehicle journey — created together with its
 * first Manifest (a trip with no manifest inside it is meaningless),
 * with more manifests addable later for a multi-drop route before
 * the trip is dispatched. Once dispatched, the whole trip locks: no
 * new manifests, no more shipments added to any of them.
 *
 * All the actual create/dispatch/eligibility logic lives in
 * ManifestService — this controller only handles the web-specific
 * bits (form validation, redirects, view rendering); the mobile API
 * controller (Api\ManifestController) calls the exact same service
 * methods.
 */
class ManifestTripController extends \App\Http\Controllers\Controller
{
    public function __construct(private ManifestService $manifests)
    {
    }

    public function index(): View
    {
        $user = auth()->user();

        $trips = ManifestTrip::with(['originHub', 'originOutlet', 'manifests.destinationHub'])
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

        return view('manifests.trips.index', compact('trips'));
    }

    public function create(): View
    {
        $user = auth()->user();

        $hubs = Hub::whereIn('id', $user->accessibleHubIds())->orderBy('name')->get();
        $outlets = $user->hasOutletAccess()
            ? Outlet::where('id', $user->outlet_id)->get()
            : Outlet::whereIn('hub_id', $user->accessibleHubIds())->orderBy('name')->get();

        return view('manifests.trips.create', [
            'hubs' => $hubs,
            'outlets' => $outlets,
            'destinationHubs' => Hub::orderBy('name')->get(),
            'vehicleTypes' => VehicleType::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
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

        $user = auth()->user();
        $originHubId = $data['origin_hub_id'] ?? null;
        $originOutletId = $data['origin_outlet_id'] ?? null;

        abort_unless(
            (! $originHubId && ! $originOutletId) || $this->manifests->userCanAccessLocation($user, $originHubId, $originOutletId),
            403,
            "You don't have access to that origin."
        );

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

        return redirect()->route('manifest-trips.show', $trip)->with('status', "Trip {$trip->trip_number} created with manifest {$trip->manifests->first()->manifest_number}.");
    }

    public function show(ManifestTrip $trip): View
    {
        $trip->load(['originHub', 'originOutlet', 'vehicleType', 'dispatchedBy', 'manifests.destinationHub', 'manifests.destinationOutlet', 'manifests.manifestShipments']);

        return view('manifests.trips.show', compact('trip'));
    }

    /**
     * The document that accompanies the driver — one sheet covering
     * every manifest on the trip, each shipment's key details
     * (pieces, service type, receiver phone, weight), and a
     * signature block per destination so whoever receives that
     * manifest's batch can sign for it right there on the page the
     * driver is already carrying — no separate confirmation slip
     * needed.
     */
    public function print(ManifestTrip $trip): View
    {
        $trip->load([
            'originHub', 'originOutlet', 'vehicleType', 'dispatchedBy',
            'manifests.destinationHub', 'manifests.destinationOutlet',
            'manifests.manifestShipments.shipment' => fn ($q) => $q->with('serviceType'),
        ]);

        $settings = \App\Models\Setting::current();

        return view('manifests.trips.print', compact('trip', 'settings'));
    }

    public function dispatch(ManifestTrip $trip): RedirectResponse
    {
        $user = auth()->user();

        abort_unless(
            (! $trip->origin_hub_id && ! $trip->origin_outlet_id) || $this->manifests->userCanAccessLocation($user, $trip->origin_hub_id, $trip->origin_outlet_id),
            403,
            "You don't have access to this trip's origin."
        );

        try {
            $this->manifests->dispatchTrip($trip, $user);
        } catch (\RuntimeException $e) {
            return redirect()->route('manifest-trips.show', $trip)->withErrors(['trip' => $e->getMessage()]);
        }

        return redirect()->route('manifest-trips.show', $trip)->with('status', "Trip {$trip->trip_number} dispatched.");
    }

    public function eligibleShipments(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'hub_id' => 'nullable|exists:hubs,id',
            'outlet_id' => 'nullable|exists:outlets,id',
        ]);

        $user = auth()->user();
        $hubId = $request->input('hub_id');
        $outletId = $request->input('outlet_id');

        abort_unless(
            (! $hubId && ! $outletId) || $this->manifests->userCanAccessLocation($user, $hubId, $outletId),
            403
        );

        return response()->json($this->manifests->eligibleShipmentsGrouped($hubId, $outletId));
    }

    public function lookupByTrackingNumber(Request $request): \Illuminate\Http\JsonResponse
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
