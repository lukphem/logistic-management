<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RiderLocation;
use App\Models\ScanEvent;
use App\Models\Shipment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RiderController extends Controller
{
    public function __construct(private \App\Services\ScanService $scans)
    {
    }

    public function assignedOrders(Request $request): JsonResponse
    {
        $orders = Shipment::where('assigned_rider_id', $request->user()->id)
            ->where('is_test', false)
            ->whereNotIn('current_status', ['delivered', 'returned'])
            ->latest()
            ->get();

        return response()->json($orders);
    }

    /**
     * Records a scan event and advances the shipment's current_status to
     * match. Kept as a single action (rather than separate scan/update
     * endpoints) since in practice a rider's scan IS the status update.
     *
     * A scan at an outlet resolves both current_outlet_id AND
     * current_hub_id (from the outlet's parent hub) together, so hub- and
     * region-scoped staff still see the shipment via the hub rollup while
     * outlet-scoped staff see it via the more specific outlet match. A
     * scan at the hub itself (no outlet_id) clears current_outlet_id —
     * the shipment is no longer "at" any particular outlet.
     */
    public function scan(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'shipment_id' => 'required|exists:shipments,id',
            'status' => 'required|string',
            'hub_id' => 'nullable|exists:hubs,id',
            'outlet_id' => 'nullable|exists:outlets,id',
            'destination_hub_id' => 'nullable|exists:hubs,id',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'photo_path' => 'nullable|string',
            'signature_path' => 'nullable|string',
            'receiver_name' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $scanEvent = $this->scans->recordScan($validator->validated(), $request->user()->id);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($scanEvent, 201);
    }

    /**
     * Alias of scan() for clients that separate "scan a barcode" from
     * "update status manually" in their UI — same effect either way.
     */
    public function updateStatus(Request $request): JsonResponse
    {
        return $this->scan($request);
    }

    /**
     * Upserts the rider's latest position. This is a live-position table,
     * not a history log — historical location is captured per-shipment via
     * scan events instead.
     */
    public function pingLocation(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $location = RiderLocation::updateOrCreate(
            ['rider_id' => $request->user()->id],
            [
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'recorded_at' => now(),
            ]
        );

        return response()->json($location);
    }

    /**
     * Marks the cash a rider collected from the receiver as physically
     * in hand — not yet "remitted" in the old sense (that concept is
     * gone), but eligible for settlement: a hub/outlet staff member
     * picks it up on the Reconciliation page along with whatever else
     * is outstanding and pays the accumulated total to the company via
     * Paystack in one batch. Same collection_method/cash_collected_at
     * fields a walk-in's cash payment at an outlet counter uses — a
     * receiver paying a rider cash on delivery is the same kind of
     * event as a walk-in paying cash at a counter, so it flows through
     * the same settlement mechanism rather than a separate one.
     *
     * Ownership check added here — the previous version let any
     * authenticated rider mark any COD shipment collected, with no
     * verification they were the one actually assigned to it.
     */
    public function remitCod(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'shipment_id' => 'required|exists:shipments,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $shipment = Shipment::findOrFail($request->shipment_id);

        if (! $shipment->is_cod) {
            return response()->json(['message' => 'Shipment is not a COD order'], 422);
        }

        if ($shipment->assigned_rider_id !== $request->user()->id) {
            return response()->json(['message' => "This shipment isn't assigned to you"], 403);
        }

        if ($shipment->cash_collected_at) {
            return response()->json(['message' => 'Already marked as collected'], 422);
        }

        $shipment->update(['collection_method' => 'cash', 'cash_collected_at' => now()]);

        return response()->json($shipment);
    }

    /**
     * Basic delivery-count earnings view. Rider commission structure
     * (per-delivery pay rates) is scoped for a later increment — this
     * returns the raw counts a commission calculation would run against.
     */
    public function earnings(Request $request): JsonResponse
    {
        $from = $request->date('from') ?? now()->startOfMonth();
        $to = $request->date('to') ?? now();

        $delivered = Shipment::where('assigned_rider_id', $request->user()->id)
            ->where('current_status', 'delivered')
            ->whereBetween('delivered_at', [$from, $to])
            ->count();

        return response()->json([
            'period' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'deliveries_completed' => $delivered,
        ]);
    }
}
