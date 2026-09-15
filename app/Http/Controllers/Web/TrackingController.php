<?php

namespace App\Http\Controllers\Web;

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

    public function show(string $trackingNumber): View
    {
        $shipment = Shipment::where('tracking_number', $trackingNumber)
            ->with(['scanEvents' => fn ($q) => $q->orderBy('scanned_at'), 'scanEvents.hub', 'scanEvents.outlet', 'originCity', 'destinationCity', 'serviceType'])
            ->first();

        // Keyed by ScanStatus.key so the timeline can resolve a scan
        // event's raw status string (e.g. "out_for_delivery") to its
        // staff-configured human label, the same label staff see
        // everywhere else in the app — never inventing separate
        // customer-facing wording here that could drift from what
        // staff actually configured.
        $statusLabels = \App\Models\ScanStatus::all()->pluck('label', 'key');

        return view('tracking.show', compact('shipment', 'trackingNumber', 'statusLabels'));
    }
}
