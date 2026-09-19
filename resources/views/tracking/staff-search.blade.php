<x-layouts.app :title="'Tracking'">

    <div class="mb-6">
        <p class="text-2xl font-semibold text-ink-900">Tracking</p>
        <p class="mt-1 text-sm text-ink-500">Enter one or more tracking, manifest, or trip numbers to see full details.</p>
    </div>

    @if ($errors->any())
        <div class="mb-4 rounded-xl border border-status-exception/30 bg-status-exception/5 px-4 py-3 text-sm text-status-exception">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="max-w-lg rounded-xl border border-line bg-surface-0 shadow-sm p-6">
        <form method="POST" action="{{ route('staff-tracking.submit') }}">
            @csrf
            <label class="mb-1 block text-sm font-medium text-ink-900">Tracking number(s)</label>
            <textarea name="tracking_numbers" required autofocus rows="4" placeholder="e.g. LM260913WAAORT&#10;One per line for more than one"
                      class="w-full resize-y rounded-md border border-line px-3 py-2.5 text-sm font-mono outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">{{ old('tracking_numbers') }}</textarea>
            <p class="mt-1 text-xs text-ink-500">One per line, or separate with commas.</p>
            <button type="submit" class="mt-4 rounded-md bg-[var(--brand-primary)] px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:opacity-90">
                Track
            </button>
        </form>
    </div>

</x-layouts.app>
