<x-layouts.app title="Transfer Funds">

    <div class="mb-6">
        <p class="text-2xl font-semibold text-ink-900">Transfer Funds</p>
        <p class="mt-1 text-sm text-ink-500">Move money directly from one wallet to another — a client account's, an outlet's, either direction.</p>
    </div>

    <div class="max-w-lg rounded-xl border border-line bg-surface-0 shadow-sm p-5">
        <form method="POST" action="{{ route('wallets.transfer') }}" class="space-y-4" data-confirm="Move this amount between the two wallets selected? This can't be undone from here.">
            @csrf

            <div>
                <label class="mb-1 block text-sm font-medium text-ink-900">From</label>
                <select name="from_wallet_id" required class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
                    <option value="">— Select —</option>
                    @foreach ($wallets as $w)
                        <option value="{{ $w->id }}" @selected(old('from_wallet_id', $fromWalletId) == $w->id)>{{ $w->label() }} — {{ $w->currency }} {{ number_format($w->balance, 2) }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-ink-900">To</label>
                <select name="to_wallet_id" required class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
                    <option value="">— Select —</option>
                    @foreach ($wallets as $w)
                        <option value="{{ $w->id }}" @selected(old('to_wallet_id') == $w->id)>{{ $w->label() }} — {{ $w->currency }} {{ number_format($w->balance, 2) }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-ink-900">Amount</label>
                <input type="number" name="amount" step="0.01" min="0.01" required value="{{ old('amount') }}" class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-ink-900">Note <span class="text-xs font-normal text-ink-500">(optional)</span></label>
                <input type="text" name="note" maxlength="255" value="{{ old('note') }}" placeholder="Reason for this transfer" class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
            </div>

            <button type="submit" class="w-full rounded-md bg-[var(--brand-primary)] px-4 py-2 text-sm font-semibold text-white hover:opacity-90">Transfer</button>
        </form>
    </div>

</x-layouts.app>
