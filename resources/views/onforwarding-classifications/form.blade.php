<x-layouts.app :title="$classification->exists ? 'Edit Classification' : 'Add Classification'">

    <form method="POST" action="{{ $classification->exists ? route('onforwarding-classifications.update', $classification) : route('onforwarding-classifications.store') }}" class="max-w-xl space-y-4 rounded-xl border border-line bg-surface-0 shadow-sm p-5">
        @csrf
        @if ($classification->exists) @method('PUT') @endif

        @if ($errors->any())
            <div class="rounded-md bg-status-exception/10 px-4 py-3 text-sm text-status-exception">
                <ul class="list-inside list-disc">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div>
            <label class="mb-1 block text-sm font-medium text-ink-900">Name <x-required /></label>
            <input type="text" name="name" value="{{ old('name', $classification->name) }}" placeholder="e.g. Onforwarding - Remote"
                   class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-ink-900">Surcharge amount <x-required /></label>
            <input type="number" step="0.01" min="0" name="surcharge_amount" value="{{ old('surcharge_amount', $classification->surcharge_amount) }}"
                   class="w-full max-w-[10rem] rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
            <p class="mt-1 text-xs text-ink-500">Added once per side of the shipment it applies to — if both origin and destination are onforwarding, both charges apply.</p>
        </div>

        <label class="flex items-center gap-2 text-sm text-ink-900">
            <input type="checkbox" name="is_default" value="1" @checked(old('is_default', $classification->is_default)) class="rounded border-line">
            Default (no-charge baseline classification)
        </label>

        <div class="flex justify-end gap-3 pt-2">
            <a href="{{ route('onforwarding-classifications.index') }}" class="rounded-md px-4 py-2 text-sm font-medium text-ink-500 hover:bg-surface-50">Cancel</a>
            <button type="submit" class="rounded-md bg-[var(--brand-primary)] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 hover:shadow-md">
                {{ $classification->exists ? 'Save changes' : 'Add classification' }}
            </button>
        </div>
    </form>

</x-layouts.app>
