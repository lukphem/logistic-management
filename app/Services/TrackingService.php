<?php

namespace App\Services;

use App\Models\Manifest;
use App\Models\ManifestTrip;
use App\Models\Shipment;
use Illuminate\Support\Collection;

/**
 * The lookup logic shared by the public tracking pages and the
 * separate staff-facing ones — resolving a number to a shipment/
 * manifest/trip, and building the "last scan" summary (date, and a
 * location that falls back to the handling staff member's own
 * assigned hub/outlet when the scan itself didn't record one, since
 * not every scan carries GPS/location context and a blank "last
 * known location" is worse than a reasonable approximation).
 */
class TrackingService
{
    public function resolveKind(string $number): string
    {
        if (str_starts_with($number, 'MAN-')) {
            return 'manifest';
        }

        if (str_starts_with($number, 'TRIP-')) {
            return 'trip';
        }

        return 'shipment';
    }

    public function findShipment(string $trackingNumber, bool $withStaffDetail = false): ?Shipment
    {
        return Shipment::where('tracking_number', $trackingNumber)
            ->with(array_filter([
                'scanEvents' => fn ($q) => $q->orderBy('scanned_at'),
                'scanEvents.hub',
                'scanEvents.outlet',
                'scanEvents.destinationHub',
                $withStaffDetail ? 'scanEvents.handler' : null,
                $withStaffDetail ? 'scanEvents.handler.hub' : null,
                $withStaffDetail ? 'scanEvents.handler.outlet' : null,
                $withStaffDetail ? 'scanEvents.handedTo' : null,
                'originCity',
                'destinationCity',
                'serviceType',
            ]))
            ->first();
    }

    public function findManifest(string $manifestNumber): ?Manifest
    {
        return Manifest::where('manifest_number', $manifestNumber)
            ->with(['trip', 'destinationHub', 'destinationOutlet', 'manifestShipments.shipment'])
            ->first();
    }

    public function findTrip(string $tripNumber): ?ManifestTrip
    {
        return ManifestTrip::where('trip_number', $tripNumber)
            ->with(['originHub', 'originOutlet', 'manifests.destinationHub', 'manifests.manifestShipments.shipment'])
            ->first();
    }

    public function shipmentsOnTrip(ManifestTrip $trip): Collection
    {
        return $trip->manifests->flatMap(fn ($m) => $m->manifestShipments->pluck('shipment'))->filter()->unique('id');
    }

    /**
     * Resolves ANY number a scan input might receive — a shipment's
     * own tracking number, or a manifest/trip batch number — into the
     * full list of shipments it represents. A tracking number
     * resolves to itself (one shipment); a manifest or trip number
     * pulls in every shipment riding on it, so scanning a batch
     * number loads the whole batch for verification in one action
     * instead of the individual pieces one at a time.
     *
     * @return array{found: bool, message?: string, shipments: Collection<int, Shipment>}
     */
    public function resolveShipmentsForScan(string $number): array
    {
        $kind = $this->resolveKind($number);

        if ($kind === 'manifest') {
            $manifest = $this->findManifest($number);

            if (! $manifest) {
                return ['found' => false, 'message' => "No manifest with that number.", 'shipments' => collect()];
            }

            $shipments = $manifest->manifestShipments->pluck('shipment')->filter();

            if ($shipments->isEmpty()) {
                return ['found' => false, 'message' => "Manifest {$number} has no shipments on it.", 'shipments' => collect()];
            }

            return ['found' => true, 'shipments' => $shipments];
        }

        if ($kind === 'trip') {
            $trip = $this->findTrip($number);

            if (! $trip) {
                return ['found' => false, 'message' => "No trip with that number.", 'shipments' => collect()];
            }

            $shipments = $this->shipmentsOnTrip($trip);

            if ($shipments->isEmpty()) {
                return ['found' => false, 'message' => "Trip {$number} has no shipments on it.", 'shipments' => collect()];
            }

            return ['found' => true, 'shipments' => $shipments];
        }

        $shipment = $this->findShipment($number);

        if (! $shipment) {
            return ['found' => false, 'message' => "No shipment with that tracking number.", 'shipments' => collect()];
        }

        return ['found' => true, 'shipments' => collect([$shipment])];
    }

    /**
     * The verification card every scan type shows before submitting
     * — tracking number, who it's registered to, piece count, current
     * status, and when/where it was last scanned. Same shape whether
     * it came from a single tracking number or one entry in a
     * manifest/trip batch, so the confirmation list looks identical
     * either way.
     */
    public function verificationSummary(Shipment $shipment, bool $withStaffFallback = false): array
    {
        $lastScan = $this->lastScanSummary($shipment, $withStaffFallback);

        return [
            'id' => $shipment->id,
            'tracking_number' => $shipment->tracking_number,
            'receiver_name' => $shipment->receiver_name,
            'receiver_phone' => $shipment->receiver_phone,
            'destination_address' => $shipment->destination_address,
            'quantity' => $shipment->quantity,
            'weight_kg' => $shipment->weight_kg,
            'origin' => $shipment->originCity?->name,
            'destination' => $shipment->destinationCity?->name,
            'current_status' => $shipment->current_status,
            'last_scan_date' => $lastScan['date'] ?? null,
            'last_scan_location' => $lastScan['location'] ?? null,
        ];
    }

    /**
     * @return array{date: ?\Carbon\Carbon, location: ?string}|null
     */
    public function lastScanSummary(Shipment $shipment, bool $withStaffFallback = false): ?array
    {
        $lastEvent = $shipment->scanEvents->last();

        if (! $lastEvent) {
            return null;
        }

        $location = $lastEvent->outlet?->name ?? $lastEvent->hub?->name;

        // No location on the scan itself — fall back to wherever the
        // staff member who performed it is assigned, so "last known
        // location" is a reasonable approximation rather than blank.
        // Only offered when explicitly asked for, since inferring a
        // location from a person is a step removed from the scan
        // actually recording one.
        if (! $location && $withStaffFallback && $lastEvent->handler) {
            $location = $lastEvent->handler->outlet?->name ?? $lastEvent->handler->hub?->name;
        }

        return ['date' => $lastEvent->scanned_at, 'location' => $location];
    }
}
