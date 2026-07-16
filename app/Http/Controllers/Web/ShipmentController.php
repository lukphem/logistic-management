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
        $shipment->load(['scanEvents.handler', 'rateCard', 'originZone', 'destinationZone', 'assignedRider']);

        return view('shipments.show', compact('shipment'));
    }
}
