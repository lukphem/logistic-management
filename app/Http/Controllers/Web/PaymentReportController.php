<?php

namespace App\Http\Controllers\Web;

use App\Models\Outlet;
use App\Models\Shipment;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The historical record — every cash-collected shipment ever, paid
 * or not, not just what's currently outstanding (that's what
 * Reconciliation is for). Gated on payments:read so it's a real
 * permission decision, not just "anyone who can see shipments can
 * see the money report" the way Reconciliation itself currently is.
 *
 * Outlet-scoped staff see only their own outlet's own history — this
 * is what "the outlet should be able to access their own history
 * too" means in practice: the same controller and page, the access
 * scoping already built into every staff account is what narrows it
 * per person, not a separate outlet-facing page.
 */
class PaymentReportController extends \App\Http\Controllers\Controller
{
    public function index(Request $request): View
    {
        $user = auth()->user();

        $query = Shipment::where('collection_method', 'cash')
            ->whereNotNull('cash_collected_at')
            ->with(['currentOutlet', 'assignedRider', 'cashSettlement']);

        if ($user->hasOutletAccess()) {
            $query->where('current_outlet_id', $user->outlet_id);
        } elseif (! $user->hasGlobalAccess()) {
            $query->whereIn('current_hub_id', $user->accessibleHubIds());
        }

        if ($request->filled('outlet_id')) {
            $query->where('current_outlet_id', $request->input('outlet_id'));
        }

        if ($request->filled('status')) {
            $status = $request->input('status');
            if ($status === 'paid') {
                $query->where('payment_status', 'paid');
            } elseif ($status === 'unpaid') {
                $query->where('payment_status', '!=', 'paid');
            }
        }

        if ($request->filled('date_from')) {
            $query->whereDate('cash_collected_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('cash_collected_at', '<=', $request->input('date_to'));
        }

        $shipments = $query->latest('cash_collected_at')->paginate(30)->withQueryString();

        $summary = [
            'paid_count' => (clone $query)->where('payment_status', 'paid')->count(),
            'paid_total' => (clone $query)->where('payment_status', 'paid')->sum('total_amount'),
            'unpaid_count' => (clone $query)->where('payment_status', '!=', 'paid')->count(),
            'unpaid_total' => (clone $query)->where('payment_status', '!=', 'paid')->sum('total_amount'),
        ];

        // Only global/hub-scoped staff get an outlet filter to choose
        // from — an outlet-scoped user only ever sees their own
        // outlet's data anyway, so a filter offering other outlets
        // they can't see would be misleading.
        $outlets = $user->hasOutletAccess() ? collect() : Outlet::orderBy('name')->get(['id', 'name']);

        return view('payment-reports.index', compact('shipments', 'summary', 'outlets'));
    }
}
