<?php

namespace App\Http\Controllers\Web;

use App\Models\Manifest;
use App\Models\ManifestTrip;
use App\Models\ScanStatus;
use App\Models\Shipment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Public — no login, no API key, the same "type a number and see
 * where it is" page every courier's own website has. Deliberately
 * shows far less than the authenticated API's own track() endpoint
 * (Api\ClientShipmentController::track): full addresses, phone
 * numbers, pricing, GPS coordinates, handler names, and photo/
 * signature evidence are all internal/sender-receiver-only
 * information that has no business being visible to whoever happens
 * to guess or be given a tracking number — only what a courier's own
 * public tracking page actually shows: status, city-level route, and
 * a scan history stripped down to label + location + time.
 *
 * Also usable internally by staff (same page, linked from the
 * sidebar) — the distinction that matters here isn't staff vs.
 * public, it's what's safe to show anyone who has the number at all,
 * since a tracking number itself isn't treated as a secret.
 */
class TrackingController extends \App\Http\Controllers\Controller
{
    public function search(): View
    {
        return view('tracking.search');
    }

    public function submit(Request $request): RedirectResponse
    {
        $request->validate(['tracking_number' => 'required|string|max:64']);

        return redirect()->route('tracking.show', trim($request->input('tracking_number')));
    }

    /**
     * A single number field on the search form doubles as three
     * different kinds of lookup — a shipment's own tracking number,
     * or the batch number of whichever manifest or trip it travelled
     * on — since staff and customers alike think of "the number on
     * the paperwork" as one thing, not three separate systems to
     * remember. Manifest/trip numbers are tried first since their
     * MAN-/TRIP- prefixes make them unambiguous; anything else is
     * treated as a shipment's own number.
     */
    public function show(string $trackingNumber): View
    {
        $number = trim($trackingNumber);

        if (str_starts_with($number, 'MAN-')) {
            return $this->showManifest($number);
        }

        if (str_starts_with($number, 'TRIP-')) {
            return $this->showTrip($number);
        }

        return $this->showShipment($number);
    }

    private function showShipment(string $trackingNumber): View
    {
        $shipment = Shipment::where('tracking_number', $trackingNumber)
            ->with(['scanEvents' => fn ($q) => $q->orderBy('scanned_at'), 'scanEvents.hub', 'scanEvents.outlet', 'scanEvents.destinationHub', 'originCity', 'destinationCity', 'serviceType'])
            ->first();

        // Keyed by ScanStatus.key so the timeline can resolve a scan
        // event's raw status string (e.g. "out_for_delivery") to its
        // staff-configured human label, the same label staff see
        // everywhere else in the app — never inventing separate
        // customer-facing wording here that could drift from what
        // staff actually configured.
        $statusLabels = ScanStatus::all()->pluck('label', 'key');

        return view('tracking.show', compact('shipment', 'trackingNumber', 'statusLabels'));
    }

    private function showManifest(string $manifestNumber): View
    {
        $manifest = Manifest::where('manifest_number', $manifestNumber)
            ->with(['trip', 'destinationHub', 'destinationOutlet', 'manifestShipments.shipment'])
            ->first();

        return view('tracking.batch', [
            'trackingNumber' => $manifestNumber,
            'batchLabel' => 'Manifest ' . $manifestNumber,
            'batch' => $manifest,
            'shipments' => $manifest?->manifestShipments->pluck('shipment')->filter() ?? collect(),
        ]);
    }

    private function showTrip(string $tripNumber): View
    {
        $trip = ManifestTrip::where('trip_number', $tripNumber)
            ->with(['originHub', 'originOutlet', 'manifests.destinationHub', 'manifests.manifestShipments.shipment'])
            ->first();

        $shipments = $trip
            ? $trip->manifests->flatMap(fn ($m) => $m->manifestShipments->pluck('shipment'))->filter()->unique('id')
            : collect();

        return view('tracking.batch', [
            'trackingNumber' => $tripNumber,
            'batchLabel' => 'Trip ' . $tripNumber,
            'batch' => $trip,
            'shipments' => $shipments,
        ]);
    }
}
