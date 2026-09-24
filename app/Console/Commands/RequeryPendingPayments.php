<?php

namespace App\Console\Commands;

use App\Models\AccountWalletFunding;
use App\Models\CashSettlement;
use App\Models\Shipment;
use App\Services\PaystackService;
use Illuminate\Console\Command;

/**
 * The actual safety net for "what if the callback URL is incomplete" —
 * a person closing their tab, losing connection, or the browser
 * simply never making it back to callback_url means
 * PaymentController::callback() never runs at all. The webhook is
 * supposed to catch this independently, but a webhook can itself fail
 * silently (misconfigured URL, firewall, Paystack's own delivery
 * hiccup) — this command is what catches a payment that both of those
 * paths missed, by directly asking Paystack "did this reference
 * actually succeed?" rather than waiting to be told.
 *
 * Only requeries references at least 10 minutes old (see the
 * whereDate filter below) — a reference that was just created is
 * probably still mid-checkout, and querying it repeatedly while
 * someone's actively paying adds nothing but API calls.
 */
class RequeryPendingPayments extends Command
{
    protected $signature = 'payments:requery-pending';

    protected $description = 'Ask Paystack directly about shipments/settlements that have a payment reference but were never confirmed paid or failed — catches payments where the callback and webhook both missed it';

    public function handle(PaystackService $paystack): int
    {
        if (! $paystack->isConfigured()) {
            $this->info('Paystack is not configured — nothing to requery.');

            return self::SUCCESS;
        }

        $cutoff = now()->subMinutes(10);
        $checked = 0;
        $confirmed = 0;

        $shipments = Shipment::whereNotNull('payment_reference')
            ->where('payment_status', 'unpaid')
            ->where('updated_at', '<', $cutoff)
            ->get();

        foreach ($shipments as $shipment) {
            $checked++;
            $result = $paystack->verifyTransaction($shipment->payment_reference);

            if (! $result['success']) {
                continue;
            }

            if ($result['paid']) {
                $applied = $paystack->markShipmentPaidIfDue($shipment->payment_reference, (int) round(((float) $shipment->total_amount) * 100));

                if ($applied) {
                    $confirmed++;
                    $this->info("Confirmed payment for shipment {$shipment->tracking_number} ({$shipment->payment_reference}) — callback/webhook must have missed it.");
                }
            }
        }

        $settlements = CashSettlement::whereNotNull('payment_reference')
            ->where('status', 'pending')
            ->where('updated_at', '<', $cutoff)
            ->get();

        foreach ($settlements as $settlement) {
            $checked++;
            $result = $paystack->verifyTransaction($settlement->payment_reference);

            if (! $result['success']) {
                continue;
            }

            if ($result['paid']) {
                $applied = $paystack->markSettlementPaidIfDue($settlement->payment_reference, (int) round(((float) $settlement->total_amount) * 100));

                if ($applied) {
                    $confirmed++;
                    $this->info("Confirmed settlement #{$settlement->id} ({$settlement->payment_reference}) — callback/webhook must have missed it.");
                }
            }
        }

        $walletFundings = AccountWalletFunding::whereNotNull('payment_reference')
            ->where('status', 'pending')
            ->where('updated_at', '<', $cutoff)
            ->get();

        foreach ($walletFundings as $funding) {
            $checked++;
            $result = $paystack->verifyTransaction($funding->payment_reference);

            if (! $result['success']) {
                continue;
            }

            if ($result['paid']) {
                $applied = $paystack->markWalletFundedIfDue($funding->payment_reference, (int) round(((float) $funding->amount) * 100));

                if ($applied) {
                    $confirmed++;
                    $this->info("Confirmed wallet funding #{$funding->id} ({$funding->payment_reference}) — callback/webhook must have missed it.");
                }
            }
        }

        $this->info("Requeried {$checked} pending payment(s), confirmed {$confirmed} that callback/webhook had missed.");

        return self::SUCCESS;
    }
}
