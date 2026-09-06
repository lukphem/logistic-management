    <p class="mb-4 text-sm text-ink-500">
        Industry-standard cost-based freight rating: Base Haul Rate + Weight Charge + Distance Charge, floored at the
        Minimum Trip Charge, then Fuel Surcharge on top — plus an Empty Return charge when the shipper marks a trip as
        empty-return at booking. Priced per lane (origin to destination, state/city or country) and vehicle type.
    </p>

    <div class="mb-5 flex items-center justify-between gap-3">
        <x-csv-actions :export-route="route('fleet-billing.export')" :import-route="route('fleet-billing.import')" label="Fleet Billing" />
        <div class="flex gap-2">
            <a href="{{ route('vehicle-types.index') }}" class="rounded-md border border-line px-4 py-2 text-sm font-medium text-ink-900 shadow-sm transition hover:bg-surface-50">
                Vehicle types
            </a>
            <a href="{{ route('fleet-billing.create') }}" class="rounded-md bg-[var(--brand-primary)] px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:opacity-90 hover:shadow-md">
                + Add fleet rate
            </a>
        </div>
    </div>

    <div class="overflow-x-auto rounded-xl border border-line bg-surface-0 shadow-sm">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="border-b border-line text-xs uppercase tracking-wide text-ink-500">
                    <th class="px-5 py-3 font-medium">Service type</th>
                    <th class="px-5 py-3 font-medium">Vehicle</th>
                    <th class="px-5 py-3 font-medium">Origin</th>
                    <th class="px-5 py-3 font-medium">Destination</th>
                    <th class="px-5 py-3 font-medium">Haul rate</th>
                    <th class="px-5 py-3 font-medium">Distance</th>
                    <th class="px-5 py-3 font-medium">Min. trip</th>
                    <th class="px-5 py-3 font-medium">Fuel %</th>
                    <th class="px-5 py-3 font-medium">Status</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($fleetBillingTariffs as $tariff)
                    <tr class="border-b border-line last:border-0 odd:bg-surface-0 even:bg-surface-50/50 hover:bg-[var(--brand-primary)]/5 transition-colors">
                        <td class="px-5 py-3 font-medium text-ink-900">{{ $tariff->serviceType->name }}</td>
                        <td class="px-5 py-3 text-ink-900">{{ $tariff->vehicleType->name }}</td>
                        <td class="px-5 py-3 text-ink-900">{{ $tariff->originLabel() }}</td>
                        <td class="px-5 py-3 text-ink-900">{{ $tariff->destinationLabel() }}</td>
                        <td class="px-5 py-3 font-mono text-ink-900">{{ number_format($tariff->base_haul_rate, 2) }}</td>
                        <td class="px-5 py-3 text-ink-500">{{ $tariff->distance_km ? rtrim(rtrim(number_format($tariff->distance_km, 2), '0'), '.') . 'km' : '—' }}</td>
                        <td class="px-5 py-3 font-mono text-ink-900">{{ number_format($tariff->minimum_trip_charge, 2) }}</td>
                        <td class="px-5 py-3 text-ink-500">{{ rtrim(rtrim(number_format($tariff->fuel_surcharge_percentage, 2), '0'), '.') }}%</td>
                        <td class="px-5 py-3">
                            @if ($tariff->is_active)
                                <span class="inline-flex items-center rounded-full bg-status-delivered/10 px-2.5 py-0.5 text-xs font-medium text-status-delivered">Active</span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-ink-500/10 px-2.5 py-0.5 text-xs font-medium text-ink-500">Inactive</span>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-right">
                            <a href="{{ route('fleet-billing.edit', $tariff) }}" class="text-sm font-medium text-[var(--brand-primary)] hover:underline">Edit</a>
                            <form method="POST" action="{{ route('fleet-billing.destroy', $tariff) }}" class="inline" onsubmit="return confirm('Remove this fleet rate?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="ml-3 text-sm font-medium text-status-exception transition-colors hover:text-status-exception/70">Remove</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="px-5 py-8 text-center text-sm text-ink-500">No fleet rates configured yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-5">{{ $fleetBillingTariffs->links() }}</div>
