<?php

namespace App\Http\Controllers\Web;

use App\Models\Manifest;
use App\Models\ManifestTrip;
use App\Models\Setting;
use App\Models\Shipment;
use App\Services\TrackingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The general, number-based way in to printing — separate from the
 * per-record Print buttons already on a shipment's page or a trip's
 * page. One number field accepts a shipment's own tracking number
 * (waybill), a manifest number, or a trip number, and lands on the
 * right document either way — the same three-way detection
 * TrackingService already uses for tracking lookups, reused here
 * rather than re-implemented.
 */
class PrintDocumentController extends \App\Http\Controllers\Controller
{
    public function __construct(private TrackingService $tracking)
    {
    }

    public function search(): View
    {
        return view('print-documents.search');
    }

    public function lookup(Request $request): RedirectResponse
    {
        $request->validate(['number' => 'required|string|max:64']);

        $number = trim($request->input('number'));
        $kind = $this->tracking->resolveKind($number);

        if ($kind === 'manifest') {
            $manifest = Manifest::where('manifest_number', $number)->first();

            if (! $manifest) {
                return redirect()->route('print-documents.search')->withErrors(['number' => "No manifest found for \"{$number}\"."]);
            }

            return redirect()->route('manifests.print', $manifest);
        }

        if ($kind === 'trip') {
            $trip = ManifestTrip::where('trip_number', $number)->first();

            if (! $trip) {
                return redirect()->route('print-documents.search')->withErrors(['number' => "No trip found for \"{$number}\"."]);
            }

            return redirect()->route('manifest-trips.print', $trip);
        }

        $shipment = Shipment::where('tracking_number', $number)->first();

        if (! $shipment) {
            return redirect()->route('print-documents.search')->withErrors(['number' => "No shipment found for \"{$number}\"."]);
        }

        abort_unless(auth()->user()->canAccessShipment($shipment), 403, "This shipment isn't somewhere you have access to.");

        return redirect()->route('shipments.waybill', $shipment);
    }
}
