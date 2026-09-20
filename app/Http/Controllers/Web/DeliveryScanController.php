<?php

namespace App\Http\Controllers\Web;

use App\Models\Hub;
use App\Models\Outlet;
use App\Models\ScanStatus;
use App\Models\Shipment;
use App\Services\ScanService;
use App\Services\TrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Delivery Scan is deliberately its own flow, separate from the
 * other five (pickup/dropoff/arrival/departure/exception), which
 * stay as a fast scan-and-go loop. Delivery is different on purpose:
 * each shipment is looked up and its registered details shown BEFORE
 * anything is recorded, so staff can actually verify they're
 * delivering the right package before confirming — and since more
 * than one waybill can go to the same person in one visit, several
 * shipments can be gathered into one batch that shares a single
 * receiver name, signature, and photo, with one explicit
 * confirmation before any of them are marked delivered.
 */
class DeliveryScanController extends \App\Http\Controllers\Controller
{
    public function __construct(private ScanService $scans, private TrackingService $tracking)
    {
    }

    public function index(): View
    {
        $user = auth()->user();
        [$hubs, $outlets, $locked] = $this->resolveLocationOptions($user);

        return view('operational-scans.delivery', [
            'hubs' => $hubs,
            'outlets' => $outlets,
            'lockedLocationLabel' => $locked,
            'lockedHubId' => $locked ? $user->hub_id : null,
            'lockedOutletId' => $locked ? $user->outlet_id : null,
        ]);
    }

    /**
     * Fetches a shipment's registered details for verification —
     * deliberately records nothing. This is the "does this actually
     * match who's in front of me" step, separate from the delivery
     * scan itself.
     */
    public function lookup(Request $request): JsonResponse
    {
        $request->validate(['number' => 'required|string']);

        $result = $this->tracking->resolveShipmentsForScan(trim($request->input('number')));

        if (! $result['found']) {
            return response()->json(['found' => false, 'message' => $result['message']], 404);
        }

        $eligible = $result['shipments']->reject(
            fn ($s) => in_array($s->current_status, ['delivered', 'returned', 'cancelled'], true)
        )->values();

        if ($eligible->isEmpty()) {
            return response()->json(['found' => false, 'message' => 'Every shipment in that batch is already out of the company\'s hands.'], 422);
        }

        return response()->json([
            'found' => true,
            'shipments' => $eligible->map(fn ($s) => $this->tracking->verificationSummary($s, withStaffFallback: true))->values(),
        ]);
    }

    /**
     * One receiver, one piece of evidence, any number of shipments —
     * each recorded through the exact same ScanService::recordScan()
     * every other scan uses, so the audit trail, terminal/custody
     * checks, and notify_customer emails all behave identically to a
     * single delivery. If one shipment in the batch fails its own
     * check (already delivered by the time this submits, say), the
     * rest still go through — the response reports each outcome
     * individually rather than failing the whole batch for one bad
     * item.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'shipment_ids' => 'required|array|min:1',
            'shipment_ids.*' => 'exists:shipments,id',
            'receiver_name' => 'required|string|max:255',
            'photo_path' => 'nullable|string',
            'signature_path' => 'nullable|string',
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

        $results = [];

        foreach (array_unique($data['shipment_ids']) as $shipmentId) {
            $shipment = Shipment::find($shipmentId);

            try {
                $this->scans->recordScan([
                    'shipment_id' => $shipmentId,
                    'status' => 'delivered',
                    'hub_id' => $data['hub_id'] ?? null,
                    'outlet_id' => $data['outlet_id'] ?? null,
                    'receiver_name' => $data['receiver_name'],
                    'photo_path' => $data['photo_path'] ?? null,
                    'signature_path' => $data['signature_path'] ?? null,
                ], $user->id);

                $results[] = ['id' => $shipmentId, 'tracking_number' => $shipment?->tracking_number, 'success' => true];
            } catch (\RuntimeException $e) {
                $results[] = ['id' => $shipmentId, 'tracking_number' => $shipment?->tracking_number, 'success' => false, 'message' => $e->getMessage()];
            }
        }

        return response()->json(['results' => $results]);
    }

    private function resolveLocationOptions($user): array
    {
        if ($user->hasGlobalAccess()) {
            return [Hub::orderBy('name')->get(), Outlet::orderBy('name')->get(), null];
        }

        if ($user->hasRegionAccess()) {
            $hubs = Hub::where('region_id', $user->region_id)->orderBy('name')->get();
            $outlets = Outlet::whereIn('hub_id', $hubs->pluck('id'))->orderBy('name')->get();

            return [$hubs, $outlets, null];
        }

        if ($user->hasOutletAccess()) {
            $outlet = Outlet::find($user->outlet_id);

            return [collect(), collect(), $outlet?->name ?? 'Your outlet'];
        }

        if ($user->hasHubAccess()) {
            $hub = Hub::find($user->hub_id);

            return [collect(), collect(), $hub?->name ?? 'Your hub'];
        }

        return [collect(), collect(), null];
    }
}
