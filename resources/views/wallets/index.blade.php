<x-layouts.app title="Wallets">

    <div class="mb-6 flex items-start justify-between">
        <div>
            <p class="text-2xl font-semibold text-ink-900">Wallets</p>
            <p class="mt-1 text-sm text-ink-500">Every client account and outlet wallet — fund one, or open it to see its full transaction history.</p>
        </div>
        @can('wallets:update')
            <a href="{{ route('wallets.transfer.form') }}" class="rounded-md border border-line px-3 py-1.5 text-sm font-medium text-ink-700 hover:bg-surface-50">Transfer Funds</a>
        @endcan
    </div>

    <div class="rounded-xl border border-line bg-surface-0 shadow-sm overflow-hidden">
        @if ($wallets->isEmpty())
            <p class="p-6 text-sm text-ink-500">No wallets yet.</p>
        @else
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-line text-left text-xs uppercase tracking-wide text-ink-500">
                        <th class="p-3">Owner</th>
                        <th class="p-3">Type</th>
                        <th class="p-3">Balance</th>
                        <th class="p-3"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($wallets as $wallet)
                        <tr class="border-b border-line last:border-0">
                            <td class="p-3 font-medium text-ink-900">{{ $wallet->label() }}</td>
                            <td class="p-3 text-ink-700">{{ $wallet->owner_type === \App\Models\ClientAccount::class ? 'Client account' : 'Outlet' }}</td>
                            <td class="p-3 font-medium text-ink-900">{{ $wallet->currency }} {{ number_format($wallet->balance, 2) }}</td>
                            <td class="p-3">
                                <a href="{{ route('wallets.show', $wallet) }}" class="text-[var(--brand-primary)] hover:underline">View</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div class="mt-4">
        {{ $wallets->links() }}
    </div>

</x-layouts.app>
