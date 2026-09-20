<x-layouts.app :title="'Print Documents'">

    <div class="mb-6">
        <p class="text-2xl font-semibold text-ink-900">Print Documents</p>
        <p class="mt-1 text-sm text-ink-500">Enter a tracking number for a waybill, or a manifest/trip number for its document.</p>
    </div>

    @if ($errors->any())
        <div class="mb-4 rounded-xl border border-status-exception/30 bg-status-exception/5 px-4 py-3 text-sm text-status-exception">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="max-w-md rounded-xl border border-line bg-surface-0 shadow-sm p-6">
        <form method="POST" action="{{ route('print-documents.lookup') }}">
            @csrf
            <label class="mb-1 block text-sm font-medium text-ink-900">Number</label>
            <input type="text" name="number" required autofocus placeholder="e.g. LM260913WAAORT, MAN-260913-ABCDE, or TRIP-260913-XYZAB"
                   class="w-full rounded-md border border-line px-3 py-2.5 text-sm font-mono outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
            <button type="submit" class="mt-4 w-full rounded-md bg-[var(--brand-primary)] px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:opacity-90">
                Open document
            </button>
        </form>
    </div>

</x-layouts.app>
