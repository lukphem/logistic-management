<x-layouts.app :title="'Batch ' . $batch->batch_number">

    <div class="mb-6 flex items-start justify-between">
        <div>
            <p class="text-2xl font-semibold text-ink-900">Bulk Upload Batch no: <span class="font-mono">{{ $batch->batch_number }}</span></p>
            <p class="mt-1 text-sm text-ink-500">Created at: {{ $batch->created_at->format('d M Y, g:i A') }}</p>
        </div>

        <div class="flex items-center gap-2">
            @if ($batch->hasCreatedShipments())
                {{-- Uploading is over once a batch has real shipments
                     — printing is the only action offered from here
                     on, never "Upload more". --}}
                <a href="{{ route('shipments.bulk.print', $batch) }}" target="_blank" class="rounded-md bg-[var(--brand-primary)] px-4 py-2 text-sm font-semibold text-white hover:opacity-90">🖨️ Print waybills</a>
            @else
                <a href="{{ route('shipments.bulk.review', $batch) }}" class="rounded-md border border-line px-4 py-2 text-sm font-medium text-ink-700 hover:bg-surface-50">Review pending ({{ $pendingCount }})</a>
                <a href="{{ route('shipments.bulk.upload', $batch) }}" class="rounded-md bg-[var(--brand-primary)] px-4 py-2 text-sm font-semibold text-white hover:opacity-90">Upload</a>
            @endif
        </div>
    </div>

    <div class="mb-6 rounded-xl border border-line bg-surface-0 shadow-sm p-5">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div>
                <p class="text-xs uppercase tracking-wide text-ink-500">Account</p>
                <p class="mt-0.5 text-sm font-medium text-ink-900">{{ $batch->isWalkIn() ? 'Walk-in customer' : $batch->clientAccount?->account_name }}</p>
            </div>
            <div>
                <p class="text-xs uppercase tracking-wide text-ink-500">Service type</p>
                <p class="mt-0.5 text-sm font-medium text-ink-900">{{ $batch->serviceType?->name ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs uppercase tracking-wide text-ink-500">Origin</p>
                <p class="mt-0.5 text-sm font-medium text-ink-900">{{ $batch->originCity?->name }}{{ $batch->originCity?->state ? ', ' . $batch->originCity->state->name : '' }}</p>
            </div>
            <div>
                <p class="text-xs uppercase tracking-wide text-ink-500">Sender</p>
                <p class="mt-0.5 text-sm font-medium text-ink-900">{{ $batch->sender_name }} — {{ $batch->sender_phone }}</p>
            </div>
            <div>
                <p class="text-xs uppercase tracking-wide text-ink-500">Sender address</p>
                <p class="mt-0.5 text-sm font-medium text-ink-900">{{ $batch->sender_address }}</p>
            </div>
            <div>
                <p class="text-xs uppercase tracking-wide text-ink-500">Total shipments</p>
                <p class="mt-0.5 text-sm font-medium text-ink-900">{{ $shipments->total() }}</p>
            </div>
        </div>
    </div>

    <div class="rounded-xl border border-line bg-surface-0 shadow-sm overflow-hidden">
        @if ($shipments->isEmpty())
            <p class="p-6 text-sm text-ink-500">No shipments created under this batch yet.</p>
        @else
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-line text-left text-xs uppercase tracking-wide text-ink-500">
                        <th class="p-3">Shipment no</th>
                        <th class="p-3">Receiver</th>
                        <th class="p-3">Phone</th>
                        <th class="p-3">Email</th>
                        <th class="p-3">Destination</th>
                        <th class="p-3">State/Town</th>
                        <th class="p-3">Pieces</th>
                        <th class="p-3">Weight</th>
                        <th class="p-3">COD Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($shipments as $shipment)
                        <tr class="border-b border-line last:border-0">
                            <td class="p-3">
                                <a href="{{ route('shipments.show', $shipment) }}" class="inline-block rounded border border-line px-2 py-1 font-mono text-xs text-[var(--brand-primary)] hover:underline">{{ $shipment->tracking_number }}</a>
                            </td>
                            <td class="p-3 text-ink-700">{{ $shipment->receiver_name }}</td>
                            <td class="p-3 text-ink-700">{{ $shipment->receiver_phone }}</td>
                            <td class="p-3 text-ink-700">{{ $shipment->receiver_email ?? '—' }}</td>
                            <td class="p-3 text-ink-700">{{ $shipment->destination_address }}</td>
                            <td class="p-3 text-ink-700">{{ $shipment->destinationCity?->name }}{{ $shipment->destinationCity?->state ? ', ' . $shipment->destinationCity->state->name : '' }}</td>
                            <td class="p-3 text-ink-700">{{ $shipment->quantity ?? 1 }}</td>
                            <td class="p-3 text-ink-700">{{ $shipment->weight_kg ? $shipment->weight_kg . ' kg' : '—' }}</td>
                            <td class="p-3 text-ink-700">{{ $shipment->is_cod ? number_format($shipment->cod_amount, 2) : '—' }}</td>
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
