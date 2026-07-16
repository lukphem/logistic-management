<x-layouts.app :title="'Scan Statuses'">

    @if (session('status'))
        <div class="mb-5 rounded-md bg-status-delivered/10 px-4 py-3 text-sm font-medium text-status-delivered">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-5 rounded-md bg-status-exception/10 px-4 py-3 text-sm text-status-exception">
            {{ $errors->first() }}
        </div>
    @endif

    <p class="mb-5 text-sm text-ink-500">
        This is the checkpoint sequence riders and hub staff scan against — it drives the shipment timeline shown to clients.
        Reordering here changes display order only; the underlying <code class="font-mono">key</code> on past shipments is never touched.
    </p>

    <div class="overflow-hidden rounded-xl border border-line bg-surface-0 shadow-sm">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="border-b border-line text-xs uppercase tracking-wide text-ink-500">
                    <th class="w-20 px-5 py-3 font-medium">Order</th>
                    <th class="px-5 py-3 font-medium">Key</th>
                    <th class="px-5 py-3 font-medium">Label</th>
                    <th class="px-5 py-3 font-medium">Ends shipment?</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($scanStatuses as $status)
                    @php $formId = 'scan-status-' . $status->id; @endphp
                    <tr class="border-b border-line last:border-0">
                        <td class="px-5 py-3">
                            <input form="{{ $formId }}" type="number" name="sort_order" value="{{ $status->sort_order }}"
                                   class="w-16 rounded-md border border-line px-2 py-1 text-sm outline-none focus:border-[var(--brand-primary)]">
                        </td>
                        <td class="px-5 py-3 font-mono text-ink-500">{{ $status->key }}</td>
                        <td class="px-5 py-3">
                            <input form="{{ $formId }}" type="text" name="label" value="{{ $status->label }}"
                                   class="w-full rounded-md border border-line px-2 py-1 text-sm outline-none focus:border-[var(--brand-primary)]">
                        </td>
                        <td class="px-5 py-3">
                            <input form="{{ $formId }}" type="checkbox" name="is_terminal" value="1" @checked($status->is_terminal)
                                   class="rounded border-line">
                        </td>
                        <td class="px-5 py-3 text-right">
                            <form id="{{ $formId }}" method="POST" action="{{ route('scan-statuses.update', $status) }}"></form>
                            <input form="{{ $formId }}" type="hidden" name="_token" value="{{ csrf_token() }}">
                            <input form="{{ $formId }}" type="hidden" name="_method" value="PUT">
                            <button form="{{ $formId }}" type="submit" class="text-sm font-medium text-[var(--brand-primary)] hover:underline">Save</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-5 py-8 text-center text-sm text-ink-500">No scan statuses configured yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6 rounded-xl border border-line bg-surface-0 shadow-sm p-5">
        <h2 class="mb-4 text-sm font-semibold text-ink-900">Add a scan status</h2>
        <form method="POST" action="{{ route('scan-statuses.store') }}" class="flex flex-wrap items-end gap-4">
            @csrf
            <div>
                <label class="mb-1 block text-sm font-medium text-ink-900">Key <x-required /></label>
                <input type="text" name="key" placeholder="e.g. customs_hold" value="{{ old('key') }}"
                       class="w-48 rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                <p class="mt-1 text-xs text-ink-500">Letters, numbers, dashes/underscores. Cannot change later.</p>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-ink-900">Label <x-required /></label>
                <input type="text" name="label" placeholder="Customs Hold" value="{{ old('label') }}"
                       class="w-48 rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
            </div>
            <label class="flex items-center gap-2 pb-2 text-sm text-ink-900">
                <input type="checkbox" name="is_terminal" value="1" class="rounded border-line">
                Ends shipment
            </label>
            <button type="submit" class="rounded-md bg-[var(--brand-primary)] px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:opacity-90 hover:shadow-md">
                Add status
            </button>
        </form>
    </div>

</x-layouts.app>
