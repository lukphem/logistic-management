<?php

namespace App\Services;

use App\Mail\ShipmentStatusUpdated;
use App\Models\ClientAccount;
use App\Models\ScanEvent;
use App\Models\ScanStatus;
use App\Models\Shipment;
use Illuminate\Support\Facades\Mail;

/**
 * The single place a shipment scan actually gets recorded — extracted
 * from what used to be inline in RiderController::scan() so the same
 * logic (attempt-limit counting, notify_customer emails, delivered_at
 * handling) applies identically whether the scan came from a rider's
 * mobile app or a hub/counter staff member using the web Operational
 * Scans module. One behavior, two front doors.
 */
class ScanService
{
    /**
     * @param array{shipment_id: int, status: string, hub_id?: ?int, outlet_id?: ?int, latitude?: ?float, longitude?: ?float, photo_path?: ?string, signature_path?: ?string} $data
     */
    public function recordScan(array $data, int $handledByUserId): ScanEvent
    {
        $shipment = Shipment::findOrFail($data['shipment_id']);

        $hubId = $data['hub_id'] ?? null;
        $outletId = $data['outlet_id'] ?? null;

        if ($outletId) {
            $outlet = \App\Models\Outlet::find($outletId);
            $hubId = $outlet?->hub_id ?? $hubId;
        }

        $scanEvent = ScanEvent::create([
            'shipment_id' => $shipment->id,
            'status' => $data['status'],
            'hub_id' => $hubId,
            'outlet_id' => $outletId,
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'photo_path' => $data['photo_path'] ?? null,
            'signature_path' => $data['signature_path'] ?? null,
            'handled_by' => $handledByUserId,
            'scanned_at' => now(),
        ]);

        $shipmentUpdate = [
            'current_status' => $data['status'],
            'delivered_at' => $data['status'] === 'delivered' ? now() : $shipment->delivered_at,
        ];

        if ($hubId) {
            $shipmentUpdate['current_hub_id'] = $hubId;
            $shipmentUpdate['current_outlet_id'] = $outletId; // null clears it when scanning at the hub itself
        }

        // Counts against the shipment's own client account's Maximum
        // Delivery Attempt limit — not every scan status qualifies,
        // only ones staff have explicitly marked as representing an
        // attempt (ScanStatus::is_delivery_attempt), since statuses
        // are fully staff-configurable and there's no reliable way to
        // infer this from a label alone.
        $scanStatus = ScanStatus::where('key', $data['status'])->first();

        if ($scanStatus?->is_delivery_attempt) {
            $shipmentUpdate['delivery_attempts_count'] = $shipment->delivery_attempts_count + 1;

            $maxAttempts = $shipment->client_account_id
                ? ClientAccount::find($shipment->client_account_id)?->effectiveMaximumDeliveryAttempts()
                : null;

            if ($maxAttempts && $shipmentUpdate['delivery_attempts_count'] >= $maxAttempts && ! $shipment->delivery_attempts_exceeded_at) {
                $shipmentUpdate['delivery_attempts_exceeded_at'] = now();
            }
        }

        $shipment->update($shipmentUpdate);

        $this->notifyIfConfigured($shipment, $scanStatus);

        return $scanEvent;
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
