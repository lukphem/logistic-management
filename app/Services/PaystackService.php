<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;

/**
 * Redirect-flow integration (https://paystack.com/docs/payments/accept-payments/#redirect)
 * rather than the Popup/InlineJS flow — this app is server-rendered
 * Blade, not a JS-driven SPA, and the redirect flow needs no frontend
 * JavaScript at all: initialize server-side, redirect the browser to
 * Paystack's own checkout page, Paystack redirects back to a
 * callback_url with the transaction reference, verify server-side.
 *
 * Amount is always in kobo (NGN's subunit) per Paystack's API
 * requirement, so this service is the one place that multiplication/
 * division happens — callers always pass/receive naira, never kobo,
 * so that conversion can't accidentally happen twice or not at all
 * somewhere else in the codebase.
 */
class PaystackService
{
    private const BASE_URL = 'https://api.paystack.co';

    public function isConfigured(): bool
    {
        $settings = Setting::current();

        return $settings->paystack_enabled
            && ! empty($settings->paystack_public_key)
            && ! empty($settings->paystack_secret_key);
    }

    /**
     * @return array{success: bool, authorization_url?: string, reference?: string, message?: string}
     */
    public function initializeTransaction(string $email, float $amountNaira, string $reference, string $callbackUrl, array $metadata = []): array
    {
        $settings = Setting::current();

        if (! $this->isConfigured()) {
            return ['success' => false, 'message' => 'Paystack is not configured — set it up under Settings → Payments first.'];
        }

        $response = Http::withToken($settings->paystack_secret_key)
            ->post(self::BASE_URL . '/transaction/initialize', [
                'email' => $email,
                'amount' => (int) round($amountNaira * 100), // naira -> kobo
                'reference' => $reference,
                'callback_url' => $callbackUrl,
                'metadata' => $metadata,
            ]);

        $body = $response->json();

        if (! $response->successful() || empty($body['status'])) {
            return ['success' => false, 'message' => $body['message'] ?? 'Could not start the Paystack transaction — try again.'];
        }

        return [
            'success' => true,
            'authorization_url' => $body['data']['authorization_url'],
            'reference' => $body['data']['reference'],
        ];
    }

    /**
     * The reference alone (from the callback_url's own query string)
     * is never trusted as proof of payment — visiting the callback
     * URL doesn't prove a transaction actually succeeded, only that
     * the browser was redirected there. This is what actually
     * confirms it, straight from Paystack's own records.
     *
     * @return array{success: bool, paid: bool, amount_naira?: float, message?: string}
     */
    public function verifyTransaction(string $reference): array
    {
        $settings = Setting::current();

        if (! $this->isConfigured()) {
            return ['success' => false, 'paid' => false, 'message' => 'Paystack is not configured.'];
        }

        $response = Http::withToken($settings->paystack_secret_key)
            ->get(self::BASE_URL . '/transaction/verify/' . rawurlencode($reference));

        $body = $response->json();

        if (! $response->successful() || empty($body['status'])) {
            return ['success' => false, 'paid' => false, 'message' => $body['message'] ?? 'Could not verify this transaction.'];
        }

        $paid = ($body['data']['status'] ?? null) === 'success';

        return [
            'success' => true,
            'paid' => $paid,
            'amount_naira' => isset($body['data']['amount']) ? $body['data']['amount'] / 100 : null,
        ];
    }

    /**
     * HMAC SHA512 of the raw request body, signed with the secret
     * key — https://paystack.com/docs/payments/webhooks/#signature-validation.
     * Must be checked against the RAW body, not a re-encoded version
     * of the parsed JSON, since re-encoding can change whitespace/key
     * order and produce a different hash even for identical data.
     */
    public function verifyWebhookSignature(string $rawBody, ?string $signatureHeader): bool
    {
        $settings = Setting::current();

        if (! $signatureHeader || empty($settings->paystack_secret_key)) {
            return false;
        }

        $expected = hash_hmac('sha512', $rawBody, $settings->paystack_secret_key);

        return hash_equals($expected, $signatureHeader);
    }

    /**
     * Every path that can mark a shipment paid — the browser callback,
     * the webhook, and the scheduled requery command
     * (app/Console/Commands/RequeryPendingPayments.php) — all funnel
     * through this one method rather than each doing their own
     * read-then-update. Locked inside a transaction so that if two of
     * those paths fire within milliseconds of each other for the same
     * reference (a real possibility — Paystack sends the webhook
     * almost immediately, often before the browser has even finished
     * redirecting back to the callback_url), the second one to reach
     * this method waits for the first's lock to release, then sees the
     * row already marked paid and does nothing — never a double
     * update, never two "payment confirmed" notifications for the same
     * payment.
     *
     * Returns whether this call is the one that actually applied the
     * update (false if it was already paid, or the amount didn't
     * match) — callers use this to word their message correctly rather
     * than always claiming credit for confirming the payment.
     */
    public function markShipmentPaidIfDue(string $reference, int $paidKobo, bool $logMismatch = false): bool
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($reference, $paidKobo, $logMismatch) {
            $shipment = \App\Models\Shipment::where('payment_reference', $reference)->lockForUpdate()->first();

            if (! $shipment || $shipment->payment_status === 'paid') {
                return false;
            }

            $expectedKobo = (int) round(((float) $shipment->total_amount) * 100);

            if ($paidKobo < $expectedKobo) {
                if ($logMismatch) {
                    \Illuminate\Support\Facades\Log::warning("Paystack: amount mismatch for {$reference} — paid {$paidKobo} kobo, expected {$expectedKobo} kobo.");
                }

                return false;
            }

            $shipment->update(['payment_status' => 'paid', 'paid_at' => now()]);

            return true;
        });
    }

    /**
     * The settlement equivalent of markShipmentPaidIfDue() above — same
     * locking reasoning, same funnel-every-path-through-one-place
     * approach. The cascade from "settlement paid" to "every shipment
     * in it paid" happens inside the same locked transaction, so a
     * concurrent read of any of those shipments' payment_status either
     * sees the pre-settlement state or the fully-cascaded post-
     * settlement state — never a half-applied cascade.
     */
    public function markSettlementPaidIfDue(string $reference, int $paidKobo, bool $logMismatch = false): bool
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($reference, $paidKobo, $logMismatch) {
            $settlement = \App\Models\CashSettlement::where('payment_reference', $reference)->lockForUpdate()->first();

            if (! $settlement || $settlement->status === 'paid') {
                return false;
            }

            $expectedKobo = (int) round(((float) $settlement->total_amount) * 100);

            if ($paidKobo < $expectedKobo) {
                if ($logMismatch) {
                    \Illuminate\Support\Facades\Log::warning("Paystack: settlement amount mismatch for {$reference} — paid {$paidKobo} kobo, expected {$expectedKobo} kobo.");
                }

                return false;
            }

            $settlement->update(['status' => 'paid', 'paid_at' => now()]);
            $settlement->shipments()->update(['payment_status' => 'paid', 'paid_at' => now()]);

            return true;
        });
    }

    /**
     * The wallet-funding equivalent of the two above. A wallet's
     * balance is only ever actually credited here, once payment is
     * confirmed — never at initializeTransaction() time — so a
     * funding attempt that's initialized but never completed leaves
     * the wallet exactly as it was before.
     */
    public function markWalletFundedIfDue(string $reference, int $paidKobo, bool $logMismatch = false): bool
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($reference, $paidKobo, $logMismatch) {
            $funding = \App\Models\AccountWalletFunding::where('payment_reference', $reference)->lockForUpdate()->first();

            if (! $funding || $funding->status === 'paid') {
                return false;
            }

            $expectedKobo = (int) round(((float) $funding->amount) * 100);

            if ($paidKobo < $expectedKobo) {
                if ($logMismatch) {
                    \Illuminate\Support\Facades\Log::warning("Paystack: wallet funding amount mismatch for {$reference} — paid {$paidKobo} kobo, expected {$expectedKobo} kobo.");
                }

                return false;
            }

            $funding->update(['status' => 'paid', 'paid_at' => now()]);

            $funding->wallet->credit(
                amount: (float) $funding->amount,
                fundingMethod: 'paystack',
                reference: $reference,
                description: 'Paystack wallet funding',
                recordedByUserId: $funding->initiated_by_user_id,
            );

            return true;
        });
    }
}
