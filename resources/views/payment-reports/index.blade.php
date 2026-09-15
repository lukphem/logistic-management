<x-layouts.app title="Payment Reports">

    <div class="mb-6">
        <p class="text-2xl font-semibold text-ink-900">Payment Reports</p>
        <p class="mt-1 text-sm text-ink-500">Every cash-collected shipment — paid and unpaid, all time. For what's still outstanding right now, see <a href="{{ route('reconciliation.index') }}" class="text-[var(--brand-primary)] hover:underline">Reconciliation</a> instead.</p>
    </div>

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border border-line bg-surface-0 shadow-sm p-4">
            <p class="text-xs uppercase tracking-wide text-ink-500">Paid</p>
            <p class="mt-1 text-2xl font-semibold text-status-delivered">{{ number_format($summary['paid_total'], 2) }}</p>
            <p class="text-xs text-ink-500">{{ $summary['paid_count'] }} shipment(s)</p>
        </div>
        <div class="rounded-xl border border-line bg-surface-0 shadow-sm p-4">
            <p class="text-xs uppercase tracking-wide text-ink-500">Unpaid</p>
            <p class="mt-1 text-2xl font-semibold text-ink-900">{{ number_format($summary['unpaid_total'], 2) }}</p>
            <p class="text-xs text-ink-500">{{ $summary['unpaid_count'] }} shipment(s)</p>
        </div>
    </div>

    <form method="GET" class="mb-4 flex flex-wrap items-end gap-3 rounded-xl border border-line bg-surface-0 shadow-sm p-4">
        @if ($outlets->isNotEmpty())
            <div>
                <label class="mb-1 block text-xs font-medium text-ink-900">Outlet</label>
                <select name="outlet_id" class="rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                    <option value="">All outlets</option>
                    @foreach ($outlets as $outlet)
                        <option value="{{ $outlet->id }}" @selected(request('outlet_id') == $outlet->id)>{{ $outlet->name }}</option>
                    @endforeach
                </select>
            </div>
        @endif
        <div>
            <label class="mb-1 block text-xs font-medium text-ink-900">Status</label>
            <select name="status" class="rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                <option value="">All</option>
                <option value="paid" @selected(request('status') === 'paid')>Paid</option>
                <option value="unpaid" @selected(request('status') === 'unpaid')>Unpaid</option>
            </select>
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-ink-900">From</label>
            <input type="date" name="date_from" value="{{ request('date_from') }}" class="rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-ink-900">To</label>
            <input type="date" name="date_to" value="{{ request('date_to') }}" class="rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
        </div>
        <button type="submit" class="rounded-md bg-[var(--brand-primary)] px-4 py-2 text-sm font-semibold text-white hover:opacity-90">Filter</button>
        @if (request()->hasAny(['outlet_id', 'status', 'date_from', 'date_to']))
            <a href="{{ route('payment-reports.index') }}" class="text-sm text-ink-500 hover:underline">Clear</a>
        @endif
    </form>

    <div class="rounded-xl border border-line bg-surface-0 shadow-sm overflow-hidden">
        @if ($shipments->isEmpty())
            <p class="p-6 text-sm text-ink-500">No cash-collected shipments match these filters.</p>
        @else
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-line text-left text-xs uppercase tracking-wide text-ink-500">
                        <th class="p-3">Tracking #</th>
                        <th class="p-3">Outlet / Source</th>
                        <th class="p-3">Collected</th>
                        <th class="p-3">Status</th>
                        <th class="p-3 text-right">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($shipments as $shipment)
                        <tr class="border-b border-line last:border-0">
                            <td class="p-3">
                                <a href="{{ route('shipments.show', $shipment) }}" class="font-mono text-[var(--brand-primary)] hover:underline">{{ $shipment->tracking_number }}</a>
                            </td>
                            <td class="p-3 text-ink-700">
                                @if ($shipment->is_cod && $shipment->assignedRider)
                                    COD · {{ $shipment->assignedRider->name }}
                                @elseif ($shipment->currentOutlet)
                                    {{ $shipment->currentOutlet->name }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="p-3 text-ink-500">{{ $shipment->cash_collected_at->format('d M Y, H:i') }}</td>
                            <td class="p-3">
                                @if ($shipment->payment_status === 'paid')
                                    <span class="text-status-delivered">Paid</span>
                                    @if ($shipment->cashSettlement)
                                        <span class="text-xs text-ink-500">(settlement #{{ $shipment->cashSettlement->id }})</span>
                                    @endif
                                @elseif ($shipment->payment_status === 'failed')
                                    <span class="text-status-exception">Failed</span>
                                @else
                                    <span class="text-ink-500">Unpaid</span>
                                @endif
                            </td>
                            <td class="p-3 text-right font-mono text-ink-900">{{ number_format($shipment->total_amount, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div class="mt-4">
        {{ $shipments->links() }}
    </div>

</x-layouts.app>
