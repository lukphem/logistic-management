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
        // callback/webhook/requery only ever have the reference to
        // work with, so this is what lets any of them find their way
        // back to the right shipment.
        $shipment->update(['payment_reference' => $result['reference']]);

        return redirect()->away($result['authorization_url']);
    }

    /**
     * Same idea as pay() above, but for a whole batch of cash-collected
     * shipments at once — one Paystack transaction for the settlement's
     * total_amount, not one per shipment. The shipments themselves
     * aren't touched here; they only become "paid" once this batch's
     * own payment is confirmed (see PaystackService::markSettlementPaidIfDue()),
     * so a settlement that's initialized but never completed leaves
     * every shipment in it exactly as outstanding as before.
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
     * redirected here without a real charge succeeding — an
     * "incomplete callback" in exactly this sense). Always
     * re-verified against Paystack's own records before anything is
     * ever marked paid here. Reference prefix decides which of the two
     * payment types (single shipment vs settlement batch) this is.
     *
     * If the person never makes it back here at all — closed tab, lost
     * connection, browser crash — nothing in this method ever runs,
     * and that's fine: the webhook (independent of this endpoint
     * entirely) and the scheduled requery command
     * (app/Console/Commands/RequeryPendingPayments.php) are what
     * actually guarantee the payment still gets confirmed even when
     * this callback never fires at all.
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

        if ($shipment->payment_status === 'paid') {
            return redirect()->route('shipments.show', $shipment)->with('status', 'Payment already confirmed for ' . $shipment->tracking_number . '.');
        }

        $result = $this->paystack->verifyTransaction($reference);

        if (! $result['success']) {
            return redirect()->route('shipments.show', $shipment)->withErrors(['payment' => $result['message']]);
        }

        if ($result['paid']) {
            $applied = $this->paystack->markShipmentPaidIfDue($reference, (int) round(((float) $shipment->total_amount) * 100));

            return redirect()->route('shipments.show', $shipment)->with('status', $applied
                ? 'Payment confirmed for ' . $shipment->tracking_number . '.'
                : 'Payment already confirmed for ' . $shipment->tracking_number . '.');
        }

        $shipment->update(['payment_status' => 'failed']);

        return redirect()->route('shipments.show', $shipment)->withErrors(['payment' => 'Payment was not successful.']);
    }

    private function handleSettlementCallback(string $reference): RedirectResponse
    {
        $settlement = CashSettlement::where('payment_reference', $reference)->first();

        if (! $settlement) {
            return redirect()->route('reconciliation.index')->withErrors(['payment' => 'Could not match this payment to a settlement.']);
        }

        if ($settlement->status === 'paid') {
            return redirect()->route('reconciliation.index')->with('status', 'This settlement was already confirmed.');
        }

        $result = $this->paystack->verifyTransaction($reference);

        if (! $result['success']) {
            return redirect()->route('reconciliation.index')->withErrors(['payment' => $result['message']]);
        }

        if ($result['paid']) {
            $applied = $this->paystack->markSettlementPaidIfDue($reference, (int) round(((float) $settlement->total_amount) * 100));

            return redirect()->route('reconciliation.index')->with('status', $applied
                ? 'Settlement of ' . number_format($settlement->total_amount, 2) . ' confirmed — ' . $settlement->shipments()->count() . ' shipment(s) marked paid.'
                : 'This settlement was already confirmed.');
        }

        $settlement->update(['status' => 'failed']);

        return redirect()->route('reconciliation.index')->withErrors(['payment' => 'Settlement payment was not successful.']);
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
                $this->paystack->markSettlementPaidIfDue($reference, $paidKobo, logMismatch: true);
            } elseif ($reference) {
                $this->paystack->markShipmentPaidIfDue($reference, $paidKobo, logMismatch: true);
            }
        }

        return response('OK', 200);
    }

    /**
     * A manual, on-demand version of the same requery the scheduled
     * command runs automatically — for when someone doesn't want to
     * wait for the next scheduled pass (RequeryPendingPayments runs
     * every 10 minutes) and just wants to know right now whether a
     * payment that never confirmed actually went through.
     */
    public function checkStatus(Request $request): RedirectResponse
    {
        $reference = $request->input('reference');
        $redirectRoute = str_starts_with((string) $reference, 'SETTLE-') ? 'reconciliation.index' : 'shipments.index';

        if (! $reference) {
            return redirect()->route($redirectRoute)->withErrors(['payment' => 'No reference to check.']);
        }

        $result = $this->paystack->verifyTransaction($reference);

        if (! $result['success']) {
            return redirect()->route($redirectRoute)->withErrors(['payment' => $result['message']]);
        }

        if (str_starts_with($reference, 'SETTLE-')) {
            $settlement = CashSettlement::where('payment_reference', $reference)->first();

            if (! $settlement) {
                return redirect()->route('reconciliation.index')->withErrors(['payment' => 'Could not find this settlement.']);
            }

            if ($result['paid']) {
                $this->paystack->markSettlementPaidIfDue($reference, (int) round(((float) $settlement->total_amount) * 100));

                return redirect()->route('reconciliation.index')->with('status', 'Confirmed — settlement is paid.');
            }

            return redirect()->route('reconciliation.index')->with('status', 'Checked — this settlement has not been paid yet.');
        }

        $shipment = Shipment::where('payment_reference', $reference)->first();

        if (! $shipment) {
            return redirect()->route('shipments.index')->withErrors(['payment' => 'Could not find this shipment.']);
        }

        if ($result['paid']) {
            $this->paystack->markShipmentPaidIfDue($reference, (int) round(((float) $shipment->total_amount) * 100));

            return redirect()->route('shipments.show', $shipment)->with('status', 'Confirmed — payment is paid.');
        }

        return redirect()->route('shipments.show', $shipment)->with('status', 'Checked — payment has not gone through yet.');
    }
}
