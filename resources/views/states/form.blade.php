<x-layouts.app :title="$state->exists ? 'Edit State/Province' : 'Add State/Province'">

    <form method="POST" action="{{ $state->exists ? route('states.update', $state) : route('states.store') }}" class="max-w-xl space-y-4 rounded-xl border border-line bg-surface-0 shadow-sm p-5">
        @csrf
        @if ($state->exists) @method('PUT') @endif

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
            <label class="mb-1 block text-sm font-medium text-ink-900">Country <x-required /></label>
            <select name="country_id" class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                @foreach ($countries as $country)
                    <option value="{{ $country->id }}" @selected(old('country_id', $state->country_id) == $country->id)>{{ $country->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-ink-900">State/Province name <x-required /></label>
            <input type="text" name="name" value="{{ old('name', $state->name) }}" placeholder="e.g. Lagos"
                   class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-ink-900">Short code <x-required /></label>
            <input type="text" name="short_code" value="{{ old('short_code', $state->short_code) }}" placeholder="e.g. LA"
                   class="w-full max-w-[10rem] rounded-md border border-line px-3 py-2 text-sm font-mono uppercase outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
            <p class="mt-1 text-xs text-ink-500">
                The full client-facing code is composed automatically: country code + this short code.
                @if ($state->exists && $state->code)
                    Currently <span class="font-mono font-medium text-ink-900">{{ $state->code }}</span>.
                @endif
            </p>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-ink-900">Postal code <span class="text-xs font-normal text-ink-500">(optional)</span></label>
            <input type="text" name="postal_code" value="{{ old('postal_code', $state->postal_code) }}"
                   class="w-full max-w-[10rem] rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
        </div>

        <div class="flex justify-end gap-3 pt-2">
            <a href="{{ route('states.index') }}" class="rounded-md px-4 py-2 text-sm font-medium text-ink-500 hover:bg-surface-50">Cancel</a>
            <button type="submit" class="rounded-md bg-[var(--brand-primary)] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 hover:shadow-md">
                {{ $state->exists ? 'Save changes' : 'Add state/province' }}
            </button>
        </div>
    </form>

</x-layouts.app>
