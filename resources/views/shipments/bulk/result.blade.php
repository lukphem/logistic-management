<x-layouts.app :title="'Bulk Shipment Upload — Result'">

    <div class="mb-5">
        <p class="text-2xl font-semibold text-ink-900">Bulk Upload Complete</p>
        <p class="mt-1 text-sm text-ink-500">Batch <span class="font-mono font-medium text-ink-900">{{ $batch->batch_number }}</span> — {{ count($created) }} created, {{ count($failed) }} failed.</p>
    </div>

    @if (count($created) > 0)
        <div class="mb-6">
            <p class="mb-3 text-sm font-semibold text-status-delivered">Created ({{ count($created) }})</p>
            <div class="max-h-96 overflow-y-auto rounded-lg border border-line">
                <table class="w-full text-sm">
                    <thead class="sticky top-0 bg-surface-50">
                        <tr class="border-b border-line text-left text-xs uppercase tracking-wide text-ink-500">
                            <th class="p-2.5">Row</th>
                            <th class="p-2.5">Tracking #</th>
                            <th class="p-2.5">Receiver</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($created as $item)
                            <tr class="border-b border-line">
                                <td class="p-2.5 font-mono">{{ $item['row'] }}</td>
                                <td class="p-2.5 font-mono">
                                    <a href="{{ route('shipments.show', $item['id']) }}" class="text-[var(--brand-primary)] hover:underline">{{ $item['tracking_number'] }}</a>
                                </td>
                                <td class="p-2.5">{{ $item['receiver_name'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if (count($failed) > 0)
        <div class="mb-6">
            <p class="mb-3 text-sm font-semibold text-status-exception">Failed ({{ count($failed) }})</p>
            <div class="max-h-80 overflow-y-auto rounded-lg border border-line">
                <table class="w-full text-sm">
                    <thead class="sticky top-0 bg-surface-50">
                        <tr class="border-b border-line text-left text-xs uppercase tracking-wide text-ink-500">
                            <th class="p-2.5">Row</th>
                            <th class="p-2.5">Receiver</th>
                            <th class="p-2.5">Error</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($failed as $item)
                            <tr class="border-b border-line">
                                <td class="p-2.5 font-mono">{{ $item['row'] }}</td>
                                <td class="p-2.5">{{ $item['receiver_name'] ?? '—' }}</td>
                                <td class="p-2.5 text-status-exception">{{ $item['error'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if (count($failed) > 0)
        {{-- Failed rows stay pending on the batch either way — Review
             is where they're actually retried or deleted, not the
             upload form, which is unreachable anyway once the batch
             has shipments (as it does here, having just created
             some). --}}
        <a href="{{ route('shipments.bulk.review', $batch) }}" class="mr-4 text-sm font-medium text-[var(--brand-primary)] hover:underline">Review the failed rows</a>
    @endif
    <a href="{{ route('shipments.bulk.show', $batch) }}" class="mr-4 text-sm font-medium text-[var(--brand-primary)] hover:underline">View batch</a>
    <a href="{{ route('shipments.bulk.create') }}" class="text-sm font-medium text-[var(--brand-primary)] hover:underline">Start a new batch</a>

</x-layouts.app>
