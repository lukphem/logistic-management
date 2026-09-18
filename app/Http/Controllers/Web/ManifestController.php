<?php

namespace App\Http\Controllers\Web;

use App\Models\Hub;
use App\Models\Manifest;
use App\Models\ManifestTrip;
use App\Services\ManifestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * All the actual add-manifest/receive logic lives in ManifestService
 * — this controller only handles the web-specific bits (form
 * validation, redirects, view rendering); the mobile API controller
 * calls the exact same service methods.
 */
class ManifestController extends \App\Http\Controllers\Controller
{
    public function __construct(private ManifestService $manifests)
    {
    }

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

        $manifest = $this->manifests->addManifestToTrip(
            $trip,
            [
                'destination_hub_id' => $data['destination_hub_id'],
                'estimated_arrival_at' => $data['estimated_arrival_at'] ?? null,
            ],
            $data['shipment_ids'] ?? []
        );

        return redirect()->route('manifest-trips.show', $trip)->with('status', "Manifest {$manifest->manifest_number} added to trip {$trip->trip_number}.");
    }

    /**
     * The receiving checklist — every shipment expected on this
     * manifest, each with its own condition to record. Only staff
     * with access to the manifest's own destination can receive it,
     * and only a manifest that's actually been dispatched.
     */
    public function receive(Manifest $manifest): View
    {
        $user = auth()->user();

        abort_unless(
            $this->manifests->userCanAccessLocation($user, $manifest->destination_hub_id, $manifest->destination_outlet_id),
            403,
            "You don't have access to this manifest's destination."
        );

        abort_unless($manifest->status === 'dispatched', 403, 'This manifest is not currently in transit to be received.');

        $manifest->load(['trip', 'destinationHub', 'destinationOutlet', 'manifestShipments.shipment']);

        return view('manifests.manifests.receive', compact('manifest'));
    }

    public function storeReceive(Request $request, Manifest $manifest): RedirectResponse
    {
        $user = auth()->user();

        abort_unless(
            $this->manifests->userCanAccessLocation($user, $manifest->destination_hub_id, $manifest->destination_outlet_id),
            403,
            "You don't have access to this manifest's destination."
        );

        $data = $request->validate([
            'conditions' => 'required|array',
            'conditions.*' => 'required|in:received,damaged,missing',
            'notes' => 'nullable|array',
            'notes.*' => 'nullable|string|max:1000',
        ]);

        try {
            $this->manifests->receiveManifest($manifest, $data['conditions'], $data['notes'] ?? [], $user);
        } catch (\RuntimeException $e) {
            return redirect()->route('manifest-trips.show', $manifest->trip)->withErrors(['manifest' => $e->getMessage()]);
        }

        return redirect()->route('manifest-trips.show', $manifest->trip)->with('status', "Manifest {$manifest->manifest_number} received.");
    }
}
