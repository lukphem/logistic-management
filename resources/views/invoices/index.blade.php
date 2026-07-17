<x-layouts.app :title="'Invoices'">

    <p class="mb-4 text-sm text-ink-500">
        A billing statement per shipment — there's no separate invoice document in this system, each shipment's own
        breakdown (visible on its detail page) is the invoice.
    </p>

    <form method="GET" class="mb-5 flex flex-wrap items-center gap-3">
        <select name="client_user_id" onchange="this.form.submit()"
                class="rounded-md border border-line bg-surface-0 px-3 py-2 text-sm text-ink-900 outline-none focus:border-[var(--brand-primary)]">
            <option value="">All portal clients</option>
            @foreach ($portalClients as $client)
                <option value="{{ $client->id }}" @selected(request('client_user_id') == $client->id)>{{ $client->name }}</option>
            @endforeach
        </select>
        <select name="api_client_id" onchange="this.form.submit()"
                class="rounded-md border border-line bg-surface-0 px-3 py-2 text-sm text-ink-900 outline-none focus:border-[var(--brand-primary)]">
            <option value="">All API integrations</option>
            @foreach ($apiClients as $apiClient)
                <option value="{{ $apiClient->id }}" @selected(request('api_client_id') == $apiClient->id)>{{ $apiClient->name }}</option>
            @endforeach
        </select>
        <input type="date" name="from" value="{{ request('from') }}" onchange="this.form.submit()"
               class="rounded-md border border-line bg-surface-0 px-3 py-2 text-sm text-ink-900 outline-none focus:border-[var(--brand-primary)]">
        <input type="date" name="to" value="{{ request('to') }}" onchange="this.form.submit()"
               class="rounded-md border border-line bg-surface-0 px-3 py-2 text-sm text-ink-900 outline-none focus:border-[var(--brand-primary)]">
    </form>

    <div class="overflow-x-auto rounded-xl border border-line bg-surface-0 shadow-sm">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="border-b border-line text-xs uppercase tracking-wide text-ink-500">
                    <th class="px-5 py-3 font-medium">Tracking No.</th>
                    <th class="px-5 py-3 font-medium">Client</th>
                    <th class="px-5 py-3 font-medium">Date</th>
                    <th class="px-5 py-3 font-medium text-right">Base</th>
                    <th class="px-5 py-3 font-medium text-right">Onforwarding</th>
                    <th class="px-5 py-3 font-medium text-right">VAT</th>
                    <th class="px-5 py-3 font-medium text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($shipments as $shipment)
                    <tr class="border-b border-line last:border-0 odd:bg-surface-0 even:bg-surface-50/50 hover:bg-[var(--brand-primary)]/5 transition-colors">
                        <td class="px-5 py-3">
                            <a href="{{ route('shipments.show', $shipment) }}" class="font-mono text-sm text-[var(--brand-primary)] hover:underline">
                                {{ $shipment->tracking_number }}
                            </a>
                        </td>
                        <td class="px-5 py-3 text-ink-500">{{ $shipment->clientUser?->name ?? $shipment->apiClient?->name ?? '—' }}</td>
                        <td class="px-5 py-3 text-ink-500">{{ $shipment->created_at->format('d M Y') }}</td>
                        <td class="px-5 py-3 text-right font-mono text-ink-900">{{ number_format($shipment->base_amount, 2) }}</td>
                        <td class="px-5 py-3 text-right font-mono text-ink-900">{{ number_format($shipment->onforwarding_amount, 2) }}</td>
                        <td class="px-5 py-3 text-right font-mono text-ink-900">{{ number_format($shipment->vat_amount, 2) }}</td>
                        <td class="px-5 py-3 text-right font-mono font-semibold text-ink-900">{{ number_format($shipment->total_amount, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-5 py-8 text-center text-sm text-ink-500">No shipments match this filter.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-5">{{ $shipments->links() }}</div>

</x-layouts.app>
