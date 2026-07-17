<x-layouts.app :title="'Client Billing'">

    @if (session('status'))
        <div class="mb-5 rounded-xl bg-status-delivered/10 px-4 py-3 text-sm font-medium text-status-delivered">
            {{ session('status') }}
        </div>
    @endif

    <p class="mb-5 text-sm text-ink-500">
        Every client is <strong>Standard</strong> by default and pays whatever the active rate card says. Put a client on
        <strong>Special</strong> to give them a percentage discount off the standard rate — raising the standard rate later
        still moves their price, since the discount is applied fresh at every quote, not frozen at the rate on the day it was agreed.
    </p>

    <div class="overflow-x-auto rounded-xl border border-line bg-surface-0 shadow-sm">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="border-b border-line text-xs uppercase tracking-wide text-ink-500">
                    <th class="px-5 py-3 font-medium">Client</th>
                    <th class="px-5 py-3 font-medium">Type</th>
                    <th class="px-5 py-3 font-medium">Billing</th>
                    <th class="px-5 py-3 font-medium">Discount</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($clients as $client)
                    @php $profile = $client['profile']; @endphp
                    <tr class="border-b border-line last:border-0 odd:bg-surface-0 even:bg-surface-50/50 hover:bg-[var(--brand-primary)]/5 transition-colors">
                        <td class="px-5 py-3">
                            <p class="font-medium text-ink-900">{{ $client['name'] }}</p>
                            <p class="text-xs text-ink-500">{{ $client['identifier'] }}</p>
                        </td>
                        <td class="px-5 py-3 text-ink-500">{{ $client['type'] === 'portal' ? 'Portal user' : 'API integration' }}</td>
                        <td class="px-5 py-3">
                            @if ($profile && $profile->billing_type === 'special')
                                <span class="inline-flex items-center rounded-full bg-[var(--brand-secondary)]/15 px-2.5 py-0.5 text-xs font-medium text-ink-900">Special</span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-ink-500/10 px-2.5 py-0.5 text-xs font-medium text-ink-500">Standard</span>
                            @endif
                        </td>
                        <td class="px-5 py-3 font-mono text-ink-900">
                            {{ $profile && $profile->billing_type === 'special' ? number_format($profile->discount_percentage, 1) . '%' : '—' }}
                        </td>
                        <td class="px-5 py-3 text-right">
                            <a href="{{ route('client-billing.edit', [$client['type'], $client['id']]) }}" class="text-sm font-medium text-[var(--brand-primary)] hover:underline">
                                Manage
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-5 py-8 text-center text-sm text-ink-500">No clients yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

</x-layouts.app>
