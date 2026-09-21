<x-layouts.app :title="'Bulk Shipment Upload — Result'">

    <div class="mb-5">
        <p class="text-2xl font-semibold text-ink-900">Bulk Upload Complete</p>
        <p class="mt-1 text-sm text-ink-500">{{ count($created) }} created, {{ count($failed) }} failed.</p>
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

    <a href="{{ route('shipments.bulk.create') }}" class="text-sm font-medium text-[var(--brand-primary)] hover:underline">← Upload another batch</a>

</x-layouts.app>
