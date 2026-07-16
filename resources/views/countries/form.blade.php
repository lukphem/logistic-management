<x-layouts.app :title="$country->exists ? 'Edit Country' : 'Add Country'">

    <form method="POST" action="{{ $country->exists ? route('countries.update', $country) : route('countries.store') }}" class="max-w-xl space-y-4 rounded-xl border border-line bg-surface-0 shadow-sm p-5">
        @csrf
        @if ($country->exists) @method('PUT') @endif

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
            <label class="mb-1 block text-sm font-medium text-ink-900">Country name <x-required /></label>
            <input type="text" name="name" value="{{ old('name', $country->name) }}" placeholder="e.g. Nigeria"
                   class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-ink-900">Code (ISO) <x-required /></label>
            <input type="text" name="code" value="{{ old('code', $country->code) }}" placeholder="e.g. NG"
                   class="w-full max-w-[10rem] rounded-md border border-line px-3 py-2 text-sm font-mono uppercase outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
        </div>

        <div class="flex justify-end gap-3 pt-2">
            <a href="{{ route('countries.index') }}" class="rounded-md px-4 py-2 text-sm font-medium text-ink-500 hover:bg-surface-50">Cancel</a>
            <button type="submit" class="rounded-md bg-[var(--brand-primary)] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 hover:shadow-md">
                {{ $country->exists ? 'Save changes' : 'Add country' }}
            </button>
        </div>
    </form>

</x-layouts.app>
