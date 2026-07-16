<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    /**
     * Exception queue: shipments flagged as failed/exception status, or
     * that have breached their promised delivery time. SLA breach
     * detection itself (comparing promised_delivery_at to now) is a
     * scheduled job to be added in a later increment — this endpoint just
     * surfaces whatever's already flagged.
     */
    public function exceptions(Request $request): JsonResponse
    {
        $query = Shipment::with(['originZone', 'destinationZone', 'assignedRider'])
            ->where(function ($q) {
                $q->where('current_status', 'exception')
                    ->orWhere('sla_breached', true);
            });

        return response()->json($query->latest()->paginate($request->integer('per_page', 20)));
    }
}
