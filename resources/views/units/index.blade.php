<x-layouts.app :title="'Units'">

    @if (session('status'))
        <div class="mb-5 rounded-xl bg-status-delivered/10 px-4 py-3 text-sm font-medium text-status-delivered">
            {{ session('status') }}
        </div>
    @endif

    <div class="mb-5 flex items-center justify-between">
        <p class="text-sm text-ink-500">
            Organizational teams within a hub (Operations, Customer Service, Dispatch, Warehouse, Finance, etc.) —
            purely for staff structure, never affects shipment visibility.
        </p>
        <a href="{{ route('units.create') }}" class="rounded-md bg-[var(--brand-primary)] px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:opacity-90 hover:shadow-md">
            + Add unit
        </a>
    </div>

    <div class="overflow-x-auto rounded-xl border border-line bg-surface-0 shadow-sm">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="border-b border-line text-xs uppercase tracking-wide text-ink-500">
                    <th class="px-5 py-3 font-medium">Name</th>
                    <th class="px-5 py-3 font-medium">Code</th>
                    <th class="px-5 py-3 font-medium">Hub</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($units as $unit)
                    <tr class="border-b border-line last:border-0 odd:bg-surface-0 even:bg-surface-50/50 hover:bg-[var(--brand-primary)]/5 transition-colors">
                        <td class="px-5 py-3 font-medium text-ink-900">{{ $unit->name }}</td>
                        <td class="px-5 py-3 font-mono text-ink-500">{{ $unit->code }}</td>
                        <td class="px-5 py-3 text-ink-500">{{ $unit->hub->name }}</td>
                        <td class="px-5 py-3 text-right">
                            <a href="{{ route('units.edit', $unit) }}" class="text-sm font-medium text-[var(--brand-primary)] hover:underline">Edit</a>
                            <form method="POST" action="{{ route('units.destroy', $unit) }}" class="inline" onsubmit="return confirm('Remove this unit? Staff assigned to it will simply become unassigned.')">
                                @csrf @method('DELETE')
                                <button type="submit" class="ml-3 text-sm font-medium text-status-exception transition-colors hover:text-status-exception/70">Remove</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-5 py-8 text-center text-sm text-ink-500">No units configured yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-5">{{ $units->links() }}</div>

</x-layouts.app>
