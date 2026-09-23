<?php

namespace App\Services;

use App\Models\ClientAccount;
use App\Models\ClientBillingProfile;
use App\Models\Shipment;

/**
 * The full pipeline behind creating one shipment — quote, price,
 * resolve the billing account, check it isn't suspended, resolve
 * how payment is collected, then actually create the record. This
 * used to live entirely inside ShipmentController::store(); pulled
 * out so the exact same, single tested implementation can be reused
 * by the web form, bulk CSV import, and (once built) the client
 * portal — no risk of the three drifting into subtly different
 * pricing or validation behavior over time.
 *
 * Takes plain array data in, the same shape validateShipment()
 * already produces — the caller is responsible for validating shape
 * and required fields before calling this; this service handles
 * everything downstream of "the data is well-formed."
 */
class ShipmentCreationService
{
    public function __construct(
        private PricingEngine $pricingEngine,
        private ShipmentPricingService $pricingService,
    ) {
    }

    /**
     * @throws \RuntimeException on anything that should stop the
     *         shipment from being created: an unresolvable account
     *         number, pricing unavailable for the given inputs, or a
     *         suspended account. The message is written to be shown
     *         directly to whoever submitted this shipment.
     */
    public function createShipment(array $data): Shipment
    {
        if (! empty($data['account_number'])) {
            $account = ClientAccount::where('account_number', $data['account_number'])->first();

            if (! $account) {
                throw new \RuntimeException("No client account found with number \"{$data['account_number']}\".");
            }

            $data['client_account_id'] = $account->id;
            $data['client_user_id'] = $account->client_user_id;
        }

        try {
            $quote = $this->pricingEngine->quote($data);
        } catch (PricingUnavailableException $e) {
            throw new \RuntimeException($e->getMessage());
        }

        $data['base_amount'] = $quote['base_amount'];
        $data['used_special_rate'] = $quote['used_special_rate'] ?? false;
        $data['surcharges'] = array_merge($data['surcharges'] ?? [], $quote['surcharges'] ?? []);

        $billingProfile = ClientBillingProfile::resolveForClientUser($data['client_user_id'] ?? null);
        $pricing = $this->pricingService->priceShipment($data, $billingProfile);

        $data['client_account_id'] = $data['client_account_id']
            ?? (! empty($data['client_user_id']) ? ClientAccount::where('client_user_id', $data['client_user_id'])->where('is_default', true)->value('id') : null);

        if ($data['client_account_id']) {
            $resolvedAccount = ClientAccount::find($data['client_account_id']);

            if ($resolvedAccount?->isSuspended()) {
                throw new \RuntimeException("\"{$resolvedAccount->account_name}\" is suspended and can't book new shipments." . ($resolvedAccount->suspension_reason ? " Reason: {$resolvedAccount->suspension_reason}" : ''));
            }
        }

        // A credit account defaults to deferred (invoiced later) but
        // can still choose to pay a specific transaction now — see
        // resolveCollectionMethod() below. Everyone else (a walk-in
        // with no account at all, or a registered account that isn't
        // on credit) has no invoice to fall back on, so one of the
        // two real payment methods is required, not optional.
        $isCreditAccount = isset($resolvedAccount) && $resolvedAccount->isCreditAccount();
        if (! $isCreditAccount && ! in_array($data['payment_method'] ?? null, ['cash', 'paystack'], true)) {
            throw new \RuntimeException('A payment method (cash or online) is required — this account has no credit facility to defer payment to.');
        }

        $collectionMethod = $this->resolveCollectionMethod($data);

        return Shipment::create([
            ...$data,
            'shipping_type' => $quote['shipping_type'],
            'promised_delivery_at' => null,
            'transit_days' => $quote['transit_days'] ?? null,
            ...$pricing,
            ...$collectionMethod,
        ]);
    }

    /**
     * A credit client is normally invoiced later, not paid at
     * booking — but they can still choose to pay a specific
     * transaction immediately rather than have it added to their
     * monthly invoice, so whichever payment_method was actually
     * submitted is what's respected here, not overridden by the
     * account's own default. 'deferred' (or nothing at all) means no
     * collection now — that one transaction rides on the invoice
     * instead. Enforcing that a non-credit/walk-in account can't
     * leave this deferred is the calling form's job (it requires a
     * real choice there), not this service's — this only interprets
     * whatever was actually sent.
     */
    private function resolveCollectionMethod(array $data): array
    {
        if (($data['payment_method'] ?? null) === 'cash') {
            return ['collection_method' => 'cash', 'cash_collected_at' => now()];
        }

        if (($data['payment_method'] ?? null) === 'paystack') {
            return ['collection_method' => 'paystack'];
        }

        return [];
    }
}
