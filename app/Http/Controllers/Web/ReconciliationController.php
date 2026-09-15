<?php

namespace App\Http\Controllers\Web;

use App\Models\CashSettlement;
use App\Models\Shipment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Cash settlement — where physically-collected cash (a walk-in paying
 * at an outlet counter, or a rider collecting COD on delivery) gets
 * turned into an actual electronic payment to the company via
 * Paystack. Same access scoping as the shipments list itself
 * (ShipmentController::index) — outlet-scoped staff see only their
 * own outlet's cash, hub-scoped staff see their hub's, global staff
 * see everything — since settling cash you can't see or touch makes
 * no sense.
 */
class ReconciliationController extends \App\Http\Controllers\Controller
{
    public function index(): View
    {
        $shipments = $this->eligibleShipments()
            ->with(['currentOutlet', 'assignedRider'])
            ->latest('cash_collected_at')
            ->get();

        $pastSettlements = CashSettlement::with('initiatedBy')
            ->where('initiated_by_user_id', auth()->id())
            ->latest()
            ->limit(10)
            ->get();

        return view('reconciliation.index', compact('shipments', 'pastSettlements'));
    }

    /**
     * Bundles whichever shipments were selected into one CashSettlement
     * and hands off to PaymentController::paySettlement() to actually
     * take payment — this action only ever builds the batch, it never
     * touches payment_status itself. A shipment stays exactly as
     * outstanding as it was until the settlement's own Paystack
     * transaction is confirmed.
     */
    /**
     * Bundles whichever shipments were selected into one CashSettlement
     * and hands off to PaymentController::paySettlement() to actually
     * take payment — this action only ever builds the batch, it never
     * touches payment_status itself. A shipment stays exactly as
     * outstanding as it was until the settlement's own Paystack
     * transaction is confirmed.
     *
     * Wrapped in a transaction with lockForUpdate() — without it, two
     * staff members submitting overlapping selections at nearly the
     * same moment could both read the same shipment as "still
     * eligible" before either one's update lands, and both end up
     * claiming it into two different settlements. The lock forces the
     * second request to wait for the first to finish (and re-read the
     * now-claimed row as ineligible) rather than racing it.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'shipment_ids' => 'required|array|min:1',
            'shipment_ids.*' => 'exists:shipments,id',
        ]);

        $settlement = \Illuminate\Support\Facades\DB::transaction(function () use ($request) {
            // Re-fetched from the same eligible/scoped query rather than
            // trusting the submitted IDs outright — a staff member can only
            // ever settle shipments they could actually see on this page in
            // the first place, and re-checking "still eligible" here closes
            // the gap where two people try to settle the same shipment in
            // two different batches at once.
            $shipments = $this->eligibleShipments()
                ->whereIn('id', $request->input('shipment_ids'))
                ->lockForUpdate()
                ->get();

            if ($shipments->isEmpty()) {
                return null;
            }

            $settlement = CashSettlement::create([
                'initiated_by_user_id' => auth()->id(),
                'total_amount' => $shipments->sum('total_amount'),
                'status' => 'pending',
            ]);

            Shipment::whereIn('id', $shipments->pluck('id'))->update(['cash_settlement_id' => $settlement->id]);

            return $settlement;
        });

        if (! $settlement) {
            return redirect()->route('reconciliation.index')->withErrors(['reconciliation' => 'None of the selected shipments are still eligible for settlement — someone may have already settled them.']);
        }

        return redirect()->route('payments.pay-settlement', $settlement);
    }

    private function eligibleShipments()
    {
        $query = Shipment::where('collection_method', 'cash')
            ->whereNotNull('cash_collected_at')
            ->where('payment_status', '!=', 'paid')
            ->whereNull('cash_settlement_id');

        $user = auth()->user();

        if ($user->hasOutletAccess()) {
            $query->where('current_outlet_id', $user->outlet_id);
        } elseif (! $user->hasGlobalAccess()) {
            $query->whereIn('current_hub_id', $user->accessibleHubIds());
        }

        return $query;
    }
}
