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
        [$hubs, $outlets, $locked, $lockedUnitId] = $this->resolveLocationOptions($user);

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
            'lockedUnitId' => $lockedUnitId,
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
            'destination_hub_id' => 'nullable|exists:hubs,id',
            'destination_unit_id' => ($config['needs_destination'] ?? false) ? 'nullable|exists:units,id' : 'prohibited',
            'handed_to_user_id' => ($config['needs_handoff'] ?? false) ? 'nullable|exists:users,id' : 'prohibited',
        ]);

        // Heading to is required for a destination-needing type
        // unless it's "Out for Delivery" (no destination at all) —
        // but it can be satisfied by either a hub or a unit, the two
        // different kinds of "heading to" this flow supports, so this
        // is checked as a pair rather than as a single required field.
        if (($config['needs_destination'] ?? false) && $data['status'] !== 'out_for_delivery' && empty($data['destination_hub_id']) && empty($data['destination_unit_id'])) {
            return response()->json(['message' => 'Choose where this is heading.'], 422);
        }

        $user = $request->user();

        [$hubId, $outletId, $locationError, $unitId] = $this->resolveScanLocation($user, $data['hub_id'] ?? null, $data['outlet_id'] ?? null);

        if ($locationError) {
            return response()->json(['message' => $locationError], 403);
        }

        $results = [];
        $destinationHubId = $data['destination_hub_id'] ?? null;
        $destinationUnitId = $data['destination_unit_id'] ?? null;

        foreach (array_unique($data['shipment_ids']) as $shipmentId) {
            $shipment = \App\Models\Shipment::find($shipmentId);

            // A "transfer" to the same place it's already at isn't a
            // transfer at all — most likely the origin and
            // destination were picked the wrong way round, so this
            // is caught here rather than silently recorded as a
            // no-op movement. Two separate checks since hub-to-hub
            // and unit-to-unit are two different kinds of "heading
            // to" — a unit-to-unit destination staying within the
            // same hub is expected, not a mistake, so only an
            // identical unit (not an identical hub) trips this one.
            if ($destinationHubId && (int) $destinationHubId === (int) $hubId) {
                $results[] = ['id' => $shipmentId, 'tracking_number' => $shipment?->tracking_number, 'success' => false, 'message' => "{$shipment?->tracking_number} is already at that location — pick a different destination."];
                continue;
            }
            if ($destinationUnitId && $unitId && (int) $destinationUnitId === (int) $unitId) {
                $results[] = ['id' => $shipmentId, 'tracking_number' => $shipment?->tracking_number, 'success' => false, 'message' => "{$shipment?->tracking_number} is already at that unit — pick a different destination."];
                continue;
            }

            // The custody rule for unit-to-unit transfer: a shipment
            // can only depart a unit if it's actually arrived there,
            // unless the person scanning oversees the unit rather
            // than being pinned to it — which is exactly what $unitId
            // being set here already means (resolveScanLocation only
            // returns one for a hub-scoped user with a specific unit
            // assigned; anyone broader gets null and skips this
            // entirely). This only applies to departure-type moves —
            // an arrival is what establishes custody in the first
            // place, so it can't require it as a precondition.
            if ($unitId && $type === 'departure' && (int) $shipment?->current_unit_id !== (int) $unitId) {
                $results[] = ['id' => $shipmentId, 'tracking_number' => $shipment?->tracking_number, 'success' => false, 'message' => "{$shipment?->tracking_number} hasn't arrived at your unit yet — it can't be sent onward from here."];
                continue;
            }

            try {
                $scanEvent = $this->scans->recordScan([
                    'shipment_id' => $shipmentId,
                    'status' => $data['status'],
                    'hub_id' => $hubId,
                    'outlet_id' => $outletId,
                    'unit_id' => $unitId,
                    'destination_hub_id' => $destinationHubId,
                    'destination_unit_id' => $destinationUnitId,
                    'handed_to_user_id' => $data['handed_to_user_id'] ?? null,
                ], $user->id);

                $results[] = ['id' => $shipmentId, 'tracking_number' => $shipment?->tracking_number, 'success' => true, 'status' => $scanEvent->status];
            } catch (\RuntimeException $e) {
                $results[] = ['id' => $shipmentId, 'tracking_number' => $shipment?->tracking_number, 'success' => false, 'message' => $e->getMessage()];
            }
        }

        // A reference number, generated the moment the batch actually
        // confirms rather than fresh on every page load — so if the
        // resulting document is printed more than once, it's always
        // the same number, the way any real reference document
        // would be. The prefix says which kind of scan produced it:
        // TRF for a local transfer, DEL for an out-for-delivery run —
        // the only two departure outcomes that generate a printable
        // document at all.
        $reference = null;
        if ($type === 'departure' && array_filter($results, fn ($r) => $r['success'])) {
            $prefix = $data['status'] === 'out_for_delivery' ? 'DEL' : 'TRF';
            $reference = $prefix . '-' . now()->format('ymd') . '-' . strtoupper(\Illuminate\Support\Str::random(5));
        }

        return response()->json(['results' => $results, 'reference' => $reference]);
    }

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

        $originHubId = $request->input('outlet_id')
            ? Outlet::find($request->input('outlet_id'))?->hub_id
            : $request->input('hub_id');

        $originHub = $originHubId ? Hub::find($originHubId) : null;

        // Units within the origin hub itself — the unit-to-unit case,
        // available regardless of whether the hub has a city set,
        // since this never leaves the building. Excludes the
        // scanning user's own unit if they're pinned to one (can't
        // transfer to where it already is).
        $sameHubUnits = $originHubId
            ? \App\Models\Unit::where('hub_id', $originHubId)
                ->when(auth()->user()->unit_id, fn ($q) => $q->where('id', '!=', auth()->user()->unit_id))
                ->orderBy('name')->get(['id', 'name', 'code'])
            : collect();

        if (! $originHub || ! $originHub->city_id) {
            return response()->json(['hubs' => [], 'outlets' => [], 'units' => $sameHubUnits]);
        }

        $cityHubs = Hub::where('city_id', $originHub->city_id)->orderBy('name')->get(['id', 'name', 'code']);
        $cityOutlets = Outlet::whereIn('hub_id', $cityHubs->pluck('id'))->orderBy('name')->get(['id', 'name', 'hub_id']);

        return response()->json(['hubs' => $cityHubs, 'outlets' => $cityOutlets, 'units' => $sameHubUnits]);
    }

    /**
     * A printable confirmation slip for a local (Departure Scan)
     * transfer — the same shape as the manifest/trip document, scaled
     * down to what an ad-hoc local handover actually needs: no
     * persisted batch record to look up, so the shipment IDs just
     * confirmed are passed straight through and their current details
     * pulled fresh. One signature line for the second person — the
     * receiving unit — to sign for the handover.
     */
    public function printTransfer(Request $request): View
    {
        $request->validate([
            'shipment_ids' => 'required|string',
            'origin_label' => 'nullable|string',
            'destination_label' => 'nullable|string',
            'reference' => 'nullable|string',
        ]);

        $ids = collect(explode(',', $request->input('shipment_ids')))->map(fn ($id) => (int) trim($id))->filter();

        $shipments = \App\Models\Shipment::whereIn('id', $ids)->with('serviceType')->get();

        $settings = \App\Models\Setting::current();

        return view('operational-scans.print-transfer', [
            'shipments' => $shipments,
            'originLabel' => $request->input('origin_label'),
            'destinationLabel' => $request->input('destination_label'),
            'reference' => $request->input('reference'),
            'settings' => $settings,
        ]);
    }

    /**
     * A delivery run sheet for "Out for Delivery" — a genuinely
     * different shape from the local-transfer confirmation
     * (printTransfer() above), which stays standard: one shared
     * handover to one receiving unit. Out for Delivery usually means
     * several different customers on one run, so each shipment gets
     * its own signature and printed-name space — one shared line at
     * the bottom wouldn't make sense when the run has five different
     * receivers on it.
     */
    public function printDeliverySheet(Request $request): View
    {
        $request->validate([
            'shipment_ids' => 'required|string',
            'origin_label' => 'nullable|string',
            'reference' => 'nullable|string',
        ]);

        $ids = collect(explode(',', $request->input('shipment_ids')))->map(fn ($id) => (int) trim($id))->filter();

        $shipments = \App\Models\Shipment::whereIn('id', $ids)->with(['serviceType', 'destinationCity.state'])->get();

        $settings = \App\Models\Setting::current();

        return view('operational-scans.print-delivery-sheet', [
            'shipments' => $shipments,
            'originLabel' => $request->input('origin_label'),
            'riderName' => $request->input('rider_name'),
            'reference' => $request->input('reference'),
            'settings' => $settings,
        ]);
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

    /**
     * Global staff pick freely from every hub/outlet. Regional staff
     * pick freely, but only within their own region. Hub- and outlet-
     * scoped staff aren't offered a choice at all — their own single
     * location is all that's ever available, so there's nothing to
     * pick. A hub-scoped user with a specific unit assigned is locked
     * to that unit specifically, one level finer than the hub itself
     * — matching "by default users' location is set to that" for
     * scanning.
     *
     * @return array{0: \Illuminate\Support\Collection, 1: \Illuminate\Support\Collection, 2: ?string, 3: ?int} [hubs, outlets, lockedLocationLabel, lockedUnitId]
     */
    private function resolveLocationOptions($user): array
    {
        if ($user->hasGlobalAccess()) {
            return [Hub::orderBy('name')->get(), Outlet::orderBy('name')->get(), null, null];
        }

        if ($user->hasRegionAccess()) {
            $hubs = Hub::where('region_id', $user->region_id)->orderBy('name')->get();
            $outlets = Outlet::whereIn('hub_id', $hubs->pluck('id'))->orderBy('name')->get();

            return [$hubs, $outlets, null, null];
        }

        if ($user->hasOutletAccess()) {
            $outlet = Outlet::find($user->outlet_id);

            return [collect(), collect(), $outlet?->name ?? 'Your outlet', null];
        }

        if ($user->hasHubAccess()) {
            if ($user->unit_id) {
                $unit = \App\Models\Unit::find($user->unit_id);

                return [collect(), collect(), $unit ? "{$unit->name} ({$unit->hub->name})" : 'Your unit', $user->unit_id];
            }

            $hub = Hub::find($user->hub_id);

            return [collect(), collect(), $hub?->name ?? 'Your hub', null];
        }

        return [collect(), collect(), null, null];
    }

    /**
     * @return array{0: ?int, 1: ?int, 2: ?string, 3: ?int} [hubId, outletId, errorMessage, unitId]
     */
    private function resolveScanLocation($user, ?int $requestedHubId, ?int $requestedOutletId): array
    {
        if ($user->hasGlobalAccess()) {
            return [$requestedHubId, $requestedOutletId, null, null];
        }

        if ($user->hasRegionAccess()) {
            if ($requestedOutletId) {
                $outlet = Outlet::find($requestedOutletId);
                if (! $outlet || $outlet->hub?->region_id !== $user->region_id) {
                    return [null, null, "That outlet isn't in your region.", null];
                }

                return [$outlet->hub_id, $requestedOutletId, null, null];
            }

            if ($requestedHubId) {
                $hub = Hub::find($requestedHubId);
                if (! $hub || $hub->region_id !== $user->region_id) {
                    return [null, null, "That hub isn't in your region.", null];
                }
            }

            return [$requestedHubId, null, null, null];
        }

        if ($user->hasOutletAccess()) {
            $outlet = Outlet::find($user->outlet_id);

            return [$outlet?->hub_id, $user->outlet_id, null, null];
        }

        if ($user->hasHubAccess()) {
            // Never trust a client-supplied unit — the scanning
            // unit is always the user's own assigned one, resolved
            // server-side, the same as hub/outlet are above.
            return [$user->hub_id, null, null, $user->unit_id];
        }

        return [null, null, null, null];
    }
}
