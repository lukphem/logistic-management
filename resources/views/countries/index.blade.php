<x-layouts.app :title="'Countries'">

    @if (session('status'))
        <div class="mb-5 rounded-xl bg-status-delivered/10 px-4 py-3 text-sm font-medium text-status-delivered">
            {{ session('status') }}
        </div>
    @endif

    <div class="mb-5 flex items-center justify-between">
        <p class="text-sm text-ink-500">Top of the operating-location hierarchy: Country → State/Province → City.</p>
        <a href="{{ route('countries.create') }}" class="rounded-md bg-[var(--brand-primary)] px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:opacity-90 hover:shadow-md">
            + Add country
        </a>
    </div>

    <div class="overflow-x-auto rounded-xl border border-line bg-surface-0 shadow-sm">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="border-b border-line text-xs uppercase tracking-wide text-ink-500">
                    <th class="px-5 py-3 font-medium">Name</th>
                    <th class="px-5 py-3 font-medium">Code</th>
                    <th class="px-5 py-3 font-medium">States/Provinces</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($countries as $country)
                    <tr class="border-b border-line last:border-0 odd:bg-surface-0 even:bg-surface-50/50 hover:bg-[var(--brand-primary)]/5 transition-colors">
                        <td class="px-5 py-3 font-medium text-ink-900">{{ $country->name }}</td>
                        <td class="px-5 py-3 font-mono text-ink-500">{{ $country->code }}</td>
                        <td class="px-5 py-3 text-ink-500">{{ $country->states_count }}</td>
                        <td class="px-5 py-3 text-right">
                            <a href="{{ route('countries.edit', $country) }}" class="text-sm font-medium text-[var(--brand-primary)] hover:underline">Edit</a>
                            <form method="POST" action="{{ route('countries.destroy', $country) }}" class="inline" onsubmit="return confirm('Remove this country? Its states/cities will simply become unassigned, not deleted.')">
                                @csrf @method('DELETE')
                                <button type="submit" class="ml-3 text-sm font-medium text-status-exception transition-colors hover:text-status-exception/70">Remove</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-5 py-8 text-center text-sm text-ink-500">No countries configured yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-5">{{ $countries->links() }}</div>

</x-layouts.app>
