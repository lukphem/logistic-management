<?php

namespace App\Http\Controllers\Web;

use App\Models\Hub;
use App\Models\Manifest;
use App\Models\ManifestTrip;
use App\Models\Outlet;
use App\Models\ScanEvent;
use App\Models\ScanStatus;
use App\Models\VehicleType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ManifestController extends \App\Http\Controllers\Controller
{
    /**
     * Adding another Manifest to an existing Trip — the multi-drop
     * case, where one vehicle carries several destination batches.
     * Only possible while the trip is still a draft (undispatched);
     * once dispatched the whole trip locks, this route with it.
     */
    public function create(ManifestTrip $trip): View
    {
        abort_if($trip->isDispatched(), 403, 'This trip has already been dispatched.');

        return view('manifests.manifests.create', [
            'trip' => $trip,
            'destinationHubs' => Hub::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, ManifestTrip $trip): RedirectResponse
    {
        abort_if($trip->isDispatched(), 403, 'This trip has already been dispatched.');

        $data = $request->validate([
            'destination_hub_id' => 'required|exists:hubs,id',
            'estimated_arrival_at' => 'nullable|date',
            'shipment_ids' => 'nullable|array',
            'shipment_ids.*' => 'exists:shipments,id',
        ]);

        $manifest = DB::transaction(function () use ($data, $trip) {
            $manifest = Manifest::create([
                'manifest_number' => Manifest::generateManifestNumber(),
                'manifest_trip_id' => $trip->id,
                'destination_hub_id' => $data['destination_hub_id'],
                'estimated_arrival_at' => $data['estimated_arrival_at'] ?? null,
            ]);

            foreach (array_unique($data['shipment_ids'] ?? []) as $shipmentId) {
                $manifest->shipments()->attach($shipmentId, ['condition' => 'pending']);
            }

            return $manifest;
        });

        return redirect()->route('manifest-trips.show', $trip)->with('status', "Manifest {$manifest->manifest_number} added to trip {$trip->trip_number}.");
    }

    /**
     * The receiving checklist — every shipment expected on this
     * manifest, each with its own condition to record. Only staff
     * with access to the manifest's own destination can receive it,
     * and only a manifest that's actually been dispatched (nothing to
     * receive off a draft that never left).
     */
    public function receive(Manifest $manifest): View
    {
        $user = auth()->user();

        abort_unless(
            $this->userCanAccessLocation($user, $manifest->destination_hub_id, $manifest->destination_outlet_id),
            403,
            "You don't have access to this manifest's destination."
        );

        abort_unless($manifest->status === 'dispatched', 403, 'This manifest is not currently in transit to be received.');

        $manifest->load(['trip', 'destinationHub', 'destinationOutlet', 'manifestShipments.shipment']);

        return view('manifests.manifests.receive', compact('manifest'));
    }

    /**
     * Closes the manifest — records each shipment's condition
     * (received/damaged/missing), moves the shipment's current
     * location to this manifest's destination, and creates the
     * matching scan event/status for each one (arrived normally,
     * arrived damaged, or missing — the exact per-shipment audit
     * trail this whole feature was built for). A manifest with any
     * non-"received" condition gets its own discrepancy note so it's
     * visible at a glance, not buried in the per-shipment detail.
     */
    public function storeReceive(Request $request, Manifest $manifest): RedirectResponse
    {
        $user = auth()->user();

        abort_unless(
            $this->userCanAccessLocation($user, $manifest->destination_hub_id, $manifest->destination_outlet_id),
            403,
            "You don't have access to this manifest's destination."
        );

        abort_unless($manifest->status === 'dispatched', 403, 'This manifest is not currently in transit to be received.');

        $data = $request->validate([
            'conditions' => 'required|array',
            'conditions.*' => 'required|in:received,damaged,missing',
            'notes' => 'nullable|array',
            'notes.*' => 'nullable|string|max:1000',
        ]);

        $statusMap = [
            'received' => 'arrived_at_hub',
            'damaged' => 'arrived_damaged',
            'missing' => 'missing',
        ];
        $scanStatuses = ScanStatus::whereIn('key', array_values($statusMap))->get()->keyBy('key');

        DB::transaction(function () use ($manifest, $data, $user, $statusMap, $scanStatuses) {
            $hasDiscrepancy = false;

            foreach ($manifest->manifestShipments as $manifestShipment) {
                $condition = $data['conditions'][$manifestShipment->shipment_id] ?? 'received';
                $note = $data['notes'][$manifestShipment->shipment_id] ?? null;

                $manifestShipment->update(['condition' => $condition, 'condition_notes' => $note, 'scanned_at' => now()]);

                if ($condition !== 'received') {
                    $hasDiscrepancy = true;
                }

                $statusKey = $statusMap[$condition];
                $scanStatus = $scanStatuses->get($statusKey);

                $shipment = $manifestShipment->shipment;

                ScanEvent::create([
                    'shipment_id' => $shipment->id,
                    'status' => $statusKey,
                    'hub_id' => $manifest->destination_hub_id,
                    'outlet_id' => $manifest->destination_outlet_id,
                    'handled_by' => $user->id,
                    'scanned_at' => now(),
                ]);

                $shipmentUpdate = ['current_status' => $statusKey];
                if ($condition === 'received') {
                    $shipmentUpdate['current_hub_id'] = $manifest->destination_hub_id;
                    $shipmentUpdate['current_outlet_id'] = $manifest->destination_outlet_id;
                }
                $shipment->update($shipmentUpdate);

                if ($scanStatus?->notify_customer) {
                    $recipients = array_filter([$shipment->receiver_email, $shipment->sender_email]);
                    if (! empty($recipients)) {
                        \Illuminate\Support\Facades\Mail::to($recipients)
                            ->queue(new \App\Mail\ShipmentStatusUpdated($shipment, $scanStatus->label));
                    }
                }
            }

            $manifest->update([
                'status' => 'received',
                'received_by_user_id' => $user->id,
                'received_at' => now(),
                'discrepancy_notes' => $hasDiscrepancy ? 'One or more shipments were not received in full/good condition — see per-shipment notes.' : null,
            ]);
        });

        return redirect()->route('manifest-trips.show', $manifest->trip)->with('status', "Manifest {$manifest->manifest_number} received.");
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
