<x-layouts.app :title="'States / Provinces'">

    @if (session('status'))
        <div class="mb-5 rounded-xl bg-status-delivered/10 px-4 py-3 text-sm font-medium text-status-delivered">
            {{ session('status') }}
        </div>
    @endif

    <x-csv-actions :export-route="route('states.export')" :import-route="route('states.import')" label="States" />

    <div class="mb-5 flex items-center justify-between gap-4">
        <form method="GET" class="flex items-center gap-3">
            <select name="country_id" onchange="this.form.submit()"
                    class="rounded-md border border-line bg-surface-0 px-3 py-2 text-sm text-ink-900 outline-none focus:border-[var(--brand-primary)]">
                <option value="">All countries</option>
                @foreach ($countries as $country)
                    <option value="{{ $country->id }}" @selected(request('country_id') == $country->id)>{{ $country->name }}</option>
                @endforeach
            </select>
        </form>
        <a href="{{ route('states.create') }}" class="rounded-md bg-[var(--brand-primary)] px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:opacity-90 hover:shadow-md">
            + Add state/province
        </a>
    </div>

    <div class="overflow-x-auto rounded-xl border border-line bg-surface-0 shadow-sm">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="border-b border-line text-xs uppercase tracking-wide text-ink-500">
                    <th class="px-5 py-3 font-medium">Name</th>
                    <th class="px-5 py-3 font-medium">Country</th>
                    <th class="px-5 py-3 font-medium">Code</th>
                    <th class="px-5 py-3 font-medium">Postal code</th>
                    <th class="px-5 py-3 font-medium">Cities</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($states as $state)
                    <tr class="border-b border-line last:border-0 odd:bg-surface-0 even:bg-surface-50/50 hover:bg-[var(--brand-primary)]/5 transition-colors">
                        <td class="px-5 py-3 font-medium text-ink-900">{{ $state->name }}</td>
                        <td class="px-5 py-3 text-ink-500">{{ $state->country->name }}</td>
                        <td class="px-5 py-3 font-mono text-ink-500">{{ $state->code }}</td>
                        <td class="px-5 py-3 text-ink-500">{{ $state->postal_code ?? '—' }}</td>
                        <td class="px-5 py-3 text-ink-500">{{ $state->cities_count }}</td>
                        <td class="px-5 py-3 text-right">
                            <a href="{{ route('states.edit', $state) }}" class="text-sm font-medium text-[var(--brand-primary)] hover:underline">Edit</a>
                            <form method="POST" action="{{ route('states.destroy', $state) }}" class="inline" onsubmit="return confirm('Remove this state/province? Its cities will simply become unassigned, not deleted.')">
                                @csrf @method('DELETE')
                                <button type="submit" class="ml-3 text-sm font-medium text-status-exception transition-colors hover:text-status-exception/70">Remove</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-5 py-8 text-center text-sm text-ink-500">No states/provinces configured yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-5">{{ $states->links() }}</div>

</x-layouts.app>
