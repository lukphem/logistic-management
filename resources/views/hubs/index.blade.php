<x-layouts.app :title="'Hubs & Branches'">

    @if (session('status'))
        <div class="mb-5 rounded-md bg-status-delivered/10 px-4 py-3 text-sm font-medium text-status-delivered">
            {{ session('status') }}
        </div>
    @endif

    <div class="mb-5 flex items-center justify-between">
        <p class="text-sm text-ink-500">Hubs feed rider assignment and zone grouping.</p>
        <a href="{{ route('hubs.create') }}" class="rounded-md bg-[var(--brand-primary)] px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:opacity-90 hover:shadow-md">
            + Add hub
        </a>
    </div>

    <div class="overflow-hidden rounded-xl border border-line bg-surface-0 shadow-sm">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="border-b border-line text-xs uppercase tracking-wide text-ink-500">
                    <th class="px-5 py-3 font-medium">Name</th>
                    <th class="px-5 py-3 font-medium">Code</th>
                    <th class="px-5 py-3 font-medium">Region</th>
                    <th class="px-5 py-3 font-medium">City</th>
                    <th class="px-5 py-3 font-medium">Operating states</th>
                    <th class="px-5 py-3 font-medium">Address</th>
                    <th class="px-5 py-3 font-medium">Zones</th>
                    <th class="px-5 py-3 font-medium">Status</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($hubs as $hub)
                    <tr class="border-b border-line last:border-0 odd:bg-surface-0 even:bg-surface-50/50 hover:bg-[var(--brand-primary)]/5 transition-colors">
                        <td class="px-5 py-3 font-medium text-ink-900">{{ $hub->name }}</td>
                        <td class="px-5 py-3 font-mono text-ink-500">{{ $hub->code }}</td>
                        <td class="px-5 py-3 text-ink-500">{{ $hub->region?->name ?? '—' }}</td>
                        <td class="px-5 py-3 text-ink-500">{{ $hub->city?->name ?? '—' }}</td>
                        <td class="px-5 py-3 text-ink-500">{{ $hub->states_count }}</td>
                        <td class="px-5 py-3 text-ink-500">{{ $hub->address }}</td>
                        <td class="px-5 py-3 text-ink-500">{{ $hub->zones_count }}</td>
                        <td class="px-5 py-3">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $hub->is_active ? 'bg-status-delivered/10 text-status-delivered' : 'bg-ink-500/10 text-ink-500' }}">
                                {{ $hub->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-right">
                            <a href="{{ route('hubs.edit', $hub) }}" class="text-sm font-medium text-[var(--brand-primary)] hover:underline">Edit</a>
                            <form method="POST" action="{{ route('hubs.destroy', $hub) }}" class="inline" onsubmit="return confirm('Remove this hub?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="ml-3 text-sm font-medium text-status-exception transition-colors hover:text-status-exception/70">Remove</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="px-5 py-8 text-center text-sm text-ink-500">No hubs configured yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-5">{{ $hubs->links() }}</div>

</x-layouts.app>
