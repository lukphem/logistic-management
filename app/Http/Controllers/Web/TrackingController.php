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
        $request->validate(['tracking_numbers' => 'required|string|max:4000']);

        // One per line or comma-separated, both accepted — trimmed
        // and de-duplicated, since a pasted list often has stray
        // blank lines or repeats.
        $numbers = collect(preg_split('/[\r\n,]+/', $request->input('tracking_numbers')))
            ->map(fn ($n) => trim($n))
            ->filter()
            ->unique()
            ->values();

        if ($numbers->isEmpty()) {
            return redirect()->route('tracking.search')->withErrors(['tracking_numbers' => 'Enter at least one number.']);
        }

        if ($numbers->count() === 1) {
            return redirect()->route('tracking.show', $numbers->first());
        }

        return redirect()->route('tracking.multi', ['numbers' => $numbers->implode(',')]);
    }

    /**
     * Several numbers at once — each resolved through the exact same
     * per-number logic show() uses (shipment/manifest/trip), just
     * summarized into one row per number rather than opening a
     * separate page for each.
     */
    public function multi(Request $request): View
    {
        $numbers = collect(explode(',', (string) $request->query('numbers')))
            ->map(fn ($n) => trim($n))
            ->filter()
            ->unique()
            ->values();

        $results = $numbers->map(function ($number) {
            if (str_starts_with($number, 'MAN-')) {
                $manifest = Manifest::where('manifest_number', $number)->with('manifestShipments')->first();

                return ['number' => $number, 'kind' => 'manifest', 'found' => (bool) $manifest, 'count' => $manifest?->manifestShipments->count(), 'status' => $manifest?->status];
            }

            if (str_starts_with($number, 'TRIP-')) {
                $trip = ManifestTrip::where('trip_number', $number)->first();

                return ['number' => $number, 'kind' => 'trip', 'found' => (bool) $trip, 'status' => $trip?->isDispatched() ? 'dispatched' : 'draft'];
            }

            $shipment = Shipment::where('tracking_number', $number)->first();

            return ['number' => $number, 'kind' => 'shipment', 'found' => (bool) $shipment, 'status' => $shipment?->current_status, 'receiver_name' => $shipment?->receiver_name];
        });

        return view('tracking.multi', compact('results'));
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

    /**
     * Staff who are logged in and viewing this same page see a
     * richer version — who handled each scan, who it was handed to,
     * GPS/photo/signature evidence — all deliberately excluded from
     * the public view. This is the one place that distinction is
     * actually decided: not a separate page, just more relations
     * eager-loaded and an isStaff flag the view checks before
     * showing the extra detail.
     */
    private function showShipment(string $trackingNumber): View
    {
        $isStaff = auth()->check();

        $shipment = Shipment::where('tracking_number', $trackingNumber)
            ->with(array_filter([
                'scanEvents' => fn ($q) => $q->orderBy('scanned_at'),
                'scanEvents.hub',
                'scanEvents.outlet',
                'scanEvents.destinationHub',
                $isStaff ? 'scanEvents.handler' : null,
                $isStaff ? 'scanEvents.handedTo' : null,
                'originCity',
                'destinationCity',
                'serviceType',
            ]))
            ->first();

        // Keyed by ScanStatus.key so the timeline can resolve a scan
        // event's raw status string (e.g. "out_for_delivery") to its
        // staff-configured human label, the same label staff see
        // everywhere else in the app — never inventing separate
        // customer-facing wording here that could drift from what
        // staff actually configured.
        $statusLabels = ScanStatus::all()->pluck('label', 'key');

        return view('tracking.show', compact('shipment', 'trackingNumber', 'statusLabels', 'isStaff'));
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
