<?php

namespace App\Services;

use App\Models\Shipment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * "General report that oversees all shipment status" — one row per
 * shipment, covering the full lifecycle. Pickup date, last scan, and
 * who posted the proof of delivery all come from scan_events, but
 * pulling that relation per shipment (even eager-loaded) would still
 * mean scanning every event for every shipment in PHP to find "the
 * first pickup scan" or "the most recent scan" — instead, each is
 * its own correlated subquery, resolved by the database in the same
 * pass as the main query, the same reasoning as the UNION ALL
 * approach in the payment activity report.
 */
class ShipmentStatusReportService
{
    /**
     * @param array{date_from?: string, date_to?: string, status?: string, outlet_ids?: array<int>} $filters
     */
    public function query(array $filters): Builder
    {
        $query = Shipment::query()
            ->with(['originHub', 'destinationHub', 'originCity.state', 'destinationCity.state', 'serviceType', 'createdBy'])
            ->addSelect('shipments.*')
            ->addSelect(['last_scan_status' => DB::table('scan_events')
                ->select('status')
                ->whereColumn('shipment_id', 'shipments.id')
                ->orderByDesc('scanned_at')
                ->limit(1),
            ])
            ->addSelect(['pickup_date' => DB::table('scan_events')
                ->join('scan_statuses', 'scan_statuses.key', '=', 'scan_events.status')
                ->select('scan_events.scanned_at')
                ->whereColumn('scan_events.shipment_id', 'shipments.id')
                ->where('scan_statuses.is_first_touch', true)
                ->orderBy('scan_events.scanned_at')
                ->limit(1),
            ])
            ->addSelect(['pod_handler_name' => DB::table('scan_events')
                ->join('scan_statuses', 'scan_statuses.key', '=', 'scan_events.status')
                ->join('users', 'users.id', '=', 'scan_events.handled_by')
                ->select('users.name')
                ->whereColumn('scan_events.shipment_id', 'shipments.id')
                ->where('scan_statuses.is_delivery_attempt', true)
                ->orderByDesc('scan_events.scanned_at')
                ->limit(1),
            ])
            ->addSelect(['actual_recipient' => DB::table('scan_events')
                ->join('scan_statuses', 'scan_statuses.key', '=', 'scan_events.status')
                ->select('scan_events.receiver_name')
                ->whereColumn('scan_events.shipment_id', 'shipments.id')
                ->where('scan_statuses.is_delivery_attempt', true)
                ->whereNotNull('scan_events.receiver_name')
                ->orderByDesc('scan_events.scanned_at')
                ->limit(1),
            ]);

        if (! empty($filters['date_from'])) {
            $query->whereDate('shipments.created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('shipments.created_at', '<=', $filters['date_to']);
        }

        if (! empty($filters['status'])) {
            $query->where('shipments.current_status', $filters['status']);
        }

        if (! empty($filters['outlet_ids'])) {
            $query->whereIn('shipments.current_outlet_id', $filters['outlet_ids']);
        }

        return $query->orderByDesc('shipments.created_at');
    }

    /**
     * "Department Code" has no direct data source in this app — the
     * only existing "Department" concept belongs to a client
     * account's own internal sub-structure, isn't linked to
     * individual shipments, and has no code field of its own.
     * Shipment records which hub it originated from, not a specific
     * outlet — the booking hub's own code is the closest real proxy
     * available, used here explicitly rather than left blank or
     * invented.
     */
    public function departmentCode(Shipment $shipment): ?string
    {
        return $shipment->originHub?->code;
    }
}
