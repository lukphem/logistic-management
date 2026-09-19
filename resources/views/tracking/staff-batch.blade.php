<x-layouts.app :title="$trackingNumber">

    <div class="mb-6 flex items-center gap-3">
        @if ($back)
            <a href="{{ route('staff-tracking.multi', ['numbers' => $back]) }}" class="text-sm text-[var(--brand-primary)] hover:underline">← Back to results</a>
        @else
            <a href="{{ route('staff-tracking.search') }}" class="text-sm text-[var(--brand-primary)] hover:underline">← Track another number</a>
        @endif
    </div>

    @if (! $batch)
        <div class="rounded-xl border border-line bg-surface-0 shadow-sm p-8 text-center">
            <p class="text-lg font-semibold text-ink-900">Nothing found</p>
            <p class="mt-1 text-sm text-ink-500">We couldn't find anything for <span class="font-mono">{{ $trackingNumber }}</span>.</p>
        </div>
    @else
        <div class="rounded-xl border border-line bg-surface-0 shadow-sm p-6">
            <p class="font-mono text-lg font-semibold text-ink-900">{{ $batchLabel }}</p>
            <p class="mt-1 text-sm text-ink-500">{{ $shipments->count() }} shipment(s) in this batch</p>
        </div>

        <div class="mt-6 rounded-xl border border-line bg-surface-0 shadow-sm overflow-hidden">
            @if ($shipments->isEmpty())
                <p class="p-6 text-sm text-ink-500">No shipments recorded for this batch.</p>
            @else
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-line text-left text-xs uppercase tracking-wide text-ink-500">
                            <th class="p-3">Tracking #</th>
                            <th class="p-3">Receiver</th>
                            <th class="p-3">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($shipments as $shipment)
                            <tr class="border-b border-line last:border-0">
                                <td class="p-3">
                                    <a href="{{ route('staff-tracking.show', $shipment->tracking_number) }}" class="font-mono text-[var(--brand-primary)] hover:underline">{{ $shipment->tracking_number }}</a>
                                </td>
                                <td class="p-3 text-ink-700">{{ $shipment->receiver_name }}</td>
                                <td class="p-3 text-ink-700">{{ ucfirst(str_replace('_', ' ', $shipment->current_status)) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    @endif

</x-layouts.app>
