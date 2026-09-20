<?php

namespace App\Http\Controllers\Web;

use App\Models\Hub;
use App\Models\Outlet;
use App\Models\ScanStatus;
use App\Services\ScanService;
use App\Services\TrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Six dedicated, single-purpose scan tools — each its own sidebar
 * link, each opening straight into a scan loop for that one
 * operation. Deliberately separate from the manifest system's own
 * scanning (tied to a specific trip/manifest batch) — these take
 * effect immediately and independently. Same ScanService the rider
 * mobile API uses underneath, so the resulting audit trail,
 * notify_customer emails, delivery-attempt counting, terminal-status
 * blocking, and first-touch gating all behave identically to a
 * rider's own scan.
 *
 * Every type follows the same two-step flow: scan/type a number
 * (a shipment's own tracking number, or a manifest/trip batch number
 * — either loads every shipment it represents), see its registered
 * details for verification, then one explicit confirmation before
 * anything is actually recorded. Errors and successes are reported
 * separately in the response so a batch with one bad item doesn't
 * bury the successful ones.
 */
class OperationalScanController extends \App\Http\Controllers\Controller
{
    private const TYPES = [
        'pickup' => ['label' => 'Pickup Scan', 'keys' => ['picked_up'], 'permission' => 'pickup-scan:update'],
        'dropoff' => ['label' => 'Drop-off Scan', 'keys' => ['dropped_off'], 'permission' => 'dropoff-scan:update'],
        'arrival' => ['label' => 'Arrival Scan', 'keys' => ['arrived_at_hub'], 'permission' => 'arrival-scan:update'],
        'departure' => ['label' => 'Departure Scan', 'keys' => ['in_transit', 'out_for_delivery'], 'needs_destination' => true, 'needs_handoff' => true, 'destination_same_city' => true, 'permission' => 'departure-scan:update'],
        'exception' => ['label' => 'Exception Scan', 'keys' => ['arrived_damaged', 'missing', 'exception', 'returned', 'cancelled'], 'permission' => 'exception-scan:update'],
    ];

    public function __construct(private ScanService $scans, private TrackingService $tracking)
    {
    }

    public function index(string $type): View
    {
        abort_unless(array_key_exists($type, self::TYPES), 404);
        $this->authorizeType($type);

        $config = self::TYPES[$type];
        $availableStatuses = ScanStatus::whereIn('key', $config['keys'])->get();

        $user = auth()->user();
        [$hubs, $outlets, $locked] = $this->resolveLocationOptions($user);

        return view('operational-scans.index', [
            'type' => $type,
            'typeLabel' => $config['label'],
            'availableStatuses' => $availableStatuses,
            'isMultiChoice' => count($config['keys']) > 1,
            'needsDestination' => $config['needs_destination'] ?? false,
            'needsHandoff' => $config['needs_handoff'] ?? false,
            'destinationSameCity' => $config['destination_same_city'] ?? false,
            'hubs' => $hubs,
            'outlets' => $outlets,
            'destinationHubs' => ($config['needs_destination'] ?? false) && ! ($config['destination_same_city'] ?? false) ? Hub::orderBy('name')->get() : collect(),
            'riders' => $config['needs_handoff'] ?? false ? \App\Models\User::where('user_type', 'rider')->orderBy('name')->get() : collect(),
            'lockedLocationLabel' => $locked,
            'lockedHubId' => $locked ? $user->hub_id : null,
            'lockedOutletId' => $locked ? $user->outlet_id : null,
        ]);
    }

    /**
     * Records nothing — resolves whatever number was scanned (a
     * tracking number, or a manifest/trip batch number) into the
     * shipment(s) it represents, and returns their verification
     * detail for the pending list. A batch number can add several
     * shipments to the list in one scan.
     */
    public function lookup(Request $request, string $type): JsonResponse
    {
        abort_unless(array_key_exists($type, self::TYPES), 404);
        $this->authorizeType($type);

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
     * One status (and, for Departure, one destination/handoff)
     * applied to every shipment_id in the batch — each recorded
     * independently through ScanService::recordScan(), so one item
     * failing its own check doesn't block the rest. Results report
     * each outcome individually.
     */
    public function store(Request $request, string $type): JsonResponse
    {
        abort_unless(array_key_exists($type, self::TYPES), 404);
        $this->authorizeType($type);

        $config = self::TYPES[$type];

        $data = $request->validate([
            'shipment_ids' => 'required|array|min:1',
            'shipment_ids.*' => 'exists:shipments,id',
            'status' => 'required|string|in:' . implode(',', $config['keys']),
            'hub_id' => 'nullable|exists:hubs,id',
            'outlet_id' => 'nullable|exists:outlets,id',
            'destination_hub_id' => ($config['needs_destination'] ?? false) ? 'required_unless:status,out_for_delivery|nullable|exists:hubs,id' : 'nullable|exists:hubs,id',
            'handed_to_user_id' => ($config['needs_handoff'] ?? false) ? 'nullable|exists:users,id' : 'prohibited',
        ]);

        $user = $request->user();

        [$hubId, $outletId, $locationError] = $this->resolveScanLocation($user, $data['hub_id'] ?? null, $data['outlet_id'] ?? null);

        if ($locationError) {
            return response()->json(['message' => $locationError], 403);
        }

        $results = [];
        $destinationHubId = $data['destination_hub_id'] ?? null;

        foreach (array_unique($data['shipment_ids']) as $shipmentId) {
            $shipment = \App\Models\Shipment::find($shipmentId);

            // A "transfer" to the same place it's already at isn't a
            // transfer at all — most likely the origin and
            // destination were picked the wrong way round, so this
            // is caught here rather than silently recorded as a
            // no-op movement.
            if ($destinationHubId && (int) $destinationHubId === (int) $hubId) {
                $results[] = ['id' => $shipmentId, 'tracking_number' => $shipment?->tracking_number, 'success' => false, 'message' => "{$shipment?->tracking_number} is already at that location — pick a different destination."];
                continue;
            }

            try {
                $scanEvent = $this->scans->recordScan([
                    'shipment_id' => $shipmentId,
                    'status' => $data['status'],
                    'hub_id' => $hubId,
                    'outlet_id' => $outletId,
                    'destination_hub_id' => $destinationHubId,
                    'handed_to_user_id' => $data['handed_to_user_id'] ?? null,
                ], $user->id);

                $results[] = ['id' => $shipmentId, 'tracking_number' => $shipment?->tracking_number, 'success' => true, 'status' => $scanEvent->status];
            } catch (\RuntimeException $e) {
                $results[] = ['id' => $shipmentId, 'tracking_number' => $shipment?->tracking_number, 'success' => false, 'message' => $e->getMessage()];
            }
        }

        return response()->json(['results' => $results]);
    }

    /**
     * Global staff pick freely from every hub/outlet. Regional staff
     * pick freely, but only within their own region. Hub- and outlet-
     * scoped staff aren't offered a choice at all — their own single
     * location is all that's ever available, so there's nothing to
     * pick.
     *
     * @return array{0: \Illuminate\Support\Collection, 1: \Illuminate\Support\Collection, 2: ?string} [hubs, outlets, lockedLocationLabel]
     */
    /**
     * Departure Scan's destination is deliberately scoped to local
     * movement — same city, outlet-to-outlet or unit-to-unit — not
     * the cross-city/regional linehaul a Manifest handles. Resolves
     * the origin's city from whichever hub/outlet was selected (an
     * outlet's own city comes from its parent hub), then returns
     * every other hub/outlet sharing that city. Genuinely remote
     * destinations simply never show up in this list — staff are
     * naturally routed to Manifests for those instead of being told
     * "no" here.
     */
    public function nearbyDestinations(Request $request): JsonResponse
    {
        $request->validate([
            'hub_id' => 'nullable|exists:hubs,id',
            'outlet_id' => 'nullable|exists:outlets,id',
        ]);

        $originHub = $request->input('outlet_id')
            ? Outlet::find($request->input('outlet_id'))?->hub
            : Hub::find($request->input('hub_id'));

        if (! $originHub || ! $originHub->city_id) {
            return response()->json(['hubs' => [], 'outlets' => []]);
        }

        $cityHubs = Hub::where('city_id', $originHub->city_id)->orderBy('name')->get(['id', 'name', 'code']);
        $cityOutlets = Outlet::whereIn('hub_id', $cityHubs->pluck('id'))->orderBy('name')->get(['id', 'name', 'hub_id']);

        return response()->json(['hubs' => $cityHubs, 'outlets' => $cityOutlets]);
    }

    public function uploadEvidence(Request $request): JsonResponse
    {
        $request->validate([
            'kind' => 'required|in:photo,signature',
            'data_url' => 'required|string',
        ]);

        // Signature (canvas draw, camera, or a picked file) and photo
        // (camera capture or a picked file) all hand back a data: URL
        // — decoded and stored here rather than trusting the client
        // to have already uploaded anywhere, so this is the one place
        // any kind of evidence actually lands on disk.
        if (! preg_match('/^data:image\/(png|jpe?g|webp);base64,(.+)$/', $request->input('data_url'), $matches)) {
            return response()->json(['message' => 'Invalid image data.'], 422);
        }

        $extension = $matches[1] === 'jpg' ? 'jpeg' : $matches[1];
        $binary = base64_decode($matches[2]);

        if ($binary === false || strlen($binary) > 8 * 1024 * 1024) {
            return response()->json(['message' => 'Image is invalid or too large.'], 422);
        }

        $folder = $request->input('kind') === 'photo' ? 'delivery-photos' : 'delivery-signatures';
        $filename = $folder . '/' . now()->format('Ymd') . '/' . \Illuminate\Support\Str::uuid() . '.' . $extension;

        \Illuminate\Support\Facades\Storage::disk('public')->put($filename, $binary);

        return response()->json(['path' => $filename]);
    }

    /**
     * Each of the six scan tools has its own permission (e.g.
     * pickup-scan:update) rather than all of them sharing the same
     * generic shipments:update — a company may want a rider-facing
     * counter clerk who can do Pickup and Drop-off but not Exception,
     * say, and that's only possible if each flow is gated separately.
     */
    private function authorizeType(string $type): void
    {
        $permission = self::TYPES[$type]['permission'] ?? null;

        if ($permission && ! auth()->user()->can($permission)) {
            abort(403, "You don't have permission for " . self::TYPES[$type]['label'] . '.');
        }
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

    /**
     * @return array{0: ?int, 1: ?int, 2: ?string} [hubId, outletId, errorMessage]
     */
    private function resolveScanLocation($user, ?int $requestedHubId, ?int $requestedOutletId): array
    {
        if ($user->hasGlobalAccess()) {
            return [$requestedHubId, $requestedOutletId, null];
        }

        if ($user->hasRegionAccess()) {
            if ($requestedOutletId) {
                $outlet = Outlet::find($requestedOutletId);
                if (! $outlet || $outlet->hub?->region_id !== $user->region_id) {
                    return [null, null, "That outlet isn't in your region."];
                }

                return [$outlet->hub_id, $requestedOutletId, null];
            }

            if ($requestedHubId) {
                $hub = Hub::find($requestedHubId);
                if (! $hub || $hub->region_id !== $user->region_id) {
                    return [null, null, "That hub isn't in your region."];
                }
            }

            return [$requestedHubId, null, null];
        }

        if ($user->hasOutletAccess()) {
            $outlet = Outlet::find($user->outlet_id);

            return [$outlet?->hub_id, $user->outlet_id, null];
        }

        if ($user->hasHubAccess()) {
            return [$user->hub_id, null, null];
        }

        return [null, null, null];
    }
}
