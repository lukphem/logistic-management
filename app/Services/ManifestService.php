<?php

namespace App\Services;

use App\Mail\ShipmentStatusUpdated;
use App\Models\Manifest;
use App\Models\ManifestTrip;
use App\Models\ScanEvent;
use App\Models\ScanStatus;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * The single place every manifest operation actually happens —
 * created so the web controllers (ManifestTripController,
 * ManifestController) and the mobile API controller
 * (Api\ManifestController) call exactly the same tested logic rather
 * than each keeping their own copy that could quietly drift apart.
 * Access-control checks live here too for the same reason: "can this
 * user touch this origin/destination" needs to mean the same thing
 * regardless of which surface asked.
 */
class ManifestService
{
    public function userCanAccessLocation(User $user, ?int $hubId, ?int $outletId): bool
    {
        if ($user->hasGlobalAccess()) {
            return true;
        }

        if ($outletId) {
            return $user->hasOutletAccess() && $user->outlet_id === $outletId;
        }

        if ($hubId) {
            return in_array($hubId, $user->accessibleHubIds(), true);
        }

        return false;
    }

    /**
     * Shipments currently at a location that aren't already on an
     * active (draft or dispatched) manifest and aren't already in a
     * terminal state — the base eligibility rule both the grouped
     * bulk-select list and the scan-to-add lookup enforce identically.
     */
    public function eligibleShipmentsQuery(?int $hubId, ?int $outletId): Builder
    {
        $query = Shipment::query()
            ->whereNotIn('current_status', ['delivered', 'returned', 'cancelled'])
            ->whereDoesntHave('manifestShipments.manifest', fn ($q) => $q->whereIn('status', ['draft', 'dispatched']));

        if ($outletId) {
            $query->where('current_outlet_id', $outletId);
        } elseif ($hubId) {
            $query->where('current_hub_id', $hubId)->whereNull('current_outlet_id');
        }

        return $query;
    }

    /**
     * The "common destination code" bulk-select data — everything
     * eligible at a location, grouped by destination_hub_id using
     * each hub's own code. Same shape returned to both the web
     * create-trip page's own JS and the mobile API.
     */
    public function eligibleShipmentsGrouped(?int $hubId, ?int $outletId): array
    {
        $shipments = $this->eligibleShipmentsQuery($hubId, $outletId)
            ->with('destinationHub:id,name,code')
            ->get(['id', 'tracking_number', 'destination_hub_id', 'receiver_name', 'total_amount']);

        return $shipments->groupBy(fn ($s) => $s->destination_hub_id ?? 0)->map(function ($group) {
            $hub = $group->first()->destinationHub;

            return [
                'destination_hub_id' => $hub?->id,
                'destination_hub_code' => $hub?->code ?? 'Unassigned',
                'destination_hub_name' => $hub?->name ?? 'No destination hub set',
                'shipments' => $group->map(fn ($s) => [
                    'id' => $s->id,
                    'tracking_number' => $s->tracking_number,
                    'receiver_name' => $s->receiver_name,
                ])->values(),
            ];
        })->values()->all();
    }

    /**
     * @return array{found: bool, shipment?: Shipment, message?: string}
     */
    public function lookupByTrackingNumber(string $trackingNumber): array
    {
        $shipment = Shipment::where('tracking_number', trim($trackingNumber))
            ->with('destinationHub:id,name,code')
            ->first();

        if (! $shipment) {
            return ['found' => false, 'message' => 'No shipment with that tracking number.'];
        }

        if (in_array($shipment->current_status, ['delivered', 'returned', 'cancelled'], true)) {
            return ['found' => false, 'message' => "{$shipment->tracking_number} is already {$shipment->current_status} and can't be manifested."];
        }

        $onActiveManifest = $shipment->manifestShipments()->whereHas('manifest', fn ($q) => $q->whereIn('status', ['draft', 'dispatched']))->exists();
        if ($onActiveManifest) {
            return ['found' => false, 'message' => "{$shipment->tracking_number} is already on an active manifest."];
        }

        return ['found' => true, 'shipment' => $shipment];
    }

    /**
     * Creates a Trip and its first Manifest together, attaching
     * whichever shipments were selected — one transaction, since a
     * trip that exists with no manifest, or a manifest with a
     * half-attached shipment list, isn't a valid state to leave
     * behind if anything fails partway through.
     */
    public function createTripWithManifest(array $tripData, array $manifestData, array $shipmentIds): ManifestTrip
    {
        return DB::transaction(function () use ($tripData, $manifestData, $shipmentIds) {
            $trip = ManifestTrip::create([
                'trip_number' => ManifestTrip::generateTripNumber(),
                ...$tripData,
            ]);

            $manifest = Manifest::create([
                'manifest_number' => Manifest::generateManifestNumber(),
                'manifest_trip_id' => $trip->id,
                ...$manifestData,
            ]);

            foreach (array_unique($shipmentIds) as $shipmentId) {
                $manifest->shipments()->attach($shipmentId, ['condition' => 'pending']);
            }

            return $trip;
        });
    }

    public function addManifestToTrip(ManifestTrip $trip, array $manifestData, array $shipmentIds): Manifest
    {
        return DB::transaction(function () use ($trip, $manifestData, $shipmentIds) {
            $manifest = Manifest::create([
                'manifest_number' => Manifest::generateManifestNumber(),
                'manifest_trip_id' => $trip->id,
                ...$manifestData,
            ]);

            foreach (array_unique($shipmentIds) as $shipmentId) {
                $manifest->shipments()->attach($shipmentId, ['condition' => 'pending']);
            }

            return $manifest;
        });
    }

    /**
     * Locks the whole trip — every draft manifest inside it moves to
     * dispatched, and every shipment on every one of those manifests
     * gets an "in_transit" scan event, same notify_customer check and
     * audit trail any other status-changing scan gets.
     *
     * @throws \RuntimeException if already dispatched or has no manifests
     */
    public function dispatchTrip(ManifestTrip $trip, User $user): void
    {
        if ($trip->isDispatched()) {
            throw new \RuntimeException('This trip is already dispatched.');
        }

        if ($trip->manifests->isEmpty()) {
            throw new \RuntimeException('Add at least one manifest before dispatching.');
        }

        DB::transaction(function () use ($trip, $user) {
            $trip->update(['dispatched_by_user_id' => $user->id, 'dispatched_at' => now()]);

            $inTransitStatus = ScanStatus::where('key', 'in_transit')->first();

            foreach ($trip->manifests as $manifest) {
                if ($manifest->status !== 'draft') {
                    continue;
                }

                $manifest->update(['status' => 'dispatched']);

                foreach ($manifest->shipments as $shipment) {
                    ScanEvent::create([
                        'shipment_id' => $shipment->id,
                        'status' => 'in_transit',
                        'hub_id' => $trip->origin_hub_id,
                        'outlet_id' => $trip->origin_outlet_id,
                        'handled_by' => $user->id,
                        'scanned_at' => now(),
                    ]);

                    $shipment->update(['current_status' => 'in_transit']);

                    $this->notifyIfConfigured($shipment, $inTransitStatus);
                }
            }
        });
    }

    /**
     * Closes the manifest — records each shipment's condition
     * (received/damaged/missing), moves the shipment's current
     * location to this manifest's destination on genuine receipt,
     * and creates the matching scan event/status for each one. A
     * manifest with any non-"received" condition gets its own
     * discrepancy note.
     *
     * $conditions/$notes are keyed by shipment_id.
     *
     * @throws \RuntimeException if the manifest isn't currently dispatched
     */
    public function receiveManifest(Manifest $manifest, array $conditions, array $notes, User $user): void
    {
        if ($manifest->status !== 'dispatched') {
            throw new \RuntimeException('This manifest is not currently in transit to be received.');
        }

        $statusMap = [
            'received' => 'arrived_at_hub',
            'damaged' => 'arrived_damaged',
            'missing' => 'missing',
        ];
        $scanStatuses = ScanStatus::whereIn('key', array_values($statusMap))->get()->keyBy('key');

        DB::transaction(function () use ($manifest, $conditions, $notes, $user, $statusMap, $scanStatuses) {
            $hasDiscrepancy = false;

            foreach ($manifest->manifestShipments as $manifestShipment) {
                $condition = $conditions[$manifestShipment->shipment_id] ?? 'received';
                $note = $notes[$manifestShipment->shipment_id] ?? null;

                $manifestShipment->update(['condition' => $condition, 'condition_notes' => $note, 'scanned_at' => now()]);

                if ($condition !== 'received') {
                    $hasDiscrepancy = true;
                }

                $statusKey = $statusMap[$condition] ?? $statusMap['received'];
                $scanStatus = $scanStatuses->get($statusKey);
                $shipment = $manifestShipment->shipment;

                ScanEvent::create([
                    'shipment_id' => $shipment->id,
                    'status' => $statusKey,
                    'hub_id' => $manifest->destination_hub_id,
                    'outlet_id' => $manifest->destination_outlet_id,
                    'handled_by' => $user->id,
                    'scanned_at' => now(),
                ]);

                $shipmentUpdate = ['current_status' => $statusKey];
                if ($condition === 'received') {
                    $shipmentUpdate['current_hub_id'] = $manifest->destination_hub_id;
                    $shipmentUpdate['current_outlet_id'] = $manifest->destination_outlet_id;
                }
                $shipment->update($shipmentUpdate);

                $this->notifyIfConfigured($shipment, $scanStatus);
            }

            $manifest->update([
                'status' => 'received',
                'received_by_user_id' => $user->id,
                'received_at' => now(),
                'discrepancy_notes' => $hasDiscrepancy ? 'One or more shipments were not received in full/good condition — see per-shipment notes.' : null,
            ]);
        });
    }

    private function notifyIfConfigured(Shipment $shipment, ?ScanStatus $scanStatus): void
    {
        if (! $scanStatus?->notify_customer) {
            return;
        }

        $recipients = array_filter([$shipment->receiver_email, $shipment->sender_email]);
        if (! empty($recipients)) {
            Mail::to($recipients)->queue(new ShipmentStatusUpdated($shipment, $scanStatus->label));
        }
    }
}
