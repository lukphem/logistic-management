<x-layouts.app title="Shipment Status Report">

    <div class="mb-6">
        <p class="text-2xl font-semibold text-ink-900">Shipment Status Report</p>
        <p class="mt-1 text-sm text-ink-500">Every shipment's status, at a glance — this table shows a working subset of columns; the Excel export carries the full set.</p>
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
            <label class="mb-1 block text-xs font-medium text-ink-900">Status</label>
            <input type="text" name="status" value="{{ $filters['status'] ?? '' }}" placeholder="e.g. delivered" class="rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
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
        <a href="{{ route('shipment-status-report.export', $filters) }}" class="rounded-md border border-line px-4 py-2 text-sm font-medium text-ink-700 hover:bg-surface-50">Export to Excel (full columns)</a>
    </form>

    <div class="rounded-xl border border-line bg-surface-0 shadow-sm overflow-x-auto">
        @if ($shipments->isEmpty())
            <p class="p-6 text-sm text-ink-500">No shipments match these filters.</p>
        @else
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-line text-left text-xs uppercase tracking-wide text-ink-500">
                        <th class="p-3">Waybill No</th>
                        <th class="p-3">Destination</th>
                        <th class="p-3">Receiver</th>
                        <th class="p-3">Amount Due</th>
                        <th class="p-3">Amount Paid</th>
                        <th class="p-3">Payment Method</th>
                        <th class="p-3">Pickup Status</th>
                        <th class="p-3">Delivery Status</th>
                        <th class="p-3">Expected Delivery</th>
                        <th class="p-3">Created By</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($shipments as $shipment)
                        <tr class="border-b border-line last:border-0">
                            <td class="p-3">
                                <a href="{{ route('shipments.show', $shipment) }}" class="font-mono text-xs text-[var(--brand-primary)] hover:underline">{{ $shipment->tracking_number }}</a>
                            </td>
                            <td class="p-3 text-ink-700">{{ $shipment->destinationCity?->name }}{{ $shipment->destinationCity?->state ? ', ' . $shipment->destinationCity->state->name : '' }}</td>
                            <td class="p-3 text-ink-700">{{ $shipment->receiver_name }}</td>
                            <td class="p-3 text-ink-700">{{ number_format($shipment->total_amount, 2) }}</td>
                            <td class="p-3 text-ink-700">{{ number_format($shipment->hasCollectedPayment() ? $shipment->total_amount : 0, 2) }}</td>
                            <td class="p-3 text-ink-700">{{ $shipment->collection_method ? ucfirst($shipment->collection_method) : 'Deferred' }}</td>
                            <td class="p-3 text-ink-700">{{ $shipment->pickup_date ? 'Picked Up' : 'Not Picked Up' }}</td>
                            <td class="p-3"><x-status-pill :status="$shipment->current_status" /></td>
                            <td class="p-3 text-ink-700">{{ $shipment->promised_delivery_at?->format('d M Y') ?? '—' }}</td>
                            <td class="p-3 text-ink-700">{{ $shipment->createdBy?->name ?? '—' }}</td>
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
