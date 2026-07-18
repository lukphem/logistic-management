<x-layouts.app :title="$tariff->exists ? 'Edit Tariff' : 'Add Tariff'">

    @if (session('status'))
        <div class="mb-5 rounded-xl bg-status-delivered/10 px-4 py-3 text-sm font-medium text-status-delivered">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ $tariff->exists ? route('standard-billing.update', $tariff) : route('standard-billing.store') }}" class="max-w-2xl space-y-4 rounded-xl border border-line bg-surface-0 shadow-sm p-5">
        @csrf
        @if ($tariff->exists) @method('PUT') @endif

        @if ($errors->any())
            <div class="rounded-md bg-status-exception/10 px-4 py-3 text-sm text-status-exception">
                <ul class="list-inside list-disc">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div>
            <label class="mb-1 block text-sm font-medium text-ink-900">Service type <x-required /></label>
            <select name="service_type_id" class="w-full max-w-sm rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                @foreach ($serviceTypes as $serviceType)
                    <option value="{{ $serviceType->id }}" @selected(old('service_type_id', $tariff->service_type_id) == $serviceType->id)>{{ $serviceType->name }}</option>
                @endforeach
            </select>
            @if ($serviceTypes->isEmpty())
                <p class="mt-1 text-xs text-status-exception">No service type is set to "Standard Billing" yet — set one under Setups → Billing → Service Types first.</p>
            @endif
        </div>

        @if ($tariff->exists)
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink-900">Min weight (kg) <x-required /></label>
                    <input type="number" step="0.01" min="0" name="min_weight" value="{{ old('min_weight', $tariff->min_weight) }}"
                           class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink-900">Max weight (kg) <x-required /></label>
                    <input type="number" step="0.01" min="0" name="max_weight" value="{{ old('max_weight', $tariff->max_weight) }}"
                           class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
                    <p class="mt-1 text-xs text-ink-500">Also the overage threshold — weight above this is billed in increments below.</p>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink-900">Additional weight (kg) <x-required /></label>
                    <input type="number" step="0.01" min="0.01" name="additional_weight" value="{{ old('additional_weight', $tariff->additional_weight ?? 1) }}"
                           class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
                    <p class="mt-1 text-xs text-ink-500">The increment size overage is charged in.</p>
                </div>
            </div>
        @else
            <div>
                <div class="mb-2 flex items-center justify-between">
                    <label class="block text-sm font-medium text-ink-900">Weight ranges <x-required /></label>
                    <button type="button" id="add-range" class="text-sm font-medium text-[var(--brand-primary)] hover:underline">+ Add another range</button>
                </div>
                <p class="mb-3 text-xs text-ink-500">
                    One tariff per range, all for the service type above — e.g. 0.5–20kg and 20.5–40kg as two rows,
                    created together. Max weight also sets the overage threshold for that range.
                </p>

                <div id="range-rows" class="space-y-4">
                    <div class="range-row rounded-lg border border-line p-3">
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-[1fr_1fr_1fr_auto] sm:items-end">
                            <div>
                                <label class="mb-1 block text-xs font-medium text-ink-900">Min weight (kg)</label>
                                <input type="number" step="0.01" min="0" name="ranges[0][min_weight]"
                                       class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-ink-900">Max weight (kg)</label>
                                <input type="number" step="0.01" min="0" name="ranges[0][max_weight]"
                                       class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-ink-900">Additional weight (kg)</label>
                                <input type="number" step="0.01" min="0.01" name="ranges[0][additional_weight]" value="1"
                                       class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                            </div>
                            <button type="button" class="remove-range hidden rounded-md px-3 py-2 text-sm font-medium text-status-exception hover:bg-status-exception/10">Remove</button>
                        </div>

                        <p class="mb-2 mt-3 text-xs font-medium text-ink-500">Zone price for this range <span class="font-normal">(optional — add the rest from the edit page after, one at a time or via CSV)</span></p>
                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                            <div>
                                <label class="mb-1 block text-xs font-medium text-ink-900">Zone</label>
                                <select name="ranges[0][zone_id]" class="w-full rounded-md border border-line px-2 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                                    <option value="">— None —</option>
                                    @foreach ($zones as $zone)
                                        <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-ink-900">Charge</label>
                                <input type="number" step="0.01" min="0" name="ranges[0][charge]"
                                       class="w-full rounded-md border border-line px-2 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-ink-900">Additional charge</label>
                                <input type="number" step="0.01" min="0" name="ranges[0][zone_additional_charge]"
                                       class="w-full rounded-md border border-line px-2 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-ink-900">Transit days</label>
                                <input type="number" min="0" name="ranges[0][transit_days]"
                                       class="w-full rounded-md border border-line px-2 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <label class="flex items-center gap-2 text-sm text-ink-900">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $tariff->exists ? $tariff->is_active : true)) class="rounded border-line">
            Active
        </label>

        <div class="flex justify-end gap-3 pt-2">
            <a href="{{ route('standard-billing.index') }}" class="rounded-md px-4 py-2 text-sm font-medium text-ink-500 hover:bg-surface-50">Cancel</a>
            <button type="submit" class="rounded-md bg-[var(--brand-primary)] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 hover:shadow-md">
                {{ $tariff->exists ? 'Save changes' : 'Create tariff' }}
            </button>
        </div>
    </form>

    @if ($tariff->exists)
        <div class="mt-8 max-w-2xl rounded-xl border border-line bg-surface-0 p-5 shadow-sm">
            <h2 class="mb-1 text-sm font-semibold text-ink-900">Zone prices</h2>
            <p class="mb-4 text-xs text-ink-500">One row per zone. For more than a couple, download the CSV, fill it in, and re-upload rather than adding them one at a time below.</p>

            <x-csv-actions :export-route="route('standard-billing.zone-prices.export', $tariff)" :import-route="route('standard-billing.zone-prices.import', $tariff)" label="Zone Prices" />

            <table class="mb-5 w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-line text-xs uppercase tracking-wide text-ink-500">
                        <th class="py-2 font-medium">Zone</th>
                        <th class="py-2 font-medium">Charge</th>
                        <th class="py-2 font-medium">Additional charge</th>
                        <th class="py-2 font-medium">Transit days</th>
                        <th class="py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($zonePrices as $price)
                        <tr class="border-b border-line last:border-0">
                            <td class="py-2 text-ink-900">{{ $price->zone->name }}</td>
                            <td class="py-2 font-mono text-ink-900">{{ number_format($price->charge, 2) }}</td>
                            <td class="py-2 font-mono text-ink-500">{{ number_format($price->additional_charge, 2) }}</td>
                            <td class="py-2 text-ink-500">{{ $price->transit_days ?? '—' }}</td>
                            <td class="py-2 text-right">
                                <form method="POST" action="{{ route('standard-billing.zone-prices.destroy', [$tariff, $price]) }}" onsubmit="return confirm('Remove this zone price?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-sm font-medium text-status-exception transition-colors hover:text-status-exception/70">Remove</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-6 text-center text-sm text-ink-500">No zone prices set yet.</td></tr>
                    @endforelse
                </tbody>
            </table>

            <form method="POST" action="{{ route('standard-billing.zone-prices.store', $tariff) }}" class="flex flex-wrap items-end gap-3">
                @csrf
                <div>
                    <label class="mb-1 block text-xs font-medium text-ink-900">Zone</label>
                    <select name="zone_id" class="w-36 rounded-md border border-line px-2 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                        @foreach ($zones as $zone)
                            <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-ink-900">Charge</label>
                    <input type="number" step="0.01" min="0" name="charge" class="w-28 rounded-md border border-line px-2 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-ink-900">Additional charge</label>
                    <input type="number" step="0.01" min="0" name="additional_charge" class="w-28 rounded-md border border-line px-2 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-ink-900">Transit days</label>
                    <input type="number" min="0" name="transit_days" class="w-24 rounded-md border border-line px-2 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                </div>
                <button type="submit" class="rounded-md bg-[var(--brand-primary)] px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:opacity-90 hover:shadow-md">
                    Save price
                </button>
            </form>
        </div>
    @endif

    <script>
        (function () {
            const addButton = document.getElementById('add-range');
            if (!addButton) return; // editing an existing tariff — no repeatable rows here

            const container = document.getElementById('range-rows');
            let index = 1;

            addButton.addEventListener('click', function () {
                const row = container.querySelector('.range-row').cloneNode(true);

                row.querySelectorAll('input').forEach(function (input) {
                    input.name = input.name.replace(/ranges\[\d+\]/, `ranges[${index}]`);
                    if (!input.name.includes('additional_weight')) {
                        input.value = '';
                    }
                });

                row.querySelectorAll('select').forEach(function (select) {
                    select.name = select.name.replace(/ranges\[\d+\]/, `ranges[${index}]`);
                    select.selectedIndex = 0;
                });

                row.querySelector('.remove-range').classList.remove('hidden');
                row.querySelector('.remove-range').addEventListener('click', function () {
                    row.remove();
                });

                container.appendChild(row);
                index++;
            });
        })();
    </script>

</x-layouts.app>
