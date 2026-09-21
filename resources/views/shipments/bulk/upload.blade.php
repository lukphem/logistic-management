<x-layouts.app :title="'Bulk Shipment Upload — Upload File'">

    <div class="mb-5">
        <div class="flex items-center justify-between">
            <p class="text-2xl font-semibold text-ink-900">Bulk Shipment Upload — Step 2 of 2</p>
            <a href="{{ route('shipments.bulk.print', $batch) }}" target="_blank" class="rounded-md border border-line px-3 py-1.5 text-sm font-medium text-ink-700 hover:bg-surface-50">🖨️ Print batch</a>
        </div>
        <p class="mt-1 text-sm text-ink-500">
            Batch <span class="font-mono font-medium text-ink-900">{{ $batch->batch_number }}</span>
            — {{ $batch->isWalkIn() ? 'Walk-in customer' : $batch->clientAccount?->account_name }},
            {{ $batch->serviceType?->name }}. If a previous upload had errors, fix the file and upload it again here — the batch stays the same.
        </p>
    </div>

    @if ($errors->any())
        <div class="mb-5 max-w-2xl rounded-xl border border-status-exception/30 bg-status-exception/5 p-4 text-sm text-status-exception">
            @foreach ($errors->all() as $message)
                <p>{{ $message }}</p>
            @endforeach
        </div>
    @endif

    <div class="max-w-2xl rounded-xl border border-line bg-surface-0 shadow-sm p-5">
        <div class="mb-5 flex items-center justify-between rounded-lg border border-dashed border-line bg-surface-50 p-4">
            <div>
                <p class="text-sm font-medium text-ink-900">Need the template?</p>
                <p class="text-xs text-ink-500">Includes dropdowns for destination state and city, built from what's currently in the system.</p>
            </div>
            <a href="{{ route('shipments.bulk.template') }}" class="rounded-md border border-[var(--brand-primary)] px-4 py-2 text-sm font-semibold text-[var(--brand-primary)] transition hover:bg-[var(--brand-primary)]/5 whitespace-nowrap">
                Download template
            </a>
        </div>

        <form method="POST" action="{{ route('shipments.bulk.preview', $batch) }}" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <div>
                <label class="mb-1 block text-sm font-medium text-ink-900">Upload file</label>
                <input type="file" name="file" required accept=".xlsx,.xls,.csv"
                       class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
                <p class="mt-1 text-xs text-ink-500">.xlsx, .xls, or .csv — from the template above, or your own file with matching column headers.</p>
            </div>

            <button type="submit" class="w-full rounded-md bg-[var(--brand-primary)] px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:opacity-90">
                Preview upload
            </button>
        </form>
    </div>

</x-layouts.app>
