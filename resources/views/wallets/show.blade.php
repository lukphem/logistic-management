<x-layouts.app :title="$wallet->label()">

    @if (session('status'))
        <div class="mb-5 rounded-lg border border-line bg-surface-50 p-3 text-sm text-ink-700">{{ session('status') }}</div>
    @endif

    <div class="mb-6">
        <p class="text-2xl font-semibold text-ink-900">{{ $wallet->label() }}</p>
        <p class="mt-1 text-sm text-ink-500">{{ $wallet->owner_type === \App\Models\ClientAccount::class ? 'Client account wallet' : 'Outlet wallet' }}</p>
    </div>

    <div class="mb-6 rounded-xl border border-line bg-surface-0 shadow-sm p-5">
        <p class="text-xs uppercase tracking-wide text-ink-500">Current balance</p>
        <p class="mt-1 text-3xl font-semibold text-ink-900">{{ $wallet->currency }} {{ number_format($wallet->balance, 2) }}</p>
    </div>

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div class="rounded-xl border border-line bg-surface-0 shadow-sm p-5">
            <p class="mb-3 text-sm font-semibold text-ink-900">Fund via bank transfer</p>
            <p class="mb-3 text-xs text-ink-500">Records that a bank transfer was received and credits the wallet immediately — enter the amount and a reference so it stays traceable.</p>
            <form method="POST" action="{{ route('wallets.fund.bank-transfer', $wallet) }}" class="space-y-3" data-confirm="Credit this wallet with the amount entered? This can't be undone from here.">
                @csrf
                <div>
                    <label class="mb-1 block text-xs font-medium text-ink-900">Amount</label>
                    <input type="number" name="amount" step="0.01" min="0.01" required class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-ink-900">Bank reference</label>
                    <input type="text" name="bank_reference" required maxlength="255" placeholder="e.g. transfer ref, teller number" class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
                </div>
                <button type="submit" class="w-full rounded-md bg-[var(--brand-primary)] px-4 py-2 text-sm font-semibold text-white hover:opacity-90">Credit wallet</button>
            </form>
        </div>

        <div class="rounded-xl border border-line bg-surface-0 shadow-sm p-5">
            <p class="mb-3 text-sm font-semibold text-ink-900">Fund online (Paystack)</p>
            @if ($paystackEnabled)
                <p class="mb-3 text-xs text-ink-500">Redirects to Paystack checkout — the wallet is only credited once payment is actually confirmed.</p>
                <form method="POST" action="{{ route('wallets.fund.online', $wallet) }}" class="space-y-3">
                    @csrf
                    <div>
                        <label class="mb-1 block text-xs font-medium text-ink-900">Amount</label>
                        <input type="number" name="amount" step="0.01" min="0.01" required class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
                    </div>
                    <button type="submit" class="w-full rounded-md border border-line px-4 py-2 text-sm font-semibold text-ink-700 hover:bg-surface-50">Pay with Paystack</button>
                </form>
            @else
                <p class="text-xs text-ink-500">Paystack isn't enabled — configure it under Settings → Payments first.</p>
            @endif
        </div>
    </div>

    <div class="rounded-xl border border-line bg-surface-0 shadow-sm overflow-hidden">
        <p class="border-b border-line p-4 text-sm font-semibold text-ink-900">Transaction history</p>
        @if ($transactions->isEmpty())
            <p class="p-6 text-sm text-ink-500">No transactions yet.</p>
        @else
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-line text-left text-xs uppercase tracking-wide text-ink-500">
                        <th class="p-3">Date</th>
                        <th class="p-3">Type</th>
                        <th class="p-3">Amount</th>
                        <th class="p-3">Balance after</th>
                        <th class="p-3">Method</th>
                        <th class="p-3">Reference</th>
                        <th class="p-3">Recorded by</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($transactions as $transaction)
                        <tr class="border-b border-line last:border-0">
                            <td class="p-3 text-ink-700">{{ $transaction->created_at->format('d M Y, g:i A') }}</td>
                            <td class="p-3">
                                <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $transaction->type === 'credit' ? 'bg-status-delivered/10 text-status-delivered' : 'bg-status-exception/10 text-status-exception' }}">{{ ucfirst($transaction->type) }}</span>
                            </td>
                            <td class="p-3 font-medium text-ink-900">{{ $wallet->currency }} {{ number_format($transaction->amount, 2) }}</td>
                            <td class="p-3 text-ink-700">{{ $wallet->currency }} {{ number_format($transaction->balance_after, 2) }}</td>
                            <td class="p-3 text-ink-700">{{ $transaction->funding_method ? ucfirst(str_replace('_', ' ', $transaction->funding_method)) : '—' }}</td>
                            <td class="p-3 text-ink-700">{{ $transaction->reference ?? '—' }}</td>
                            <td class="p-3 text-ink-700">{{ $transaction->recordedBy?->name ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div class="mt-4">
        {{ $transactions->links() }}
    </div>

</x-layouts.app>
