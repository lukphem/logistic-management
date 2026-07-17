<x-layouts.app :title="'Territories'">

    @if (session('status'))
        <div class="mb-5 rounded-xl bg-status-delivered/10 px-4 py-3 text-sm font-medium text-status-delivered">
            {{ session('status') }}
        </div>
    @endif

    <p class="mb-4 text-sm text-ink-500">
        Groups states together for the domestic zone-tier rule (Billing → Zone Mapping) — states in the same
        territory automatically default to Zone 2 with each other.
    </p>

    <x-csv-actions :export-route="route('territories.export')" :import-route="route('territories.import')" label="Territories" />

    <div class="mb-5 flex items-center justify-between">
        <span></span>
        <a href="{{ route('territories.create') }}" class="rounded-md bg-[var(--brand-primary)] px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:opacity-90 hover:shadow-md">
            + Add territory
        </a>
    </div>

    <div class="overflow-x-auto rounded-xl border border-line bg-surface-0 shadow-sm">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="border-b border-line text-xs uppercase tracking-wide text-ink-500">
                    <th class="px-5 py-3 font-medium">Name</th>
                    <th class="px-5 py-3 font-medium">Code</th>
                    <th class="px-5 py-3 font-medium">States</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($territories as $territory)
                    <tr class="border-b border-line last:border-0 odd:bg-surface-0 even:bg-surface-50/50 hover:bg-[var(--brand-primary)]/5 transition-colors">
                        <td class="px-5 py-3 font-medium text-ink-900">{{ $territory->name }}</td>
                        <td class="px-5 py-3 font-mono text-ink-500">{{ $territory->code }}</td>
                        <td class="px-5 py-3 text-ink-500">{{ $territory->states_count }}</td>
                        <td class="px-5 py-3 text-right">
                            <a href="{{ route('territories.edit', $territory) }}" class="text-sm font-medium text-[var(--brand-primary)] hover:underline">Edit</a>
                            <form method="POST" action="{{ route('territories.destroy', $territory) }}" class="inline" onsubmit="return confirm('Remove this territory? Its states will simply become unassigned, not deleted.')">
                                @csrf @method('DELETE')
                                <button type="submit" class="ml-3 text-sm font-medium text-status-exception transition-colors hover:text-status-exception/70">Remove</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-5 py-8 text-center text-sm text-ink-500">No territories configured yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-5">{{ $territories->links() }}</div>

</x-layouts.app>
