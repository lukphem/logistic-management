<x-layouts.app :title="'Regions'">

    @if (session('status'))
        <div class="mb-5 rounded-xl bg-status-delivered/10 px-4 py-3 text-sm font-medium text-status-delivered">
            {{ session('status') }}
        </div>
    @endif

    <div class="mb-5 flex items-center justify-between">
        <p class="text-sm text-ink-500">Groups multiple hubs — used for region-level staff access as well as reporting.</p>
        <a href="{{ route('regions.create') }}" class="rounded-md bg-[var(--brand-primary)] px-4 py-2 text-sm font-medium text-white hover:opacity-90">
            + Add region
        </a>
    </div>

    <div class="overflow-hidden rounded-xl border border-line bg-surface-0 shadow-sm">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="border-b border-line text-xs uppercase tracking-wide text-ink-500">
                    <th class="px-5 py-3 font-medium">Name</th>
                    <th class="px-5 py-3 font-medium">Code</th>
                    <th class="px-5 py-3 font-medium">Hubs</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($regions as $region)
                    <tr class="border-b border-line last:border-0 hover:bg-surface-50 transition-colors">
                        <td class="px-5 py-3 font-medium text-ink-900">{{ $region->name }}</td>
                        <td class="px-5 py-3 font-mono text-ink-500">{{ $region->code }}</td>
                        <td class="px-5 py-3 text-ink-500">{{ $region->hubs_count }}</td>
                        <td class="px-5 py-3 text-right">
                            <a href="{{ route('regions.edit', $region) }}" class="text-sm font-medium text-[var(--brand-primary)] hover:underline">Edit</a>
                            <form method="POST" action="{{ route('regions.destroy', $region) }}" class="inline" onsubmit="return confirm('Remove this region? Its hubs will simply become unassigned, not deleted.')">
                                @csrf @method('DELETE')
                                <button type="submit" class="ml-3 text-sm font-medium text-status-exception hover:underline">Remove</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-5 py-8 text-center text-sm text-ink-500">No regions configured yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-5">{{ $regions->links() }}</div>

</x-layouts.app>
