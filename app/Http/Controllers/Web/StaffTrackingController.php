<?php

namespace App\Http\Controllers\Web;

use App\Models\ScanStatus;
use App\Services\TrackingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The staff-facing counterpart to the public TrackingController — a
 * genuinely separate page (its own routes, its own views under the
 * app's sidebar layout) rather than the public page showing more
 * when someone happens to be logged in. Full internal detail here:
 * who scanned each event, who it was handed to, delivery evidence,
 * and the last-scan location falls back to the handling staff
 * member's own assigned location when the scan itself didn't record
 * one — none of which the public page shows.
 */
class StaffTrackingController extends \App\Http\Controllers\Controller
{
    public function __construct(private TrackingService $tracking)
    {
    }

    public function search(): View
    {
        return view('tracking.staff-search');
    }

    public function submit(Request $request): RedirectResponse
    {
        $request->validate(['tracking_numbers' => 'required|string|max:4000']);

        $numbers = collect(preg_split('/[\r\n,]+/', $request->input('tracking_numbers')))
            ->map(fn ($n) => trim($n))
            ->filter()
            ->unique()
            ->values();

        if ($numbers->isEmpty()) {
            return redirect()->route('staff-tracking.search')->withErrors(['tracking_numbers' => 'Enter at least one number.']);
        }

        if ($numbers->count() === 1) {
            return redirect()->route('staff-tracking.show', $numbers->first());
        }

        return redirect()->route('staff-tracking.multi', ['numbers' => $numbers->implode(',')]);
    }

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

            return ['number' => $number, 'kind' => 'shipment', 'found' => (bool) $shipment, 'status' => $shipment?->current_status, 'receiver_name' => $shipment?->receiver_name];
        });

        return view('tracking.staff-multi', ['results' => $results, 'numbersParam' => $numbers->implode(',')]);
    }

    public function show(Request $request, string $trackingNumber): View
    {
        $number = trim($trackingNumber);
        $kind = $this->tracking->resolveKind($number);
        $back = $request->query('back');

        if ($kind === 'manifest') {
            $manifest = $this->tracking->findManifest($number);

            return view('tracking.staff-batch', [
                'trackingNumber' => $number,
                'batchLabel' => 'Manifest ' . $number,
                'batch' => $manifest,
                'shipments' => $manifest?->manifestShipments->pluck('shipment')->filter() ?? collect(),
                'back' => $back,
            ]);
        }

        if ($kind === 'trip') {
            $trip = $this->tracking->findTrip($number);

            return view('tracking.staff-batch', [
                'trackingNumber' => $number,
                'batchLabel' => 'Trip ' . $number,
                'batch' => $trip,
                'shipments' => $trip ? $this->tracking->shipmentsOnTrip($trip) : collect(),
                'back' => $back,
            ]);
        }

        $shipment = $this->tracking->findShipment($number, withStaffDetail: true);
        $lastScan = $shipment ? $this->tracking->lastScanSummary($shipment, withStaffFallback: true) : null;
        $statusLabels = ScanStatus::all()->pluck('label', 'key');

        return view('tracking.staff-show', ['shipment' => $shipment, 'trackingNumber' => $number, 'statusLabels' => $statusLabels, 'lastScan' => $lastScan, 'back' => $back]);
    }
}
