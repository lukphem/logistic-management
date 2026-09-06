<x-layouts.app :title="$tariff->exists ? 'Edit Route Rate' : 'Add Route Rate'">

    @if ($serviceTypes->isEmpty())
        <div class="mb-5 rounded-xl bg-status-exception/10 px-4 py-3 text-sm text-status-exception">
            No service type is set to "Origin to Destination" yet — set one under Setups → Billing → Service Types first.
        </div>
    @endif

    <form method="POST" action="{{ $tariff->exists ? route('origin-destination-billing.update', $tariff) : route('origin-destination-billing.store') }}" class="max-w-3xl space-y-4 rounded-xl border border-line bg-surface-0 shadow-sm p-5">
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
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="rounded-lg border border-line p-4">
                <p class="mb-3 text-sm font-semibold text-ink-900">Origin</p>
                <div class="space-y-3">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-ink-900">State <x-required /></label>
                        <select id="origin-state" name="origin_state_id" class="w-full rounded-md border border-line px-2 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                            <option value="">Select a state</option>
                            @foreach ($states as $state)
                                <option value="{{ $state->id }}" @selected(old('origin_state_id', $tariff->origin_state_id) == $state->id)>{{ $state->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-ink-900">City <span class="text-xs font-normal text-ink-500">(optional — leave blank for state-wide)</span></label>
                        <select id="origin-city" name="origin_city_id" class="w-full rounded-md border border-line px-2 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                            <option value="">Whole state</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="rounded-lg border border-line p-4">
                <p class="mb-3 text-sm font-semibold text-ink-900">Destination</p>
                <div class="space-y-3">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-ink-900">State <x-required /></label>
                        <select id="destination-state" name="destination_state_id" class="w-full rounded-md border border-line px-2 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                            <option value="">Select a state</option>
                            @foreach ($states as $state)
                                <option value="{{ $state->id }}" @selected(old('destination_state_id', $tariff->destination_state_id) == $state->id)>{{ $state->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-ink-900">City <span class="text-xs font-normal text-ink-500">(optional — leave blank for state-wide)</span></label>
                        <select id="destination-city" name="destination_city_id" class="w-full rounded-md border border-line px-2 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                            <option value="">Whole state</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <p class="-mt-2 text-xs text-ink-500">e.g. Abuja to Lagos (Ikeja) — a city-specific rate always wins over a state-wide one for that city.</p>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <label class="mb-1 block text-sm font-medium text-ink-900">Base weight (kg) <x-required /></label>
                <input type="number" step="0.01" min="0" name="min_weight" value="{{ old('min_weight', $tariff->min_weight) }}"
                       class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                <p class="mt-1 text-xs text-ink-500">The lightest weight this rate applies to.</p>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-ink-900">Max weight (kg) <x-required /></label>
                <input type="number" step="0.01" min="0" name="max_weight" value="{{ old('max_weight', $tariff->max_weight) }}"
                       class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                <p class="mt-1 text-xs text-ink-500">Base charge covers up to here — extra kg beyond this is billed below.</p>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-ink-900">Max weight limit (kg) <x-required /></label>
                <input type="number" step="0.01" min="0" name="max_weight_limit" value="{{ old('max_weight_limit', $tariff->max_weight_limit ?? $tariff->min_weight) }}"
                       class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                <p class="mt-1 text-xs text-ink-500">The top of this band — heavier shipments match a different rate.</p>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-ink-900">Additional weight (kg) <x-required /></label>
                <input type="number" step="0.01" min="0.01" name="additional_weight" value="{{ old('additional_weight', $tariff->additional_weight ?? 1) }}"
                       class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                <p class="mt-1 text-xs text-ink-500">The increment size overage is charged in.</p>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div>
                <label class="mb-1 block text-sm font-medium text-ink-900">Base charge <x-required /></label>
                <input type="number" step="0.01" min="0" name="base_charge" value="{{ old('base_charge', $tariff->base_charge) }}"
                       class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-ink-900">Additional charge <x-required /></label>
                <input type="number" step="0.01" min="0" name="additional_charge" value="{{ old('additional_charge', $tariff->additional_charge) }}"
                       class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-ink-900">Transit days <span class="text-xs font-normal text-ink-500">(optional)</span></label>
                <input type="number" step="1" min="0" name="transit_days" value="{{ old('transit_days', $tariff->transit_days) }}"
                       class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
            </div>
        </div>

        <label class="flex items-center gap-2 text-sm text-ink-900">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $tariff->exists ? $tariff->is_active : true)) class="rounded border-line">
            Active
        </label>

        <div class="flex justify-end gap-3 pt-2">
            <a href="{{ route('origin-destination-billing.index') }}" class="rounded-md px-4 py-2 text-sm font-medium text-ink-500 hover:bg-surface-50">Cancel</a>
            <button type="submit" class="rounded-md bg-[var(--brand-primary)] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 hover:shadow-md">
                {{ $tariff->exists ? 'Save changes' : 'Add route rate' }}
            </button>
        </div>
    </form>

    <script>
        // Simple state -> city cascade, one per side — no district level
        // needed here, this billing model only prices at state or city
        // granularity.
        (function () {
            const citiesByState = @json($cities->groupBy('state_id')->map->map(fn ($c) => ['id' => $c->id, 'name' => $c->name]));

            function wireCascade(stateSelectId, citySelectId, initialCityId) {
                const stateSelect = document.getElementById(stateSelectId);
                const citySelect = document.getElementById(citySelectId);

                function populateCities(selectCityId) {
                    citySelect.innerHTML = '<option value="">Whole state</option>';

                    (citiesByState[stateSelect.value] || []).forEach(function (city) {
                        const opt = document.createElement('option');
                        opt.value = city.id;
                        opt.textContent = city.name;
                        if (selectCityId && String(city.id) === String(selectCityId)) {
                            opt.selected = true;
                        }
                        citySelect.appendChild(opt);
                    });
                }

                stateSelect.addEventListener('change', function () {
                    populateCities();
                });

                if (stateSelect.value) {
                    populateCities(initialCityId);
                }
            }

            wireCascade('origin-state', 'origin-city', @json(old('origin_city_id', $tariff->origin_city_id)));
            wireCascade('destination-state', 'destination-city', @json(old('destination_city_id', $tariff->destination_city_id)));
        })();
    </script>

</x-layouts.app>
