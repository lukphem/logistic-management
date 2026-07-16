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
        $query = Shipment::with(['originZone', 'destinationZone', 'assignedRider']);

        // Hub-restricted staff only see shipments currently sitting at
        // their hub — global staff (hub_id null) see everything. Applied
        // here rather than in a global scope so it's obvious from reading
        // this controller alone, and so a global user's query never pays
        // the extra WHERE for no reason.
        if (! auth()->user()->hasGlobalAccess()) {
            $query->where('current_hub_id', auth()->user()->hub_id);
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
        if (! auth()->user()->hasGlobalAccess() && $shipment->current_hub_id !== auth()->user()->hub_id) {
            abort(403, "This shipment isn't at a hub you have access to.");
        }

        $shipment->load(['scanEvents.handler', 'rateCard', 'originZone', 'destinationZone', 'assignedRider']);

        return view('shipments.show', compact('shipment'));
    }
}
