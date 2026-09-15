<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\CashSettlement;
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

        // SHIP- prefix distinguishes this from a settlement batch
        // reference (SETTLE-) at the callback/webhook, since both land
        // on the same two endpoints and need different handling.
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
     * Same idea as pay() above, but for a whole batch of cash-collected
     * shipments at once — one Paystack transaction for the settlement's
     * total_amount, not one per shipment. The shipments themselves
     * aren't touched here; they only become "paid" once this batch's
     * own payment is confirmed (see markSettlementPaid()), so a
     * settlement that's initialized but never completed leaves every
     * shipment in it exactly as outstanding as before.
     */
    public function paySettlement(CashSettlement $settlement): RedirectResponse
    {
        abort_unless($settlement->initiated_by_user_id === auth()->id(), 403, "This settlement isn't yours to pay.");

        if ($settlement->status === 'paid') {
            return redirect()->route('reconciliation.index')->with('status', 'This settlement is already paid.');
        }

        if (! $this->paystack->isConfigured()) {
            return redirect()->route('reconciliation.index')->withErrors(['payment' => 'Paystack is not set up yet — configure it under Settings → Payments first.']);
        }

        $reference = 'SETTLE-' . $settlement->id . '-' . now()->format('YmdHis') . '-' . substr(bin2hex(random_bytes(3)), 0, 6);

        $result = $this->paystack->initializeTransaction(
            email: auth()->user()->email,
            amountNaira: (float) $settlement->total_amount,
            reference: $reference,
            callbackUrl: route('payments.callback'),
            metadata: ['cash_settlement_id' => $settlement->id, 'shipment_count' => $settlement->shipments()->count()],
        );

        if (! $result['success']) {
            return redirect()->route('reconciliation.index')->withErrors(['payment' => $result['message']]);
        }

        $settlement->update(['payment_reference' => $result['reference']]);

        return redirect()->away($result['authorization_url']);
    }

    /**
     * Where Paystack sends the browser back after checkout — never
     * trusted as proof of payment on its own (the person could close
     * the tab, lose connection, or the browser could simply be
     * redirected here without a real charge succeeding). Always
     * re-verified against Paystack's own records before anything is
     * ever marked paid here. Reference prefix decides which of the two
     * payment types (single shipment vs settlement batch) this is.
     */
    public function callback(Request $request): RedirectResponse
    {
        $reference = $request->query('reference');

        if (! $reference) {
            return redirect()->route('shipments.index')->withErrors(['payment' => 'No payment reference was returned.']);
        }

        if (str_starts_with($reference, 'SETTLE-')) {
            return $this->handleSettlementCallback($reference);
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

    private function handleSettlementCallback(string $reference): RedirectResponse
    {
        $settlement = CashSettlement::where('payment_reference', $reference)->first();

        if (! $settlement) {
            return redirect()->route('reconciliation.index')->withErrors(['payment' => 'Could not match this payment to a settlement.']);
        }

        $result = $this->paystack->verifyTransaction($reference);

        if (! $result['success']) {
            return redirect()->route('reconciliation.index')->withErrors(['payment' => $result['message']]);
        }

        if ($result['paid'] && $settlement->status !== 'paid') {
            $this->markSettlementPaid($settlement);

            return redirect()->route('reconciliation.index')->with('status', 'Settlement of ' . number_format($settlement->total_amount, 2) . ' confirmed — ' . $settlement->shipments()->count() . ' shipment(s) marked paid.');
        }

        if (! $result['paid']) {
            $settlement->update(['status' => 'failed']);

            return redirect()->route('reconciliation.index')->withErrors(['payment' => 'Settlement payment was not successful.']);
        }

        return redirect()->route('reconciliation.index')->with('status', 'This settlement was already confirmed.');
    }

    /**
     * The authoritative source of truth for payment status — the
     * callback above is a convenience for whoever just paid, but this
     * is what confirms it independent of whether they ever made it
     * back to the callback URL at all. Verifies the signature before
     * touching anything, per
     * https://paystack.com/docs/payments/webhooks/#signature-validation,
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
            $paidKobo = (int) ($payload['data']['amount'] ?? 0);

            if ($reference && str_starts_with($reference, 'SETTLE-')) {
                $this->webhookSettlement($reference, $paidKobo);
            } elseif ($reference) {
                $this->webhookShipment($reference, $paidKobo);
            }
        }

        return response('OK', 200);
    }

    private function webhookShipment(string $reference, int $paidKobo): void
    {
        $shipment = Shipment::where('payment_reference', $reference)->first();

        if (! $shipment || $shipment->payment_status === 'paid') {
            return;
        }

        // Same amount check the docs call out explicitly — never mark
        // paid on reference match alone without confirming the amount
        // actually matches what was owed.
        $expectedKobo = (int) round(((float) $shipment->total_amount) * 100);

        if ($paidKobo >= $expectedKobo) {
            $shipment->update(['payment_status' => 'paid', 'paid_at' => now()]);
        } else {
            Log::warning("Paystack webhook: amount mismatch for {$reference} — paid {$paidKobo} kobo, expected {$expectedKobo} kobo.");
        }
    }

    private function webhookSettlement(string $reference, int $paidKobo): void
    {
        $settlement = CashSettlement::where('payment_reference', $reference)->first();

        if (! $settlement || $settlement->status === 'paid') {
            return;
        }

        $expectedKobo = (int) round(((float) $settlement->total_amount) * 100);

        if ($paidKobo >= $expectedKobo) {
            $this->markSettlementPaid($settlement);
        } else {
            Log::warning("Paystack webhook: settlement amount mismatch for {$reference} — paid {$paidKobo} kobo, expected {$expectedKobo} kobo.");
        }
    }

    /**
     * The cascade from "settlement batch paid" to "every shipment in
     * it paid" — the single place this happens, called from both the
     * callback (person's own browser) and the webhook (authoritative,
     * independent of whether they ever got back to the callback).
     * Guarded by the settlement's own status !== 'paid' check at both
     * call sites, so this never double-runs even if both paths fire
     * for the same settlement.
     */
    private function markSettlementPaid(CashSettlement $settlement): void
    {
        $settlement->update(['status' => 'paid', 'paid_at' => now()]);

        $settlement->shipments()->update(['payment_status' => 'paid', 'paid_at' => now()]);
    }
}
