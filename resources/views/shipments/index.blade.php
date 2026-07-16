<x-layouts.app :title="'Shipments'">

    <div class="mb-5 flex items-center justify-between">
        <form method="GET" class="flex items-center gap-3">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search tracking number…"
                   class="w-64 rounded-md border border-line bg-surface-0 px-3 py-2 text-sm text-ink-900 outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">

            <select name="status" onchange="this.form.submit()"
                    class="rounded-md border border-line bg-surface-0 px-3 py-2 text-sm text-ink-900 outline-none focus:border-[var(--brand-primary)]">
                <option value="">All statuses</option>
                @foreach (['booked', 'picked_up', 'in_transit', 'out_for_delivery', 'delivered', 'exception', 'returned', 'cancelled'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                @endforeach
            </select>

            <button type="submit" class="rounded-md bg-[var(--brand-primary)] px-4 py-2 text-sm font-medium text-white hover:opacity-90">
                Filter
            </button>
        </form>
    </div>

    <div class="overflow-hidden rounded-xl border border-line bg-surface-0 shadow-sm">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="border-b border-line text-xs uppercase tracking-wide text-ink-500">
                    <th class="px-5 py-3 font-medium">Tracking No.</th>
                    <th class="px-5 py-3 font-medium">Route</th>
                    <th class="px-5 py-3 font-medium">Service</th>
                    <th class="px-5 py-3 font-medium">Status</th>
                    <th class="px-5 py-3 font-medium">Rider</th>
                    <th class="px-5 py-3 font-medium text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($shipments as $shipment)
                    <tr class="border-b border-line last:border-0 hover:bg-surface-50 transition-colors">
                        <td class="px-5 py-3">
                            <a href="{{ route('shipments.show', $shipment) }}" class="font-mono text-sm text-[var(--brand-primary)] hover:underline">
                                {{ $shipment->tracking_number }}
                            </a>
                        </td>
                        <td class="px-5 py-3 text-ink-500">
                            {{ $shipment->originZone?->name ?? '—' }} → {{ $shipment->destinationZone?->name ?? '—' }}
                        </td>
                        <td class="px-5 py-3 text-ink-900">{{ ucfirst($shipment->service_type) }}</td>
                        <td class="px-5 py-3"><x-status-pill :status="$shipment->current_status" /></td>
                        <td class="px-5 py-3 text-ink-500">{{ $shipment->assignedRider?->name ?? '—' }}</td>
                        <td class="px-5 py-3 text-right font-mono text-ink-900">{{ number_format($shipment->total_amount, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-5 py-8 text-center text-sm text-ink-500">No shipments match this filter.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-5">
        {{ $shipments->links() }}
    </div>

</x-layouts.app>
