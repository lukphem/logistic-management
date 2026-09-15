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
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'photo_path' => 'nullable|string',
            'signature_path' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $shipment = Shipment::findOrFail($request->shipment_id);
        $data = $validator->validated();

        $hubId = $data['hub_id'] ?? null;
        $outletId = $data['outlet_id'] ?? null;

        if ($outletId) {
            $outlet = \App\Models\Outlet::find($outletId);
            $hubId = $outlet?->hub_id ?? $hubId;
        }

        $scanEvent = ScanEvent::create([
            ...$data,
            'hub_id' => $hubId,
            'handled_by' => $request->user()->id,
            'scanned_at' => now(),
        ]);

        $shipmentUpdate = [
            'current_status' => $request->status,
            'delivered_at' => $request->status === 'delivered' ? now() : $shipment->delivered_at,
        ];

        if ($hubId) {
            $shipmentUpdate['current_hub_id'] = $hubId;
            $shipmentUpdate['current_outlet_id'] = $outletId; // null clears it when scanning at the hub itself
        }

        // Counts against the shipment's own client account's
        // Maximum Delivery Attempt limit — not every scan status
        // qualifies, only ones staff have explicitly marked as
        // representing an attempt (ScanStatus::is_delivery_attempt),
        // since statuses are fully staff-configurable and the app has
        // no reliable way to infer this from a label alone.
        $scanStatus = \App\Models\ScanStatus::where('key', $request->status)->first();

        if ($scanStatus?->is_delivery_attempt) {
            $shipmentUpdate['delivery_attempts_count'] = $shipment->delivery_attempts_count + 1;

            $maxAttempts = $shipment->client_account_id
                ? \App\Models\ClientAccount::find($shipment->client_account_id)?->effectiveMaximumDeliveryAttempts()
                : null;

            if ($maxAttempts && $shipmentUpdate['delivery_attempts_count'] >= $maxAttempts && ! $shipment->delivery_attempts_exceeded_at) {
                $shipmentUpdate['delivery_attempts_exceeded_at'] = now();
            }
        }

        $shipment->update($shipmentUpdate);

        // Milestone email — only for statuses staff have explicitly
        // marked notify-worthy (ScanStatus::notify_customer), and only
        // to whichever of receiver/sender email actually has one on
        // file (most walk-in senders never give an email at all, and
        // that's fine — this just quietly sends to whoever's
        // reachable, never both-or-nothing). Queued via the Mailable's
        // own ShouldQueue, so this never adds latency to the rider's
        // own scan response even if the mail server is slow.
        if ($scanStatus?->notify_customer) {
            $recipients = array_filter([$shipment->receiver_email, $shipment->sender_email]);

            if (! empty($recipients)) {
                \Illuminate\Support\Facades\Mail::to($recipients)
                    ->queue(new \App\Mail\ShipmentStatusUpdated($shipment, $scanStatus->label));
            }
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
