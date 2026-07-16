<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShipmentController extends Controller
{
    public function index(Request $request): View
    {
        $query = Shipment::with(['originZone', 'destinationZone', 'originCity', 'destinationCity', 'assignedRider', 'currentOutlet']);

        $user = auth()->user();

        // Outlet-scoped users see shipments physically at their outlet
        // specifically (current_outlet_id) — a narrower check than the
        // hub-level accessibleHubIds() filter everyone else uses. Skipped
        // entirely for global users so their query never carries an
        // unnecessary WHERE for no reason.
        if ($user->hasOutletAccess()) {
            $query->where('current_outlet_id', $user->outlet_id);
        } elseif (! $user->hasGlobalAccess()) {
            $query->whereIn('current_hub_id', $user->accessibleHubIds());
        }

        if ($request->filled('status')) {
            $query->where('current_status', $request->status);
        }

        if ($request->filled('search')) {
            $query->where('tracking_number', 'like', "%{$request->search}%");
        }

        $shipments = $query->latest()->paginate(15)->withQueryString();

        return view('shipments.index', compact('shipments'));
    }

    public function show(Shipment $shipment): View
    {
        abort_unless(auth()->user()->canAccessShipment($shipment), 403, "This shipment isn't somewhere you have access to.");

        $shipment->load(['scanEvents.handler', 'scanEvents.outlet', 'rateCard', 'originZone', 'destinationZone', 'originCity', 'destinationCity', 'assignedRider', 'currentOutlet']);

        return view('shipments.show', compact('shipment'));
    }
}
