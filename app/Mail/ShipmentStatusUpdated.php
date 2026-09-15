<?php

namespace App\Mail;

use App\Models\Shipment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Sent only for statuses staff have explicitly marked notify-worthy
 * (ScanStatus::notify_customer) — never every single scan, which
 * would be spam. Queued (implements ShouldQueue below) so a slow or
 * failing mail send never blocks the rider's own scan API response —
 * the scan itself always succeeds and returns immediately regardless
 * of whether the email goes out a second later or fails entirely.
 */
class ShipmentStatusUpdated extends Mailable implements \Illuminate\Contracts\Queue\ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Shipment $shipment, public string $statusLabel)
    {
    }

    public function build(): self
    {
        return $this
            ->subject("{$this->shipment->tracking_number} — {$this->statusLabel}")
            ->markdown('emails.shipment-status-updated');
    }
}
