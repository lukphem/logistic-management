<x-layouts.app :title="'Outlets'">

    @if (session('status'))
        <div class="mb-5 rounded-xl bg-status-delivered/10 px-4 py-3 text-sm font-medium text-status-delivered">
            {{ session('status') }}
        </div>
    @endif

    <div class="mb-5 flex items-center justify-between">
        <p class="text-sm text-ink-500">Agent counters, franchise points, or pickup/drop-off spots — each reports to one hub.</p>
        <a href="{{ route('outlets.create') }}" class="rounded-md bg-[var(--brand-primary)] px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:opacity-90 hover:shadow-md">
            + Add outlet
        </a>
    </div>

    <div class="overflow-x-auto rounded-xl border border-line bg-surface-0 shadow-sm">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="border-b border-line text-xs uppercase tracking-wide text-ink-500">
                    <th class="px-5 py-3 font-medium">Name</th>
                    <th class="px-5 py-3 font-medium">Code</th>
                    <th class="px-5 py-3 font-medium">Hub</th>
                    <th class="px-5 py-3 font-medium">Address</th>
                    <th class="px-5 py-3 font-medium">Status</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($outlets as $outlet)
                    <tr class="border-b border-line last:border-0 odd:bg-surface-0 even:bg-surface-50/50 hover:bg-[var(--brand-primary)]/5 transition-colors">
                        <td class="px-5 py-3 font-medium text-ink-900">{{ $outlet->name }}</td>
                        <td class="px-5 py-3 font-mono text-ink-500">{{ $outlet->code }}</td>
                        <td class="px-5 py-3 text-ink-500">{{ $outlet->hub->name }}</td>
                        <td class="px-5 py-3 text-ink-500">{{ $outlet->address }}</td>
                        <td class="px-5 py-3">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $outlet->is_active ? 'bg-status-delivered/10 text-status-delivered' : 'bg-ink-500/10 text-ink-500' }}">
                                {{ $outlet->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-right">
                            <a href="{{ route('outlets.edit', $outlet) }}" class="text-sm font-medium text-[var(--brand-primary)] hover:underline">Edit</a>
                            <form method="POST" action="{{ route('outlets.destroy', $outlet) }}" class="inline" onsubmit="return confirm('Remove this outlet?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="ml-3 text-sm font-medium text-status-exception transition-colors hover:text-status-exception/70">Remove</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-5 py-8 text-center text-sm text-ink-500">No outlets configured yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-5">{{ $outlets->links() }}</div>

</x-layouts.app>
