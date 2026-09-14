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
}
