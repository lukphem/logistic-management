<x-layouts.app :title="$tariff->exists ? 'Edit Tariff' : 'Add Tariff'">

    @if (session('status'))
        <div class="mb-5 rounded-xl bg-status-delivered/10 px-4 py-3 text-sm font-medium text-status-delivered">
            {{ session('status') }}
        </div>
    @endif

    @if (! $tariff->exists && ($routeType ?? null))
        <p class="mb-4 text-sm text-ink-500">Adding a <strong>{{ $routeType === 'third_party' ? 'Cross-Trade' : ucfirst($routeType) }}</strong> tariff — only matching service types are selectable below.</p>
    @endif

    @if ($tariff->exists)
        <div class="mb-5 max-w-2xl">
            <x-csv-actions :export-route="route('standard-billing.zone-prices.export', $tariff)" :import-route="route('standard-billing.zone-prices.import', $tariff)" label="Zone Prices" />
            <p class="-mt-3 text-xs text-ink-500">
                For bulk-adding many zones to THIS tariff at once (4 columns: zone_code, charge, additional_charge, transit_days).
                To set up multiple tariffs across multiple service types from one file instead, use the Export/Import on the
                <a href="{{ route('standard-billing.index') }}" class="text-[var(--brand-primary)] hover:underline">Standard Billing list page</a> —
                that one includes service type and weight range too.
            </p>
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
            <select id="service_type_id" name="service_type_id" class="w-full max-w-sm rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                @foreach ($serviceTypes as $serviceType)
                    <option value="{{ $serviceType->id }}" data-route-type="{{ $serviceType->route_type }}" @selected(old('service_type_id', $tariff->service_type_id) == $serviceType->id)>{{ $serviceType->name }}</option>
                @endforeach
            </select>
            @if ($serviceTypes->isEmpty())
                <p class="mt-1 text-xs text-status-exception">No service type is set to "Standard Billing" yet — set one under Setups → Billing → Service Types first.</p>
            @endif
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div>
                <label class="mb-1 block text-sm font-medium text-ink-900">Min weight (kg) <x-required /></label>
                <input type="number" step="0.01" min="0" name="min_weight" value="{{ old('min_weight', $tariff->min_weight) }}"
                       class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
                <p class="mt-1 text-xs text-ink-500">The zone's charge covers exactly this weight — anything heavier is billed in increments below.</p>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-ink-900">Max weight (kg) <x-required /></label>
                <input type="number" step="0.01" min="0" name="max_weight" value="{{ old('max_weight', $tariff->max_weight) }}"
                       class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
                <p class="mt-1 text-xs text-ink-500">The top of this tariff's band — heavier shipments match a different tariff instead.</p>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-ink-900">Additional weight (kg) <x-required /></label>
                <input type="number" step="0.01" min="0.01" name="additional_weight" value="{{ old('additional_weight', $tariff->additional_weight ?? 1) }}"
                       class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
                <p class="mt-1 text-xs text-ink-500">The increment size overage is charged in.</p>
            </div>
        </div>

        <div>
            <div class="mb-2 flex items-center justify-between">
                <label class="block text-sm font-medium text-ink-900">Zone prices <span class="text-xs font-normal text-ink-500">(optional)</span></label>
                <button type="button" id="add-zone-row" class="text-sm font-medium text-[var(--brand-primary)] hover:underline">+ Add zone</button>
            </div>

            @php
                // On a failed submission, old('zone_prices') has exactly
                // what was typed — including any rows added via "+ Add
                // zone" that don't exist in the database yet, and
                // preserving gaps left by rows removed via "Remove"
                // before submitting. Without this, every zone price
                // typed in would be wiped out by any validation error,
                // anywhere else on the form.
                $oldZonePrices = old('zone_prices');

                if ($oldZonePrices !== null) {
                    $rowsToShow = $oldZonePrices;
                } else {
                    $existingPrices = $tariff->exists ? $zonePrices : collect();
                    $rowsToShow = $existingPrices->mapWithKeys(fn ($price, $i) => [$i => [
                        'id' => $price->id,
                        'zone_id' => $price->zone_id,
                        'charge' => $price->charge,
                        'additional_charge' => $price->additional_charge,
                        'transit_days' => $price->transit_days,
                    ]])->all();
                }

                if (empty($rowsToShow)) {
                    $rowsToShow = [0 => ['id' => null, 'zone_id' => '', 'charge' => '', 'additional_charge' => '', 'transit_days' => '']];
                }
            @endphp

            <div id="zone-rows" class="space-y-2" data-next-index="{{ max(array_keys($rowsToShow)) + 1 }}">
                @foreach ($rowsToShow as $i => $row)
                    <div class="zone-row grid grid-cols-2 gap-2 sm:grid-cols-[1fr_1fr_1fr_1fr_auto] sm:items-end">
                        @if (! empty($row['id']))
                            <input type="hidden" name="zone_prices[{{ $i }}][id]" value="{{ $row['id'] }}">
                        @endif
                        <div>
                            <label class="mb-1 block text-xs font-medium text-ink-900">Zone</label>
                            <select name="zone_prices[{{ $i }}][zone_id]" class="zone-select w-full rounded-md border border-line px-2 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                                <option value="">— None —</option>
                                @foreach ($zones as $zone)
                                    <option value="{{ $zone->id }}" data-domestic="{{ $zone->applies_domestic ? 1 : 0 }}" data-international="{{ $zone->applies_international ? 1 : 0 }}" @selected((string) ($row['zone_id'] ?? '') === (string) $zone->id)>{{ $zone->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-ink-900">Charge</label>
                            <input type="number" step="0.01" min="0" name="zone_prices[{{ $i }}][charge]" value="{{ $row['charge'] ?? '' }}"
                                   class="w-full rounded-md border border-line px-2 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-ink-900">Additional charge</label>
                            <input type="number" step="0.01" min="0" name="zone_prices[{{ $i }}][additional_charge]" value="{{ $row['additional_charge'] ?? '' }}"
                                   class="w-full rounded-md border border-line px-2 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-ink-900">Transit days</label>
                            <input type="number" min="0" name="zone_prices[{{ $i }}][transit_days]" value="{{ $row['transit_days'] ?? '' }}"
                                   class="w-full rounded-md border border-line px-2 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                        </div>
                        <button type="button" class="remove-zone-row rounded-md px-2 py-2 text-sm font-medium text-status-exception hover:bg-status-exception/10">Remove</button>
                    </div>
                @endforeach
            </div>
            <p class="mt-2 text-xs text-ink-500">Leave a row's Charge blank (or remove it) to clear that zone's price. New rows need a zone and a charge to be saved.</p>
        </div>

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

    <script>
        (function () {
            const serviceTypeSelect = document.getElementById('service_type_id');
            const addButton = document.getElementById('add-zone-row');
            const container = document.getElementById('zone-rows');
            let index = parseInt(container.dataset.nextIndex, 10);

            /**
             * A tariff's applicable zones follow its Service Type's
             * route_type (Domestic / International / not set = both) —
             * same filtering principle as the Zone Mapping page
             * (Increment 69), applied here since a tariff's own
             * applicability isn't stored directly, it's inherited from
             * whichever service type it's priced under. Re-run after
             * every service-type change and after every new row is
             * added, since cloned rows start with every option visible.
             */
            function filterZoneSelects() {
                const routeType = serviceTypeSelect.selectedOptions[0]?.dataset.routeType || '';

                document.querySelectorAll('.zone-select').forEach(function (select) {
                    Array.from(select.options).forEach(function (option) {
                        if (!option.value) return; // keep "— None —" always visible

                        const matches = routeType === ''
                            || (routeType === 'domestic' && option.dataset.domestic === '1')
                            || (routeType === 'international' && option.dataset.international === '1');

                        option.hidden = !matches;
                    });

                    if (select.selectedOptions[0]?.hidden) {
                        select.value = '';
                    }
                });
            }

            serviceTypeSelect.addEventListener('change', filterZoneSelects);
            filterZoneSelects();

            function wireRemove(row) {
                row.querySelector('.remove-zone-row').addEventListener('click', function () {
                    row.remove();
                });
            }

            container.querySelectorAll('.zone-row').forEach(wireRemove);

            addButton.addEventListener('click', function () {
                const template = container.querySelector('.zone-row');
                const row = template.cloneNode(true);

                const hiddenId = row.querySelector('input[name*="[id]"]');
                if (hiddenId) hiddenId.remove();

                row.querySelectorAll('input').forEach(function (input) {
                    input.name = input.name.replace(/zone_prices\[\d+\]/, `zone_prices[${index}]`);
                    input.value = '';
                });

                row.querySelectorAll('select').forEach(function (select) {
                    select.name = select.name.replace(/zone_prices\[\d+\]/, `zone_prices[${index}]`);
                    select.selectedIndex = 0;
                });

                wireRemove(row);
                container.appendChild(row);
                index++;
                filterZoneSelects();
            });
        })();
    </script>

</x-layouts.app>
