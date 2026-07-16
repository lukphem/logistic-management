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
            ->whereNotIn('current_status', ['delivered', 'returned'])
            ->latest()
            ->get();

        return response()->json($orders);
    }

    /**
     * Records a scan event and advances the shipment's current_status to
     * match. Kept as a single action (rather than separate scan/update
     * endpoints) since in practice a rider's scan IS the status update.
     */
    public function scan(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'shipment_id' => 'required|exists:shipments,id',
            'status' => 'required|string',
            'hub_id' => 'nullable|exists:hubs,id',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'photo_path' => 'nullable|string',
            'signature_path' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $shipment = Shipment::findOrFail($request->shipment_id);

        $scanEvent = ScanEvent::create([
            ...$validator->validated(),
            'handled_by' => $request->user()->id,
            'scanned_at' => now(),
        ]);

        $shipment->update([
            'current_status' => $request->status,
            'delivered_at' => $request->status === 'delivered' ? now() : $shipment->delivered_at,
        ]);

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

        $shipment->update(['cod_remitted_at' => now()]);

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
