<x-layouts.app :title="'Origin to Destination'">

    @if (session('status'))
        <div class="mb-5 rounded-xl bg-status-delivered/10 px-4 py-3 text-sm font-medium text-status-delivered">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-5 rounded-xl bg-status-exception/10 px-4 py-3 text-sm text-status-exception">
            {{ $errors->first() }}
        </div>
    @endif

    <p class="mb-4 text-sm text-ink-500">
        Prices a specific route directly — origin to destination, state or a specific city within a state on either
        side — with no zone involved at all. A shipment matches by service type, route, and weight; a city-specific
        rate always wins over a state-wide one for that city, and a state-wide rate is the fallback everywhere else.
    </p>

    <x-csv-actions :export-route="route('origin-destination-billing.export')" :import-route="route('origin-destination-billing.import')" label="Origin to Destination" />
    <p class="mb-5 -mt-3 text-xs text-ink-500">
        Origin/Destination Code is the state's short code, with an optional city short code alongside for a
        city-specific rate (e.g. state code "LA", city code "IKJ" for Lagos/Ikeja specifically) — Product Code is the
        service type's code. Import creates rates that don't exist yet and updates ones that do, matched by service
        type + exact route + weight band.
    </p>

    <div class="mb-5 flex items-center justify-between">
        <span></span>
        <a href="{{ route('origin-destination-billing.create') }}" class="rounded-md bg-[var(--brand-primary)] px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:opacity-90 hover:shadow-md">
            + Add route rate
        </a>
    </div>

    <div class="overflow-x-auto rounded-xl border border-line bg-surface-0 shadow-sm">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="border-b border-line text-xs uppercase tracking-wide text-ink-500">
                    <th class="px-5 py-3 font-medium">Service type</th>
                    <th class="px-5 py-3 font-medium">Origin</th>
                    <th class="px-5 py-3 font-medium">Destination</th>
                    <th class="px-5 py-3 font-medium">Weight band</th>
                    <th class="px-5 py-3 font-medium">Base charge</th>
                    <th class="px-5 py-3 font-medium">Additional</th>
                    <th class="px-5 py-3 font-medium">Transit days</th>
                    <th class="px-5 py-3 font-medium">Status</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($tariffs as $tariff)
                    <tr class="border-b border-line last:border-0 odd:bg-surface-0 even:bg-surface-50/50 hover:bg-[var(--brand-primary)]/5 transition-colors">
                        <td class="px-5 py-3 font-medium text-ink-900">{{ $tariff->serviceType->name }}</td>
                        <td class="px-5 py-3 text-ink-900">{{ $tariff->originLabel() }}</td>
                        <td class="px-5 py-3 text-ink-900">{{ $tariff->destinationLabel() }}</td>
                        <td class="px-5 py-3 text-ink-500">
                            {{ rtrim(rtrim(number_format($tariff->min_weight, 2), '0'), '.') }}–{{ rtrim(rtrim(number_format($tariff->max_weight, 2), '0'), '.') }} kg
                            @if ((float) $tariff->max_weight_limit !== (float) $tariff->min_weight)
                                <br><span class="text-xs">overage from {{ rtrim(rtrim(number_format($tariff->max_weight_limit, 2), '0'), '.') }} kg</span>
                            @endif
                        </td>
                        <td class="px-5 py-3 font-mono text-ink-900">{{ number_format($tariff->base_charge, 2) }}</td>
                        <td class="px-5 py-3 text-ink-500">
                            <span class="font-mono">{{ number_format($tariff->additional_charge, 2) }}</span>
                            <span class="text-xs">/ {{ rtrim(rtrim(number_format($tariff->additional_weight, 2), '0'), '.') }}kg</span>
                        </td>
                        <td class="px-5 py-3 text-ink-500">{{ $tariff->transit_days ?? '—' }}</td>
                        <td class="px-5 py-3">
                            @if ($tariff->is_active)
                                <span class="inline-flex items-center rounded-full bg-status-delivered/10 px-2.5 py-0.5 text-xs font-medium text-status-delivered">Active</span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-ink-500/10 px-2.5 py-0.5 text-xs font-medium text-ink-500">Inactive</span>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-right">
                            <a href="{{ route('origin-destination-billing.edit', $tariff) }}" class="text-sm font-medium text-[var(--brand-primary)] hover:underline">Edit</a>
                            <form method="POST" action="{{ route('origin-destination-billing.destroy', $tariff) }}" class="inline" onsubmit="return confirm('Remove this route rate?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="ml-3 text-sm font-medium text-status-exception transition-colors hover:text-status-exception/70">Remove</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="px-5 py-8 text-center text-sm text-ink-500">No route rates configured yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-5">{{ $tariffs->links() }}</div>

</x-layouts.app>
