<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $stats = [
            'total_shipments' => Shipment::count(),
            'in_transit' => Shipment::whereNotIn('current_status', ['delivered', 'cancelled', 'returned'])->count(),
            'delivered_today' => Shipment::where('current_status', 'delivered')
                ->whereDate('delivered_at', today())
                ->count(),
            'exceptions' => Shipment::where('current_status', 'exception')
                ->orWhere('sla_breached', true)
                ->count(),
        ];

        $recentShipments = Shipment::with('assignedRider')->latest()->limit(8)->get();

        return view('dashboard.index', compact('stats', 'recentShipments'));
    }
}
