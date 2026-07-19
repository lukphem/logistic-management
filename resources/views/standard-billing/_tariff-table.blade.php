    <div class="mb-5 flex items-center justify-between">
        <span></span>
        <a href="{{ $addTariffRoute }}" class="rounded-md bg-[var(--brand-primary)] px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:opacity-90 hover:shadow-md">
            + Add tariff
        </a>
    </div>

    <div class="overflow-x-auto rounded-xl border border-line bg-surface-0 shadow-sm">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="border-b border-line text-xs uppercase tracking-wide text-ink-500">
                    <th class="px-5 py-3 font-medium">Service type</th>
                    <th class="px-5 py-3 font-medium">Weight band</th>
                    <th class="px-5 py-3 font-medium">Additional wt.</th>
                    <th class="px-5 py-3 font-medium">Zone</th>
                    <th class="px-5 py-3 font-medium">Charge</th>
                    <th class="px-5 py-3 font-medium">Additional charge</th>
                    <th class="px-5 py-3 font-medium">Transit days</th>
                    <th class="px-5 py-3 font-medium">Status</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($tariffs as $tariff)
                    @php $rowCount = max($tariff->zonePrices->count(), 1); @endphp
                    @forelse ($tariff->zonePrices as $i => $price)
                        <tr class="border-b border-line last:border-0 odd:bg-surface-0 even:bg-surface-50/50 hover:bg-[var(--brand-primary)]/5 transition-colors">
                            @if ($i === 0)
                                <td class="px-5 py-3 font-medium text-ink-900" rowspan="{{ $rowCount }}">{{ $tariff->serviceType->name }}</td>
                                <td class="px-5 py-3 text-ink-500" rowspan="{{ $rowCount }}">{{ rtrim(rtrim(number_format($tariff->min_weight, 2), '0'), '.') }}–{{ rtrim(rtrim(number_format($tariff->max_weight, 2), '0'), '.') }} kg</td>
                                <td class="px-5 py-3 text-ink-500" rowspan="{{ $rowCount }}">{{ rtrim(rtrim(number_format($tariff->additional_weight, 2), '0'), '.') }} kg</td>
                            @endif
                            <td class="px-5 py-3 text-ink-900">{{ $price->zone->name }}</td>
                            <td class="px-5 py-3 font-mono text-ink-900">{{ number_format($price->charge, 2) }}</td>
                            <td class="px-5 py-3 font-mono text-ink-500">{{ number_format($price->additional_charge, 2) }}</td>
                            <td class="px-5 py-3 text-ink-500">{{ $price->transit_days ?? '—' }}</td>
                            @if ($i === 0)
                                <td class="px-5 py-3" rowspan="{{ $rowCount }}">
                                    @if ($tariff->is_active)
                                        <span class="inline-flex items-center rounded-full bg-status-delivered/10 px-2.5 py-0.5 text-xs font-medium text-status-delivered">Active</span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-ink-500/10 px-2.5 py-0.5 text-xs font-medium text-ink-500">Inactive</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-right" rowspan="{{ $rowCount }}">
                                    <a href="{{ route('standard-billing.edit', $tariff) }}" class="text-sm font-medium text-[var(--brand-primary)] hover:underline">Edit</a>
                                    <form method="POST" action="{{ route('standard-billing.destroy', $tariff) }}" class="inline" onsubmit="return confirm('Remove this tariff and all its zone prices?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="ml-3 text-sm font-medium text-status-exception transition-colors hover:text-status-exception/70">Remove</button>
                                    </form>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr class="border-b border-line last:border-0 odd:bg-surface-0 even:bg-surface-50/50 hover:bg-[var(--brand-primary)]/5 transition-colors">
                            <td class="px-5 py-3 font-medium text-ink-900">{{ $tariff->serviceType->name }}</td>
                            <td class="px-5 py-3 text-ink-500">{{ rtrim(rtrim(number_format($tariff->min_weight, 2), '0'), '.') }}–{{ rtrim(rtrim(number_format($tariff->max_weight, 2), '0'), '.') }} kg</td>
                            <td class="px-5 py-3 text-ink-500">{{ rtrim(rtrim(number_format($tariff->additional_weight, 2), '0'), '.') }} kg</td>
                            <td class="px-5 py-3 text-ink-500" colspan="3">No zone prices set yet.</td>
                            <td class="px-5 py-3 text-ink-500">—</td>
                            <td class="px-5 py-3">
                                @if ($tariff->is_active)
                                    <span class="inline-flex items-center rounded-full bg-status-delivered/10 px-2.5 py-0.5 text-xs font-medium text-status-delivered">Active</span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-ink-500/10 px-2.5 py-0.5 text-xs font-medium text-ink-500">Inactive</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-right">
                                <a href="{{ route('standard-billing.edit', $tariff) }}" class="text-sm font-medium text-[var(--brand-primary)] hover:underline">Edit</a>
                                <form method="POST" action="{{ route('standard-billing.destroy', $tariff) }}" class="inline" onsubmit="return confirm('Remove this tariff and all its zone prices?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="ml-3 text-sm font-medium text-status-exception transition-colors hover:text-status-exception/70">Remove</button>
                                </form>
                            </td>
                        </tr>
                    @endforelse
                @empty
                    <tr><td colspan="9" class="px-5 py-8 text-center text-sm text-ink-500">No tariffs configured yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-5">{{ $tariffs->links() }}</div>
