<?php

namespace App\Http\Controllers\Web;

use App\Models\ScanStatus;
use App\Services\TrackingService;
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
 * Staff tracking is a genuinely separate page now
 * (Web\StaffTrackingController, under the app's own sidebar layout)
 * rather than this same page showing more when someone happens to be
 * logged in — this page stays exactly this stripped-down regardless
 * of login state.
 */
class TrackingController extends \App\Http\Controllers\Controller
{
    public function __construct(private TrackingService $tracking)
    {
    }

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
            $kind = $this->tracking->resolveKind($number);

            if ($kind === 'manifest') {
                $manifest = $this->tracking->findManifest($number);

                return ['number' => $number, 'kind' => 'manifest', 'found' => (bool) $manifest, 'count' => $manifest?->manifestShipments->count(), 'status' => $manifest?->status];
            }

            if ($kind === 'trip') {
                $trip = $this->tracking->findTrip($number);

                return ['number' => $number, 'kind' => 'trip', 'found' => (bool) $trip, 'status' => $trip?->isDispatched() ? 'dispatched' : 'draft'];
            }

            $shipment = $this->tracking->findShipment($number);
            $lastScan = $shipment ? $this->tracking->lastScanSummary($shipment) : null;

            return [
                'number' => $number,
                'kind' => 'shipment',
                'found' => (bool) $shipment,
                'status' => $shipment?->current_status,
                'receiver_name' => $shipment?->receiver_name,
                'last_scan_date' => $lastScan['date'] ?? null,
                'last_scan_location' => $lastScan['location'] ?? null,
            ];
        });

        return view('tracking.multi', ['results' => $results, 'numbersParam' => $numbers->implode(',')]);
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
     *
     * $back carries the previous multi-track result set forward (the
     * comma-joined numbers), so the page can offer a way back to it
     * rather than only "track another number" and losing the list.
     */
    public function show(Request $request, string $trackingNumber): View
    {
        $number = trim($trackingNumber);
        $kind = $this->tracking->resolveKind($number);
        $back = $request->query('back');

        if ($kind === 'manifest') {
            return $this->showManifest($number, $back);
        }

        if ($kind === 'trip') {
            return $this->showTrip($number, $back);
        }

        return $this->showShipment($number, $back);
    }

    private function showShipment(string $trackingNumber, ?string $back): View
    {
        $shipment = $this->tracking->findShipment($trackingNumber);
        $lastScan = $shipment ? $this->tracking->lastScanSummary($shipment) : null;

        // Keyed by ScanStatus.key so the timeline can resolve a scan
        // event's raw status string (e.g. "out_for_delivery") to its
        // staff-configured human label, the same label staff see
        // everywhere else in the app — never inventing separate
        // customer-facing wording here that could drift from what
        // staff actually configured.
        $statusLabels = ScanStatus::all()->pluck('label', 'key');

        return view('tracking.show', compact('shipment', 'trackingNumber', 'statusLabels', 'lastScan', 'back'));
    }

    private function showManifest(string $manifestNumber, ?string $back): View
    {
        $manifest = $this->tracking->findManifest($manifestNumber);

        return view('tracking.batch', [
            'trackingNumber' => $manifestNumber,
            'batchLabel' => 'Manifest ' . $manifestNumber,
            'batch' => $manifest,
            'shipments' => $manifest?->manifestShipments->pluck('shipment')->filter() ?? collect(),
            'back' => $back,
        ]);
    }

    private function showTrip(string $tripNumber, ?string $back): View
    {
        $trip = $this->tracking->findTrip($tripNumber);

        return view('tracking.batch', [
            'trackingNumber' => $tripNumber,
            'batchLabel' => 'Trip ' . $tripNumber,
            'batch' => $trip,
            'shipments' => $trip ? $this->tracking->shipmentsOnTrip($trip) : collect(),
            'back' => $back,
        ]);
    }
}
