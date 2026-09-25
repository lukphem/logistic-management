<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AccountWallet;
use App\Models\AccountWalletFunding;
use App\Models\AccountWalletTransfer;
use App\Models\ClientAccount;
use App\Models\Outlet;
use App\Services\PaystackService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WalletController extends Controller
{
    public function __construct(private PaystackService $paystack)
    {
    }

    /**
     * Wallets are created lazily — a ClientAccount or Outlet doesn't
     * get one until it's needed. Listing them here is also what
     * guarantees every account/outlet actually has one to list: each
     * is firstOrCreate()'d on the way in, the same lazy pattern the
     * existing (unrelated) ClientWallet system already uses.
     */
    public function index(): View
    {
        ClientAccount::whereDoesntHave('wallet')->each(fn ($account) => AccountWallet::firstOrCreate(['owner_type' => ClientAccount::class, 'owner_id' => $account->id]));
        Outlet::whereDoesntHave('wallet')->each(fn ($outlet) => AccountWallet::firstOrCreate(['owner_type' => Outlet::class, 'owner_id' => $outlet->id]));

        $wallets = AccountWallet::with('owner')->orderByDesc('balance')->paginate(25);

        return view('wallets.index', ['wallets' => $wallets]);
    }

    public function show(AccountWallet $wallet): View
    {
        $wallet->load('owner');

        $transactions = $wallet->transactions()->with('recordedBy')->latest()->paginate(25);

        return view('wallets.show', [
            'wallet' => $wallet,
            'transactions' => $transactions,
            'paystackEnabled' => \App\Models\Setting::current()->paystack_enabled,
        ]);
    }

    /**
     * Direct value update — staff records that a bank transfer was
     * actually received and credits the wallet to match immediately.
     * Trusted the same way a shipment's refund_note is: staff attests
     * it, recorded for audit (who, when, the reference they entered),
     * not independently verified by this app.
     */
    public function fundBankTransfer(Request $request, AccountWallet $wallet): RedirectResponse
    {
        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'bank_reference' => 'required|string|max:255',
        ]);

        $funding = AccountWalletFunding::create([
            'account_wallet_id' => $wallet->id,
            'funding_method' => 'bank_transfer',
            'amount' => $data['amount'],
            'status' => 'paid',
            'bank_reference' => $data['bank_reference'],
            'initiated_by_user_id' => auth()->id(),
            'paid_at' => now(),
        ]);

        $wallet->credit(
            amount: $data['amount'],
            fundingMethod: 'bank_transfer',
            reference: $data['bank_reference'],
            description: 'Bank transfer funding',
            recordedByUserId: auth()->id(),
        );

        return redirect()->route('wallets.show', $wallet)->with('status', 'Wallet funded with ' . number_format($data['amount'], 2) . ' via bank transfer.');
    }

    /**
     * Same redirect-to-Paystack-checkout flow as
     * PaymentController::pay(), just for wallet funding instead of a
     * shipment. WALLET- prefix on the reference is what
     * PaymentController's callback/webhook/requery dispatch on to
     * land back here instead of treating it as a shipment or
     * settlement payment.
     */
    public function fundOnline(Request $request, AccountWallet $wallet): RedirectResponse
    {
        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01',
        ]);

        if (! $this->paystack->isConfigured()) {
            return redirect()->route('wallets.show', $wallet)->withErrors(['payment' => 'Paystack is not set up yet — configure it under Settings → Payments first.']);
        }

        $reference = 'WALLET-' . $wallet->id . '-' . now()->format('YmdHis') . '-' . substr(bin2hex(random_bytes(3)), 0, 6);

        $funding = AccountWalletFunding::create([
            'account_wallet_id' => $wallet->id,
            'funding_method' => 'paystack',
            'amount' => $data['amount'],
            'status' => 'pending',
            'payment_reference' => $reference,
            'initiated_by_user_id' => auth()->id(),
        ]);

        $result = $this->paystack->initializeTransaction(
            email: auth()->user()->email,
            amountNaira: (float) $data['amount'],
            reference: $reference,
            callbackUrl: route('payments.callback'),
            metadata: ['account_wallet_id' => $wallet->id, 'wallet_owner' => $wallet->label()],
        );

        if (! $result['success']) {
            $funding->update(['status' => 'failed']);

            return redirect()->route('wallets.show', $wallet)->withErrors(['payment' => $result['message']]);
        }

        return redirect()->away($result['authorization_url']);
    }

    /**
     * The general "move money between any two wallets" form — an
     * optional ?from= pre-selects the source wallet when reached from
     * a specific wallet's own page, but any wallet can be picked on
     * either side here regardless.
     */
    public function transferForm(Request $request): View
    {
        ClientAccount::whereDoesntHave('wallet')->each(fn ($account) => AccountWallet::firstOrCreate(['owner_type' => ClientAccount::class, 'owner_id' => $account->id]));
        Outlet::whereDoesntHave('wallet')->each(fn ($outlet) => AccountWallet::firstOrCreate(['owner_type' => Outlet::class, 'owner_id' => $outlet->id]));

        return view('wallets.transfer', [
            'wallets' => AccountWallet::with('owner')->orderByDesc('balance')->get(),
            'fromWalletId' => $request->integer('from') ?: null,
        ]);
    }

    /**
     * A transfer is a debit on one wallet and a credit on the other,
     * both wrapped in one transaction so they can never happen
     * independently — no transfer ever leaves one side of the ledger
     * without the other. Both entries carry the same generated
     * reference, so following it from either wallet's history finds
     * the other side.
     */
    public function transfer(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'from_wallet_id' => 'required|exists:account_wallets,id',
            'to_wallet_id' => 'required|exists:account_wallets,id|different:from_wallet_id',
            'amount' => 'required|numeric|min:0.01',
            'note' => 'nullable|string|max:255',
        ]);

        $fromWallet = AccountWallet::findOrFail($data['from_wallet_id']);
        $toWallet = AccountWallet::findOrFail($data['to_wallet_id']);

        if ($fromWallet->balance < $data['amount']) {
            return redirect()->route('wallets.transfer.form')->withErrors(['amount' => 'Insufficient balance in ' . $fromWallet->label() . ' — it has ' . number_format($fromWallet->balance, 2) . ' but ' . number_format($data['amount'], 2) . ' was requested.'])->withInput();
        }

        $reference = 'XFER-' . now()->format('YmdHis') . '-' . substr(bin2hex(random_bytes(3)), 0, 6);
        $description = $data['note'] ?? null;

        \Illuminate\Support\Facades\DB::transaction(function () use ($fromWallet, $toWallet, $data, $reference, $description) {
            AccountWalletTransfer::create([
                'from_account_wallet_id' => $fromWallet->id,
                'to_account_wallet_id' => $toWallet->id,
                'amount' => $data['amount'],
                'reference' => $reference,
                'note' => $description,
                'initiated_by_user_id' => auth()->id(),
            ]);

            $fromWallet->debit(
                amount: (float) $data['amount'],
                reference: $reference,
                description: $description ?? ('Transfer to ' . $toWallet->label()),
                recordedByUserId: auth()->id(),
            );

            $toWallet->credit(
                amount: (float) $data['amount'],
                fundingMethod: null,
                reference: $reference,
                description: $description ?? ('Transfer from ' . $fromWallet->label()),
                recordedByUserId: auth()->id(),
            );
        });

        return redirect()->route('wallets.index')->with('status', 'Transferred ' . number_format($data['amount'], 2) . ' from ' . $fromWallet->label() . ' to ' . $toWallet->label() . '.');
    }
}
