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
 * handling, and the two safety rules below) applies identically
 * whether the scan came from a rider's mobile app or a hub/counter
 * staff member using the web Operational Scans module. One behavior,
 * two front doors.
 *
 * Two hard rules enforced here, not just suggested by the UI:
 * 1. A shipment already at a terminal status (ScanStatus::is_terminal
 *    — delivered/returned/cancelled by default) can never be scanned
 *    again for anything. Once it's out of the company's hands, it's
 *    out — no further movement makes sense to record.
 * 2. A shipment still sitting at "booked" (nothing has physically
 *    touched it yet) can only move via a status staff have marked
 *    is_first_touch (Picked Up or Dropped Off by default) — it has
 *    to genuinely be in hand before an arrival/departure/delivery/
 *    exception scan means anything.
 */
class ScanService
{
    /**
     * @param array{shipment_id: int, status: string, hub_id?: ?int, outlet_id?: ?int, unit_id?: ?int, destination_hub_id?: ?int, destination_unit_id?: ?int, latitude?: ?float, longitude?: ?float, photo_path?: ?string, signature_path?: ?string, receiver_name?: ?string} $data
     *
     * @throws \RuntimeException if the shipment is terminal, or still
     *         booked and the target status isn't a first-touch one
     */
    public function recordScan(array $data, int $handledByUserId): ScanEvent
    {
        $shipment = Shipment::findOrFail($data['shipment_id']);

        $currentStatus = ScanStatus::where('key', $shipment->current_status)->first();
        if ($currentStatus?->is_terminal) {
            throw new \RuntimeException("{$shipment->tracking_number} is already {$currentStatus->label} and is considered out of the company's hands — it can't be scanned again.");
        }

        $newStatus = ScanStatus::where('key', $data['status'])->first();

        // "Never scanned before" is read off current_status still
        // being exactly 'booked' — the value ShipmentController::store()
        // sets at creation and nothing else ever sets again, so this
        // reliably means "nothing has touched this shipment yet."
        if ($shipment->current_status === 'booked' && ! $newStatus?->is_first_touch) {
            throw new \RuntimeException("{$shipment->tracking_number} hasn't been picked up or dropped off yet — it needs a Pickup or Drop-off scan before anything else.");
        }

        // Pickup and Drop-off both mean exactly the same underlying
        // fact — the shipment is now in the company's custody — so
        // once either has happened, doing the other means nothing new
        // and is rejected rather than silently re-recorded. This is
        // the reverse of the check above: that one guards against
        // skipping first-touch entirely, this one guards against
        // repeating it.
        if ($newStatus?->is_first_touch && $shipment->current_status !== 'booked') {
            throw new \RuntimeException("{$shipment->tracking_number} is already in the company's custody — it's already been picked up or dropped off, so this can't be done again.");
        }

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
            'destination_hub_id' => $data['destination_hub_id'] ?? null,
            'destination_unit_id' => $data['destination_unit_id'] ?? null,
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'photo_path' => $data['photo_path'] ?? null,
            'signature_path' => $data['signature_path'] ?? null,
            'receiver_name' => $data['receiver_name'] ?? null,
            'handed_to_user_id' => $data['handed_to_user_id'] ?? null,
            'handled_by' => $handledByUserId,
            'scanned_at' => now(),
        ]);

        $shipmentUpdate = [
            'current_status' => $data['status'],
            'delivered_at' => $data['status'] === 'delivered' ? now() : $shipment->delivered_at,
        ];

        // The SLA clock starts here, not at booking — a shipment
        // that's only been booked and never actually picked up or
        // dropped off is still outside the company's possession
        // entirely, so a promised delivery date calculated from the
        // moment of booking was never really accurate. This is the
        // exact same "first touch, coming from booked" condition
        // already checked above to gate which scans are even allowed
        // first, so it fires exactly once per shipment, the first
        // time it genuinely enters custody.
        if ($newStatus?->is_first_touch && $shipment->current_status === 'booked' && $shipment->transit_days) {
            $shipmentUpdate['promised_delivery_at'] = now()->addDays($shipment->transit_days);
        }

        if ($hubId) {
            $shipmentUpdate['current_hub_id'] = $hubId;
            $shipmentUpdate['current_outlet_id'] = $outletId; // null clears it when scanning at the hub itself
            // A unit-to-unit transfer (destination_unit_id) moves the
            // shipment straight to the receiving unit — the two units
            // are in the same physical hub, so this is a direct
            // handoff, not something that waits for a separate
            // arrival scan. Short of that, unit_id is wherever this
            // scan itself physically happened (a unit-scoped staff
            // member receiving/handling it at their own unit). With
            // neither, the shipment's unit-level location is no
            // longer known and is cleared rather than left stale.
            $shipmentUpdate['current_unit_id'] = $data['destination_unit_id'] ?? $data['unit_id'] ?? null;
        }

        // Who's actually carrying the shipment right now — kept in
        // sync with the departure/handover that just happened, not
        // just logged on the scan event itself.
        if (! empty($data['handed_to_user_id'])) {
            $shipmentUpdate['assigned_rider_id'] = $data['handed_to_user_id'];
        }

        // Counts against the shipment's own client account's Maximum
        // Delivery Attempt limit — not every scan status qualifies,
        // only ones staff have explicitly marked as representing an
        // attempt (ScanStatus::is_delivery_attempt), since statuses
        // are fully staff-configurable and there's no reliable way to
        // infer this from a label alone.
        if ($newStatus?->is_delivery_attempt) {
            $shipmentUpdate['delivery_attempts_count'] = $shipment->delivery_attempts_count + 1;

            $maxAttempts = $shipment->client_account_id
                ? ClientAccount::find($shipment->client_account_id)?->effectiveMaximumDeliveryAttempts()
                : null;

            if ($maxAttempts && $shipmentUpdate['delivery_attempts_count'] >= $maxAttempts && ! $shipment->delivery_attempts_exceeded_at) {
                $shipmentUpdate['delivery_attempts_exceeded_at'] = now();
            }
        }

        $shipment->update($shipmentUpdate);

        $this->notifyIfConfigured($shipment, $newStatus);

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
