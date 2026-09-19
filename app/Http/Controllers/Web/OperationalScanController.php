<?php

namespace App\Http\Controllers\Web;

use App\Models\Hub;
use App\Models\Outlet;
use App\Models\ScanStatus;
use App\Models\Shipment;
use App\Services\ScanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Five dedicated, single-purpose scan tools — each its own sidebar
 * link, each opening straight into a scan loop for that one
 * operation, no dropdown to pick "what am I doing" first (the menu
 * item already answered that). Deliberately separate from the
 * manifest system's own scanning, which is tied to a specific trip/
 * manifest batch — these take effect immediately and independently.
 * Same ScanService the rider mobile API uses underneath, so the
 * resulting audit trail, notify_customer emails, and delivery-
 * attempt counting all behave identically to a rider's own scan.
 *
 * Exception is the one type with more than one underlying status —
 * "arrived damaged, missing, etc." genuinely covers several distinct
 * outcomes, so it's the only one of the five that still shows a
 * (narrowed) dropdown, scoped to just the exception-flavoured
 * statuses rather than every status in the system.
 */
class OperationalScanController extends \App\Http\Controllers\Controller
{
    /**
     * Maps each fixed menu item to the ScanStatus key(s) it covers.
     * Resolved against what's actually configured under Scan Statuses
     * at request time (never assumed to exist) — a status a staff
     * member has renamed or removed just quietly disappears from
     * here rather than breaking the page.
     */
    private const TYPES = [
        'arrival' => ['label' => 'Arrival Scan', 'keys' => ['arrived_at_hub']],
        'departure' => ['label' => 'Departure Scan', 'keys' => ['in_transit']],
        'delivery' => ['label' => 'Delivery Scan', 'keys' => ['delivered']],
        'pickup' => ['label' => 'Pickup Scan', 'keys' => ['picked_up']],
        'exception' => ['label' => 'Exception Scan', 'keys' => ['arrived_damaged', 'missing', 'exception', 'returned', 'cancelled']],
    ];

    public function __construct(private ScanService $scans)
    {
    }

    public function index(string $type): View
    {
        abort_unless(array_key_exists($type, self::TYPES), 404);

        $config = self::TYPES[$type];
        $availableStatuses = ScanStatus::whereIn('key', $config['keys'])->get();

        $user = auth()->user();
        $hubs = Hub::whereIn('id', $user->accessibleHubIds())->orderBy('name')->get();
        $outlets = $user->hasOutletAccess()
            ? Outlet::where('id', $user->outlet_id)->get()
            : Outlet::whereIn('hub_id', $user->accessibleHubIds())->orderBy('name')->get();

        return view('operational-scans.index', [
            'type' => $type,
            'typeLabel' => $config['label'],
            'availableStatuses' => $availableStatuses,
            'isMultiChoice' => count($config['keys']) > 1,
            'hubs' => $hubs,
            'outlets' => $outlets,
        ]);
    }

    public function store(Request $request, string $type): JsonResponse
    {
        abort_unless(array_key_exists($type, self::TYPES), 404);

        $config = self::TYPES[$type];

        $data = $request->validate([
            'tracking_number' => 'required|string',
            'status' => 'required|string|in:' . implode(',', $config['keys']),
            'hub_id' => 'nullable|exists:hubs,id',
            'outlet_id' => 'nullable|exists:outlets,id',
        ]);

        $user = $request->user();

        if (($data['hub_id'] ?? null) || ($data['outlet_id'] ?? null)) {
            $canAccess = $user->hasGlobalAccess()
                || (($data['outlet_id'] ?? null) && $user->hasOutletAccess() && $user->outlet_id === (int) $data['outlet_id'])
                || (($data['hub_id'] ?? null) && in_array((int) $data['hub_id'], $user->accessibleHubIds(), true));

            if (! $canAccess) {
                return response()->json(['message' => "You don't have access to that location."], 403);
            }
        }

        $shipment = Shipment::where('tracking_number', trim($data['tracking_number']))->first();

        if (! $shipment) {
            return response()->json(['message' => 'No shipment with that tracking number.'], 404);
        }

        $scanEvent = $this->scans->recordScan([
            'shipment_id' => $shipment->id,
            'status' => $data['status'],
            'hub_id' => $data['hub_id'] ?? null,
            'outlet_id' => $data['outlet_id'] ?? null,
        ], $user->id);

        return response()->json([
            'tracking_number' => $shipment->tracking_number,
            'receiver_name' => $shipment->receiver_name,
            'status' => $scanEvent->status,
            'scanned_at' => $scanEvent->scanned_at,
        ], 201);
    }
}
