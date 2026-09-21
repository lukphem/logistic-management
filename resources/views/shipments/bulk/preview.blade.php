<x-layouts.app :title="'Bulk Shipment Upload — Preview'">

    <div class="mb-5">
        <p class="text-2xl font-semibold text-ink-900">Preview</p>
        <p class="mt-1 text-sm text-ink-500">
            {{ $account->account_name }} — {{ count($validRows) }} ready to create, {{ count($invalidRows) }} with errors.
            Nothing has been created yet.
        </p>
    </div>

    @if (count($invalidRows) > 0)
        <div class="mb-6">
            <p class="mb-3 text-sm font-semibold text-status-exception">Errors — will be skipped ({{ count($invalidRows) }})</p>
            <div class="max-h-80 overflow-y-auto rounded-lg border border-line">
                <table class="w-full text-sm">
                    <thead class="sticky top-0 bg-surface-50">
                        <tr class="border-b border-line text-left text-xs uppercase tracking-wide text-ink-500">
                            <th class="p-2.5">Row</th>
                            <th class="p-2.5">Receiver</th>
                            <th class="p-2.5">Errors</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($invalidRows as $row)
                            <tr class="border-b border-line">
                                <td class="p-2.5 font-mono">{{ $row['row'] }}</td>
                                <td class="p-2.5">{{ $row['receiver_name'] ?? '—' }}</td>
                                <td class="p-2.5 text-status-exception">{{ implode(' ', $row['errors']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if (count($validRows) > 0)
        <div class="mb-6">
            <p class="mb-3 text-sm font-semibold text-status-delivered">Ready to create ({{ count($validRows) }})</p>
            <div class="max-h-96 overflow-y-auto rounded-lg border border-line">
                <table class="w-full text-sm">
                    <thead class="sticky top-0 bg-surface-50">
                        <tr class="border-b border-line text-left text-xs uppercase tracking-wide text-ink-500">
                            <th class="p-2.5">Row</th>
                            <th class="p-2.5">Receiver</th>
                            <th class="p-2.5">Phone</th>
                            <th class="p-2.5">Destination</th>
                            <th class="p-2.5">Pieces</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($validRows as $row)
                            <tr class="border-b border-line">
                                <td class="p-2.5 font-mono">{{ $row['row'] }}</td>
                                <td class="p-2.5">{{ $row['data']['receiver_name'] }}</td>
                                <td class="p-2.5">{{ $row['data']['receiver_phone'] }}</td>
                                <td class="p-2.5">{{ $row['data']['destination_address'] }}</td>
                                <td class="p-2.5">{{ $row['data']['quantity'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <form method="POST" action="{{ route('shipments.bulk.store') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <button type="submit" class="rounded-md bg-[var(--brand-primary)] px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:opacity-90">
                Create {{ count($validRows) }} shipment(s)
            </button>
            <a href="{{ route('shipments.bulk.create') }}" class="ml-3 text-sm font-medium text-ink-500 hover:text-ink-900">Start over</a>
        </form>
    @else
        <div class="rounded-lg border border-line bg-surface-50 p-4 text-sm text-ink-500">
            No valid rows to create — fix the errors above and upload again.
        </div>
        <a href="{{ route('shipments.bulk.create') }}" class="mt-3 inline-block text-sm font-medium text-[var(--brand-primary)] hover:underline">← Back to upload</a>
    @endif

</x-layouts.app>
