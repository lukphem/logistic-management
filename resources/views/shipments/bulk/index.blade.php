<x-layouts.app :title="'Bulk Shipment Batches'">

    <div class="mb-6 flex items-center justify-between">
        <div>
            <p class="text-2xl font-semibold text-ink-900">Bulk Shipment Batches</p>
            <p class="mt-1 text-sm text-ink-500">Every bulk upload batch — shipper details, service type, and how many shipments actually came out of it.</p>
        </div>
        <a href="{{ route('shipments.bulk.create') }}" class="rounded-md bg-[var(--brand-primary)] px-4 py-2 text-sm font-semibold text-white hover:opacity-90">New batch</a>
    </div>

    <div class="rounded-xl border border-line bg-surface-0 shadow-sm overflow-hidden">
        @if ($batches->isEmpty())
            <p class="p-6 text-sm text-ink-500">No bulk batches yet.</p>
        @else
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-line text-left text-xs uppercase tracking-wide text-ink-500">
                        <th class="p-3">Batch</th>
                        <th class="p-3">Account</th>
                        <th class="p-3">Service type</th>
                        <th class="p-3">Shipments</th>
                        <th class="p-3">Created by</th>
                        <th class="p-3">Created</th>
                        <th class="p-3"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($batches as $batch)
                        <tr class="border-b border-line last:border-0">
                            <td class="p-3">
                                <a href="{{ route('shipments.bulk.upload', $batch) }}" class="font-mono text-[var(--brand-primary)] hover:underline">{{ $batch->batch_number }}</a>
                            </td>
                            <td class="p-3 text-ink-700">{{ $batch->isWalkIn() ? 'Walk-in customer' : $batch->clientAccount?->account_name }}</td>
                            <td class="p-3 text-ink-700">{{ $batch->serviceType?->name ?? '—' }}</td>
                            <td class="p-3 text-ink-700">{{ $batch->shipments_count }}</td>
                            <td class="p-3 text-ink-700">{{ $batch->createdBy?->name ?? '—' }}</td>
                            <td class="p-3 text-ink-500">{{ $batch->created_at->format('d M Y, H:i') }}</td>
                            <td class="p-3 text-right">
                                <a href="{{ route('shipments.bulk.upload', $batch) }}" class="text-xs font-medium text-ink-500 hover:text-ink-900">Upload more</a>
                                @if ($batch->shipments_count > 0)
                                    <a href="{{ route('shipments.bulk.print', $batch) }}" target="_blank" class="ml-3 text-xs font-medium text-[var(--brand-primary)] hover:underline">🖨️ Print</a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div class="mt-4">
        {{ $batches->links() }}
    </div>

</x-layouts.app>
