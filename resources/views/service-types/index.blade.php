<x-layouts.app :title="'Service Types'">

    @if (session('status'))
        <div class="mb-5 rounded-xl bg-status-delivered/10 px-4 py-3 text-sm font-medium text-status-delivered">
            {{ session('status') }}
        </div>
    @endif

    <p class="mb-4 text-sm text-ink-500">
        The service levels this business offers (Express, Economy, Same-Day, etc.) — used throughout booking and
        billing wherever a shipment's service type is selected.
    </p>

    <div class="mb-5 flex items-center justify-between">
        <span></span>
        <a href="{{ route('service-types.create') }}" class="rounded-md bg-[var(--brand-primary)] px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:opacity-90 hover:shadow-md">
            + Add service type
        </a>
    </div>

    <div class="overflow-x-auto rounded-xl border border-line bg-surface-0 shadow-sm">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="border-b border-line text-xs uppercase tracking-wide text-ink-500">
                    <th class="px-5 py-3 font-medium">Name</th>
                    <th class="px-5 py-3 font-medium">Code</th>
                    <th class="px-5 py-3 font-medium">Billing model</th>
                    <th class="px-5 py-3 font-medium">Route type</th>
                    <th class="px-5 py-3 font-medium">Status</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($serviceTypes as $serviceType)
                    <tr class="border-b border-line last:border-0 odd:bg-surface-0 even:bg-surface-50/50 hover:bg-[var(--brand-primary)]/5 transition-colors">
                        <td class="px-5 py-3 font-medium text-ink-900">{{ $serviceType->name }}</td>
                        <td class="px-5 py-3 font-mono text-ink-500">{{ $serviceType->code }}</td>
                        <td class="px-5 py-3 text-ink-500">{{ \App\Models\Setting::BILLING_MODELS[$serviceType->billing_model] ?? '—' }}</td>
                        <td class="px-5 py-3 text-ink-500">{{ ucfirst($serviceType->route_type) }}{{ $serviceType->trade_direction ? ' ('.($serviceType->trade_direction === 'cross_trade' ? 'Cross-Trade' : ucfirst($serviceType->trade_direction)).')' : '' }}</td>
                        <td class="px-5 py-3">
                            @if ($serviceType->is_active)
                                <span class="inline-flex items-center rounded-full bg-status-delivered/10 px-2.5 py-0.5 text-xs font-medium text-status-delivered">Active</span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-ink-500/10 px-2.5 py-0.5 text-xs font-medium text-ink-500">Inactive</span>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-right">
                            <a href="{{ route('service-types.edit', $serviceType) }}" class="text-sm font-medium text-[var(--brand-primary)] hover:underline">Edit</a>
                            <form method="POST" action="{{ route('service-types.destroy', $serviceType) }}" class="inline" onsubmit="return confirm('Remove this service type? Shipments already using it keep their record, but it will no longer be selectable.')">
                                @csrf @method('DELETE')
                                <button type="submit" class="ml-3 text-sm font-medium text-status-exception transition-colors hover:text-status-exception/70">Remove</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-5 py-8 text-center text-sm text-ink-500">No service types configured yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-5">{{ $serviceTypes->links() }}</div>

</x-layouts.app>
