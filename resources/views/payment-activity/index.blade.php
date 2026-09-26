<x-layouts.app title="Payment Activity">

    <div class="mb-6">
        <p class="text-2xl font-semibold text-ink-900">Payment Activity</p>
        <p class="mt-1 text-sm text-ink-500">Every payment-related transaction on the system — shipment payments and refunds, wallet fundings and transfers, cash settlements — in one place.</p>
    </div>

    <form method="GET" class="mb-4 flex flex-wrap items-end gap-3 rounded-xl border border-line bg-surface-0 shadow-sm p-4">
        <div>
            <label class="mb-1 block text-xs font-medium text-ink-900">From</label>
            <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-ink-900">To</label>
            <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-ink-900">Type</label>
            <select name="type" class="rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                <option value="">All</option>
                <option value="shipment_payment" @selected(($filters['type'] ?? '') === 'shipment_payment')>Shipment Payment</option>
                <option value="shipment_refund" @selected(($filters['type'] ?? '') === 'shipment_refund')>Shipment Refund</option>
                <option value="wallet_funding" @selected(($filters['type'] ?? '') === 'wallet_funding')>Wallet Funding</option>
                <option value="wallet_transfer" @selected(($filters['type'] ?? '') === 'wallet_transfer')>Wallet Transfer</option>
                <option value="cash_settlement" @selected(($filters['type'] ?? '') === 'cash_settlement')>Cash Settlement</option>
            </select>
        </div>
        @if ($outlets->isNotEmpty())
            <div>
                <label class="mb-1 block text-xs font-medium text-ink-900">Outlet</label>
                <select name="outlet_id" class="rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                    <option value="">All outlets</option>
                    @foreach ($outlets as $outlet)
                        <option value="{{ $outlet->id }}" @selected(($filters['outlet_id'] ?? '') == $outlet->id)>{{ $outlet->name }}</option>
                    @endforeach
                </select>
            </div>
        @endif
        <button type="submit" class="rounded-md bg-[var(--brand-primary)] px-4 py-2 text-sm font-semibold text-white hover:opacity-90">Filter</button>
        <a href="{{ route('payment-activity.export', $filters) }}" class="rounded-md border border-line px-4 py-2 text-sm font-medium text-ink-700 hover:bg-surface-50">Export to Excel</a>
    </form>

    <div class="rounded-xl border border-line bg-surface-0 shadow-sm overflow-hidden">
        @if ($activity->isEmpty())
            <p class="p-6 text-sm text-ink-500">No payment activity matches these filters.</p>
        @else
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-line text-left text-xs uppercase tracking-wide text-ink-500">
                        <th class="p-3">Date</th>
                        <th class="p-3">Type</th>
                        <th class="p-3">Amount</th>
                        <th class="p-3">Method</th>
                        <th class="p-3">Reference</th>
                        <th class="p-3">Description</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($activity as $entry)
                        <tr class="border-b border-line last:border-0">
                            <td class="p-3 text-ink-700">{{ \Illuminate\Support\Carbon::parse($entry->occurred_at)->format('d M Y, g:i A') }}</td>
                            <td class="p-3 text-ink-900">{{ $entry->type }}</td>
                            <td class="p-3 font-medium text-ink-900">{{ number_format($entry->amount, 2) }}</td>
                            <td class="p-3 text-ink-700">{{ $entry->method ? ucfirst(str_replace('_', ' ', $entry->method)) : '—' }}</td>
                            <td class="p-3 text-ink-700">
                                @if ($entry->shipment_id)
                                    <a href="{{ route('shipments.show', $entry->shipment_id) }}" class="text-[var(--brand-primary)] hover:underline">{{ $entry->reference }}</a>
                                @else
                                    {{ $entry->reference ?? '—' }}
                                @endif
                            </td>
                            <td class="p-3 text-ink-700">{{ $entry->description }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div class="mt-4">
        {{ $activity->links() }}
    </div>

</x-layouts.app>
