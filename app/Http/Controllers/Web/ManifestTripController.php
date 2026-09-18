<?php

namespace App\Http\Controllers\Web;

use App\Models\Hub;
use App\Models\Manifest;
use App\Models\ManifestTrip;
use App\Models\Outlet;
use App\Models\ScanEvent;
use App\Models\ScanStatus;
use App\Models\Shipment;
use App\Models\VehicleType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * A Trip is one physical vehicle journey — created together with its
 * first Manifest (a trip with no manifest inside it is meaningless),
 * with more manifests addable later for a multi-drop route before
 * the trip is dispatched. Once dispatched, the whole trip locks: no
 * new manifests, no more shipments added to any of them.
 */
class ManifestTripController extends \App\Http\Controllers\Controller
{
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

    /**
     * Creates the Trip and its first Manifest together, and attaches
     * whichever shipments were selected (bulk-by-destination-code or
     * individually scanned) — all inside one transaction, since a
     * trip that exists with no manifest, or a manifest with a
     * half-attached shipment list, isn't a valid state to leave
     * behind if anything fails partway through.
     */
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
            (! $originHubId && ! $originOutletId) || $this->userCanAccessLocation($user, $originHubId, $originOutletId),
            403,
            "You don't have access to that origin."
        );

        $trip = DB::transaction(function () use ($data, $originHubId, $originOutletId) {
            $trip = ManifestTrip::create([
                'trip_number' => ManifestTrip::generateTripNumber(),
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
            ]);

            $manifest = Manifest::create([
                'manifest_number' => Manifest::generateManifestNumber(),
                'manifest_trip_id' => $trip->id,
                'destination_hub_id' => $data['destination_hub_id'],
                'estimated_arrival_at' => $data['estimated_arrival_at'] ?? null,
            ]);

            foreach (array_unique($data['shipment_ids'] ?? []) as $shipmentId) {
                $manifest->shipments()->attach($shipmentId, ['condition' => 'pending']);
            }

            return $trip;
        });

        return redirect()->route('manifest-trips.show', $trip)->with('status', "Trip {$trip->trip_number} created with manifest {$trip->manifests->first()->manifest_number}.");
    }

    public function show(ManifestTrip $trip): View
    {
        $trip->load(['originHub', 'originOutlet', 'vehicleType', 'dispatchedBy', 'manifests.destinationHub', 'manifests.destinationOutlet', 'manifests.manifestShipments']);

        return view('manifests.trips.show', compact('trip'));
    }

    /**
     * Locks the whole trip in one action — every draft manifest
     * inside it moves to dispatched, and every shipment on every one
     * of those manifests gets an "in_transit" scan event, same as any
     * other status-changing scan (same notify_customer check, same
     * audit trail). A trip already dispatched can't be dispatched
     * again.
     */
    public function dispatch(ManifestTrip $trip): RedirectResponse
    {
        $user = auth()->user();

        abort_unless(
            (! $trip->origin_hub_id && ! $trip->origin_outlet_id) || $this->userCanAccessLocation($user, $trip->origin_hub_id, $trip->origin_outlet_id),
            403,
            "You don't have access to this trip's origin."
        );

        if ($trip->isDispatched()) {
            return redirect()->route('manifest-trips.show', $trip)->with('status', 'This trip is already dispatched.');
        }

        if ($trip->manifests->isEmpty()) {
            return redirect()->route('manifest-trips.show', $trip)->withErrors(['trip' => 'Add at least one manifest before dispatching.']);
        }

        DB::transaction(function () use ($trip, $user) {
            $trip->update(['dispatched_by_user_id' => $user->id, 'dispatched_at' => now()]);

            $inTransitStatus = ScanStatus::where('key', 'in_transit')->first();

            foreach ($trip->manifests as $manifest) {
                if ($manifest->status !== 'draft') {
                    continue;
                }

                $manifest->update(['status' => 'dispatched']);

                foreach ($manifest->shipments as $shipment) {
                    ScanEvent::create([
                        'shipment_id' => $shipment->id,
                        'status' => 'in_transit',
                        'hub_id' => $trip->origin_hub_id,
                        'outlet_id' => $trip->origin_outlet_id,
                        'handled_by' => $user->id,
                        'scanned_at' => now(),
                    ]);

                    $shipment->update(['current_status' => 'in_transit']);

                    if ($inTransitStatus?->notify_customer) {
                        $recipients = array_filter([$shipment->receiver_email, $shipment->sender_email]);
                        if (! empty($recipients)) {
                            \Illuminate\Support\Facades\Mail::to($recipients)
                                ->queue(new \App\Mail\ShipmentStatusUpdated($shipment, $inTransitStatus->label));
                        }
                    }
                }
            }
        });

        return redirect()->route('manifest-trips.show', $trip)->with('status', "Trip {$trip->trip_number} dispatched.");
    }

    /**
     * Shipments currently at a given hub/outlet, grouped by their
     * destination_hub_id — the "common destination code" bulk-select
     * this whole feature was built around. Returns JSON for the
     * create-trip page's own JS to render as pick-a-destination-code
     * groups.
     */
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
            (! $hubId && ! $outletId) || $this->userCanAccessLocation($user, $hubId, $outletId),
            403
        );

        $query = Shipment::query()
            ->whereNotIn('current_status', ['delivered', 'returned', 'cancelled'])
            ->whereDoesntHave('manifestShipments.manifest', fn ($q) => $q->whereIn('status', ['draft', 'dispatched']))
            ->with('destinationHub:id,name,code');

        if ($outletId) {
            $query->where('current_outlet_id', $outletId);
        } elseif ($hubId) {
            $query->where('current_hub_id', $hubId)->whereNull('current_outlet_id');
        }

        $shipments = $query->get(['id', 'tracking_number', 'destination_hub_id', 'receiver_name', 'total_amount']);

        $grouped = $shipments->groupBy(fn ($s) => $s->destination_hub_id ?? 0)->map(function ($group) {
            $hub = $group->first()->destinationHub;

            return [
                'destination_hub_id' => $hub?->id,
                'destination_hub_code' => $hub?->code ?? 'Unassigned',
                'destination_hub_name' => $hub?->name ?? 'No destination hub set',
                'shipments' => $group->map(fn ($s) => [
                    'id' => $s->id,
                    'tracking_number' => $s->tracking_number,
                    'receiver_name' => $s->receiver_name,
                ])->values(),
            ];
        })->values();

        return response()->json($grouped);
    }

    /**
     * Barcode/QR scan-to-add — a handheld scanner or a phone camera
     * both just need "type/scan a tracking number, get back whether
     * it's addable and to which destination group." Same eligibility
     * rule as the bulk list above (not already on an active manifest,
     * not already terminal), so a scanned shipment can't be
     * double-added any more than a manually ticked one could.
     */
    public function lookupByTrackingNumber(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate(['tracking_number' => 'required|string']);

        $shipment = Shipment::where('tracking_number', trim($request->input('tracking_number')))
            ->with('destinationHub:id,name,code')
            ->first();

        if (! $shipment) {
            return response()->json(['found' => false, 'message' => 'No shipment with that tracking number.'], 404);
        }

        if (in_array($shipment->current_status, ['delivered', 'returned', 'cancelled'], true)) {
            return response()->json(['found' => false, 'message' => "{$shipment->tracking_number} is already {$shipment->current_status} and can't be manifested."], 422);
        }

        $onActiveManifest = $shipment->manifestShipments()->whereHas('manifest', fn ($q) => $q->whereIn('status', ['draft', 'dispatched']))->exists();
        if ($onActiveManifest) {
            return response()->json(['found' => false, 'message' => "{$shipment->tracking_number} is already on an active manifest."], 422);
        }

        return response()->json([
            'found' => true,
            'id' => $shipment->id,
            'tracking_number' => $shipment->tracking_number,
            'receiver_name' => $shipment->receiver_name,
            'destination_hub_id' => $shipment->destination_hub_id,
            'destination_hub_code' => $shipment->destinationHub?->code,
        ]);
    }

    private function userCanAccessLocation($user, ?int $hubId, ?int $outletId): bool
    {
        if ($user->hasGlobalAccess()) {
            return true;
        }

        if ($outletId) {
            return $user->hasOutletAccess() && $user->outlet_id === $outletId;
        }

        if ($hubId) {
            return in_array($hubId, $user->accessibleHubIds(), true);
        }

        return false;
    }
}
