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
