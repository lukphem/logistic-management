<x-layouts.app :title="'Bulk Shipment Upload — Review'">

    <div class="mb-5">
        <p class="text-2xl font-semibold text-ink-900">Review Pending Rows</p>
        <p class="mt-1 text-sm text-ink-500">
            Batch <span class="font-mono font-medium text-ink-900">{{ $batch->batch_number }}</span> — {{ $batch->isWalkIn() ? 'Walk-in customer' : $batch->clientAccount?->account_name }} — {{ $validRows->count() }} ready to create, {{ $invalidRows->count() }} with errors.
            Nothing has been created yet — this is everything currently pending across every file uploaded to this batch so far.
        </p>
    </div>

    @if (session('status'))
        <div class="mb-5 rounded-lg border border-line bg-surface-50 p-3 text-sm text-ink-700">{{ session('status') }}</div>
    @endif

    @if ($invalidRows->isNotEmpty())
        <div class="mb-6">
            <p class="mb-3 text-sm font-semibold text-status-exception">Errors — will be skipped ({{ $invalidRows->count() }})</p>
            <div class="max-h-80 overflow-y-auto rounded-lg border border-line">
                <table class="w-full text-sm">
                    <thead class="sticky top-0 bg-surface-50">
                        <tr class="border-b border-line text-left text-xs uppercase tracking-wide text-ink-500">
                            <th class="p-2.5">Row</th>
                            <th class="p-2.5">Receiver</th>
                            <th class="p-2.5">Errors</th>
                            <th class="p-2.5"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($invalidRows as $row)
                            <tr class="border-b border-line">
                                <td class="p-2.5 font-mono">{{ $row->source_row_number }}</td>
                                <td class="p-2.5">{{ $row->receiver_name ?? '—' }}</td>
                                <td class="p-2.5 text-status-exception">{{ implode(' ', $row->errors ?? []) }}</td>
                                <td class="p-2.5 text-right">
                                    <form method="POST" action="{{ route('shipments.bulk.rows.destroy', [$batch, $row]) }}" data-confirm="Remove this row?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs font-medium text-status-exception hover:underline">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if ($validRows->isNotEmpty())
        <div class="mb-6">
            <p class="mb-3 text-sm font-semibold text-status-delivered">Ready to create ({{ $validRows->count() }})</p>
            <div class="max-h-96 overflow-y-auto rounded-lg border border-line">
                <table class="w-full text-sm">
                    <thead class="sticky top-0 bg-surface-50">
                        <tr class="border-b border-line text-left text-xs uppercase tracking-wide text-ink-500">
                            <th class="p-2.5">Row</th>
                            <th class="p-2.5">Receiver</th>
                            <th class="p-2.5">Phone</th>
                            <th class="p-2.5">Email</th>
                            <th class="p-2.5">Destination</th>
                            <th class="p-2.5">State/Town</th>
                            <th class="p-2.5">Pieces</th>
                            <th class="p-2.5">Weight</th>
                            <th class="p-2.5">COD Amount</th>
                            <th class="p-2.5"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($validRows as $row)
                            <tr class="border-b border-line">
                                <td class="p-2.5 font-mono">{{ $row->source_row_number }}</td>
                                <td class="p-2.5">{{ $row->row_data['receiver_name'] }}</td>
                                <td class="p-2.5">{{ $row->row_data['receiver_phone'] }}</td>
                                <td class="p-2.5">{{ $row->row_data['receiver_email'] ?? '—' }}</td>
                                <td class="p-2.5">{{ $row->row_data['destination_address'] }}</td>
                                <td class="p-2.5">{{ $row->display_data['destination_city_name'] }}, {{ $row->display_data['destination_state_name'] }}</td>
                                <td class="p-2.5">{{ $row->row_data['quantity'] }}</td>
                                <td class="p-2.5">{{ $row->row_data['weight_kg'] ? $row->row_data['weight_kg'] . ' kg' : '—' }}</td>
                                <td class="p-2.5">{{ $row->row_data['is_cod'] ? number_format($row->row_data['cod_amount'], 2) : '—' }}</td>
                                <td class="p-2.5 text-right">
                                    <form method="POST" action="{{ route('shipments.bulk.rows.destroy', [$batch, $row]) }}" data-confirm="Remove this row?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs font-medium text-status-exception hover:underline">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <form method="POST" action="{{ route('shipments.bulk.store', $batch) }}" class="inline">
            @csrf
            <button type="submit" class="rounded-md bg-[var(--brand-primary)] px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:opacity-90">
                Create {{ $validRows->count() }} shipment(s)
            </button>
        </form>
        @if ($batch->hasCreatedShipments())
            <a href="{{ route('shipments.bulk.print', $batch) }}" target="_blank" class="ml-3 text-sm font-medium text-[var(--brand-primary)] hover:underline">🖨️ Print batch</a>
        @else
            <a href="{{ route('shipments.bulk.upload', $batch) }}" class="ml-3 text-sm font-medium text-ink-500 hover:text-ink-900">Upload more to this batch</a>
        @endif
    @else
        <div class="rounded-lg border border-line bg-surface-50 p-4 text-sm text-ink-500">
            No valid rows to create yet.
        </div>
        @if ($batch->hasCreatedShipments())
            <a href="{{ route('shipments.bulk.print', $batch) }}" target="_blank" class="mt-3 inline-block text-sm font-medium text-[var(--brand-primary)] hover:underline">🖨️ Print batch</a>
        @else
            <a href="{{ route('shipments.bulk.upload', $batch) }}" class="mt-3 inline-block text-sm font-medium text-[var(--brand-primary)] hover:underline">← Upload a file</a>
        @endif
    @endif

</x-layouts.app>
