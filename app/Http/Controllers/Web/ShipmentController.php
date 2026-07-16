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

        // Filters to whatever the viewer's access scope resolves to:
        // every hub (global), every hub in their region, or just their
        // one hub — see User::accessibleHubIds(). Skipped entirely for
        // global users so their query never carries an unnecessary
        // WHERE IN across every hub ID.
        if (! auth()->user()->hasGlobalAccess()) {
            $query->whereIn('current_hub_id', auth()->user()->accessibleHubIds());
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
        if (! auth()->user()->hasGlobalAccess() && ! in_array($shipment->current_hub_id, auth()->user()->accessibleHubIds())) {
            abort(403, "This shipment isn't at a hub you have access to.");
        }

        $shipment->load(['scanEvents.handler', 'rateCard', 'originZone', 'destinationZone', 'assignedRider']);

        return view('shipments.show', compact('shipment'));
    }
}
