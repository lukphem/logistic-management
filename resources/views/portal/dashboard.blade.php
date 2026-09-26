<x-layouts.portal title="Dashboard">

    @if (session('status'))
        <div class="mb-5 rounded-lg border border-line bg-surface-0 p-3 text-sm text-ink-700">{{ session('status') }}</div>
    @endif

    <div class="mb-6">
        <p class="text-2xl font-semibold text-ink-900">Welcome, {{ $user->name }}</p>
        @if ($account)
            <p class="mt-1 text-sm text-ink-500">{{ $account->account_name }} ({{ $account->account_number }}) — {{ ucfirst($account->account_type) }} account</p>
            @if ($account->account_type === 'individual')
                <a href="{{ route('portal.upgrade.show') }}" class="mt-1 inline-block text-sm font-medium text-[var(--brand-primary)] hover:underline">Upgrade to an organization account</a>
            @endif
        @endif
    </div>

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div class="rounded-xl border border-line bg-surface-0 shadow-sm p-5">
            <p class="text-xs uppercase tracking-wide text-ink-500">Total shipments</p>
            <p class="mt-1 text-3xl font-semibold text-ink-900">{{ $shipmentCount }}</p>
        </div>
        <div class="rounded-xl border border-line bg-surface-0 shadow-sm p-5">
            <p class="text-xs uppercase tracking-wide text-ink-500">Wallet balance</p>
            <p class="mt-1 text-3xl font-semibold text-ink-900">{{ $wallet?->currency ?? 'NGN' }} {{ number_format($wallet?->balance ?? 0, 2) }}</p>
        </div>
    </div>

    <div class="rounded-xl border border-line bg-surface-0 shadow-sm overflow-hidden">
        <p class="border-b border-line p-4 text-sm font-semibold text-ink-900">Recent shipments</p>
        @if ($shipments->isEmpty())
            <p class="p-6 text-sm text-ink-500">No shipments yet.</p>
        @else
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-line text-left text-xs uppercase tracking-wide text-ink-500">
                        <th class="p-3">Tracking Number</th>
                        <th class="p-3">Receiver</th>
                        <th class="p-3">Status</th>
                        <th class="p-3">Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($shipments as $shipment)
                        <tr class="border-b border-line last:border-0">
                            <td class="p-3 font-mono text-xs text-ink-900">{{ $shipment->tracking_number }}</td>
                            <td class="p-3 text-ink-700">{{ $shipment->receiver_name }}</td>
                            <td class="p-3"><x-status-pill :status="$shipment->current_status" /></td>
                            <td class="p-3 text-ink-700">{{ $shipment->created_at->format('d M Y') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

</x-layouts.portal>
