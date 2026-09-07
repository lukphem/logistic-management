<x-layouts.app :title="'Clients'">

    @if (session('status'))
        <div class="mb-5 rounded-xl bg-status-delivered/10 px-4 py-3 text-sm font-medium text-status-delivered">
            {{ session('status') }}
        </div>
    @endif

    <div class="mb-5 flex items-center justify-between gap-3">
        <form method="GET" class="flex items-center gap-2">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name or email…"
                   class="w-64 rounded-md border border-line bg-surface-0 px-3 py-2 text-sm text-ink-900 outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
            <button type="submit" class="rounded-md bg-[var(--brand-primary)] px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:opacity-90 hover:shadow-md">
                Search
            </button>
        </form>

        @can('clients:create')
            <a href="{{ route('clients.create') }}" class="shrink-0 rounded-md bg-[var(--brand-primary)] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 hover:shadow-md">
                + Add client
            </a>
        @endcan
    </div>

    <div class="overflow-x-auto rounded-xl border border-line bg-surface-0 shadow-sm">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="border-b border-line text-xs uppercase tracking-wide text-ink-500">
                    <th class="px-5 py-3 font-medium">Name</th>
                    <th class="px-5 py-3 font-medium">Email</th>
                    <th class="px-5 py-3 font-medium">Type</th>
                    <th class="px-5 py-3 font-medium">Billing</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($clients as $client)
                    <tr class="border-b border-line last:border-0 odd:bg-surface-0 even:bg-surface-50/50 hover:bg-[var(--brand-primary)]/5 transition-colors">
                        <td class="px-5 py-3 font-medium text-ink-900">{{ $client->name }}</td>
                        <td class="px-5 py-3 text-ink-500">{{ $client->email }}</td>
                        <td class="px-5 py-3">
                            @if ($client->clientProfile?->isOrganization())
                                <span class="inline-flex items-center rounded-full bg-[var(--brand-primary)]/10 px-2.5 py-0.5 text-xs font-medium text-[var(--brand-primary)]">
                                    Organization
                                </span>
                                <span class="block text-xs text-ink-500">{{ $client->clientProfile->company_name }}</span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-ink-500/10 px-2.5 py-0.5 text-xs font-medium text-ink-500">
                                    Individual
                                </span>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-ink-500">
                            {{ $client->billingProfile?->billing_type === 'special' ? 'Special rate' : 'Standard' }}
                        </td>
                        <td class="px-5 py-3 text-right">
                            <a href="{{ route('clients.show', $client) }}" class="text-sm font-medium text-[var(--brand-primary)] hover:underline">View</a>
                            <span class="mx-1 text-ink-500">·</span>
                            <a href="{{ route('clients.edit', $client) }}" class="text-sm font-medium text-[var(--brand-primary)] hover:underline">Edit</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-5 py-8 text-center text-sm text-ink-500">No client accounts yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-5">{{ $clients->links() }}</div>

</x-layouts.app>
