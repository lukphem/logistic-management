<?php

namespace App\Http\Controllers\Web;

use App\Models\Setting;
use App\Models\Shipment;
use App\Services\PaystackService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function __construct(private PaystackService $paystack)
    {
    }

    /**
     * Starts a Paystack transaction for one shipment's own total_amount
     * and redirects the browser straight to Paystack's checkout page —
     * the redirect flow, not the Popup/InlineJS one, since this app has
     * no reason to add frontend JS just for this. A shipment already
     * marked paid can't be paid again (no double-charging a shipment
     * that's already settled).
     */
    public function pay(Shipment $shipment): RedirectResponse
    {
        abort_unless(auth()->user()->canAccessShipment($shipment), 403, "This shipment isn't somewhere you have access to.");

        if ($shipment->payment_status === 'paid') {
            return redirect()->route('shipments.show', $shipment)->with('status', 'This shipment is already marked as paid.');
        }

        if (! $this->paystack->isConfigured()) {
            return redirect()->route('shipments.show', $shipment)->withErrors(['payment' => 'Paystack is not set up yet — configure it under Settings → Payments first.']);
        }

        // Paystack reference must be unique per attempt, not per
        // shipment — re-trying a failed/abandoned payment on the same
        // shipment needs a fresh reference, since Paystack rejects a
        // reused one outright.
        $reference = 'SHIP-' . $shipment->id . '-' . now()->format('YmdHis') . '-' . substr(bin2hex(random_bytes(3)), 0, 6);

        $email = $shipment->clientUser?->email ?? $shipment->sender_email ?? 'no-reply@' . parse_url(config('app.url'), PHP_URL_HOST);

        $result = $this->paystack->initializeTransaction(
            email: $email,
            amountNaira: (float) $shipment->total_amount,
            reference: $reference,
            callbackUrl: route('payments.callback'),
            metadata: ['shipment_id' => $shipment->id, 'tracking_number' => $shipment->tracking_number],
        );

        if (! $result['success']) {
            return redirect()->route('shipments.show', $shipment)->withErrors(['payment' => $result['message']]);
        }

        // Reference stored immediately, before the redirect — the
        // callback/webhook only ever have the reference to work with,
        // so this is what lets either of them find their way back to
        // the right shipment.
        $shipment->update(['payment_reference' => $result['reference']]);

        return redirect()->away($result['authorization_url']);
    }

    /**
     * Where Paystack sends the browser back after checkout — never
     * trusted as proof of payment on its own (the person could close
     * the tab, lose connection, or the browser could simply be
     * redirected here without a real charge succeeding). Always
     * re-verified against Paystack's own records before the shipment
     * is ever marked paid here.
     */
    public function callback(Request $request): RedirectResponse
    {
        $reference = $request->query('reference');

        if (! $reference) {
            return redirect()->route('shipments.index')->withErrors(['payment' => 'No payment reference was returned.']);
        }

        $shipment = Shipment::where('payment_reference', $reference)->first();

        if (! $shipment) {
            return redirect()->route('shipments.index')->withErrors(['payment' => 'Could not match this payment to a shipment.']);
        }

        $result = $this->paystack->verifyTransaction($reference);

        if (! $result['success']) {
            return redirect()->route('shipments.show', $shipment)->withErrors(['payment' => $result['message']]);
        }

        if ($result['paid'] && $shipment->payment_status !== 'paid') {
            $shipment->update(['payment_status' => 'paid', 'paid_at' => now()]);

            return redirect()->route('shipments.show', $shipment)->with('status', 'Payment confirmed for ' . $shipment->tracking_number . '.');
        }

        if (! $result['paid']) {
            $shipment->update(['payment_status' => 'failed']);

            return redirect()->route('shipments.show', $shipment)->withErrors(['payment' => 'Payment was not successful.']);
        }

        return redirect()->route('shipments.show', $shipment)->with('status', 'Payment already confirmed for ' . $shipment->tracking_number . '.');
    }

    /**
     * The authoritative source of truth for payment status — the
     * callback above is a convenience for the person who just paid,
     * but this is what confirms it independent of whether they ever
     * made it back to the callback_url at all (closed tab, lost
     * connection, etc.). Verifies the signature before touching
     * anything, per https://paystack.com/docs/payments/webhooks/#verify-event-origin,
     * and always returns 200 quickly — a slow/failing response here
     * just means Paystack retries for up to 72 hours, which helps
     * nothing.
     */
    public function webhook(Request $request): Response
    {
        $rawBody = $request->getContent();
        $signature = $request->header('x-paystack-signature');

        if (! $this->paystack->verifyWebhookSignature($rawBody, $signature)) {
            Log::warning('Paystack webhook rejected — signature did not match.');

            return response('Invalid signature', 401);
        }

        $payload = json_decode($rawBody, true);

        if (($payload['event'] ?? null) === 'charge.success') {
            $reference = $payload['data']['reference'] ?? null;
            $shipment = $reference ? Shipment::where('payment_reference', $reference)->first() : null;

            if ($shipment && $shipment->payment_status !== 'paid') {
                // Same amount check the docs call out explicitly —
                // never mark paid on reference match alone without
                // confirming the amount actually matches what was owed.
                $paidKobo = (int) ($payload['data']['amount'] ?? 0);
                $expectedKobo = (int) round(((float) $shipment->total_amount) * 100);

                if ($paidKobo >= $expectedKobo) {
                    $shipment->update(['payment_status' => 'paid', 'paid_at' => now()]);
                } else {
                    Log::warning("Paystack webhook: amount mismatch for {$reference} — paid {$paidKobo} kobo, expected {$expectedKobo} kobo.");
                }
            }
        }

        return response('OK', 200);
    }
}
