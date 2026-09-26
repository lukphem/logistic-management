<?php

namespace App\Http\Controllers\Web\Portal;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use Illuminate\View\View;

class PortalDashboardController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        $account = $user->defaultAccount;

        $shipments = Shipment::where('client_user_id', $user->id)
            ->latest()
            ->limit(10)
            ->get();

        return view('portal.dashboard', [
            'user' => $user,
            'account' => $account,
            'wallet' => $account?->wallet,
            'shipments' => $shipments,
            'shipmentCount' => Shipment::where('client_user_id', $user->id)->count(),
        ]);
    }
}
