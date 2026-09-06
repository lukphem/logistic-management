<x-layouts.app :title="$tariff->exists ? 'Edit Fleet Rate' : 'Add Fleet Rate'">

    @if ($serviceTypes->isEmpty())
        <div class="mb-5 rounded-xl bg-status-exception/10 px-4 py-3 text-sm text-status-exception">
            No service type is set to "Fleet Billing" yet — set one under Setups → Billing → Service Types first.
        </div>
    @endif

    @if ($vehicleTypes->isEmpty())
        <div class="mb-5 rounded-xl bg-status-exception/10 px-4 py-3 text-sm text-status-exception">
            No vehicle type configured yet — add one under Setups → Billing → Vehicle Types first.
        </div>
    @endif

    <form method="POST" action="{{ $tariff->exists ? route('fleet-billing.update', $tariff) : route('fleet-billing.store') }}" class="max-w-3xl space-y-4 rounded-xl border border-line bg-surface-0 shadow-sm p-5">
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

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-medium text-ink-900">Service type <x-required /></label>
                <select name="service_type_id" class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                    @foreach ($serviceTypes as $serviceType)
                        <option value="{{ $serviceType->id }}" @selected(old('service_type_id', $tariff->service_type_id) == $serviceType->id)>{{ $serviceType->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-ink-900">Vehicle type <x-required /></label>
                <select name="vehicle_type_id" class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                    @foreach ($vehicleTypes as $vehicleType)
                        <option value="{{ $vehicleType->id }}" @selected(old('vehicle_type_id', $tariff->vehicle_type_id) == $vehicleType->id)>{{ $vehicleType->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        @php
            $originType = old('origin_type', $tariff->origin_country_id ? 'country' : 'state');
            $destinationType = old('destination_type', $tariff->destination_country_id ? 'country' : 'state');
        @endphp

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="rounded-lg border border-line p-4">
                <p class="mb-3 text-sm font-semibold text-ink-900">Origin</p>
                <div class="mb-3 flex gap-3">
                    <label class="flex flex-1 cursor-pointer items-center gap-2 rounded-lg border border-line p-2 text-xs text-ink-900 has-[:checked]:border-[var(--brand-primary)] has-[:checked]:bg-[var(--brand-primary)]/5">
                        <input type="radio" name="origin_type" value="state" @checked($originType === 'state') onchange="toggleOriginDestinationType('origin', 'state');" class="rounded-full border-line">
                        Nigeria (state)
                    </label>
                    <label class="flex flex-1 cursor-pointer items-center gap-2 rounded-lg border border-line p-2 text-xs text-ink-900 has-[:checked]:border-[var(--brand-primary)] has-[:checked]:bg-[var(--brand-primary)]/5">
                        <input type="radio" name="origin_type" value="country" @checked($originType === 'country') onchange="toggleOriginDestinationType('origin', 'country');" class="rounded-full border-line">
                        Country
                    </label>
                </div>

                <div id="origin-state-fields" class="space-y-3" style="{{ $originType === 'country' ? 'display:none' : '' }}">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-ink-900">State</label>
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

                <div id="origin-country-fields" style="{{ $originType === 'country' ? '' : 'display:none' }}">
                    <label class="mb-1 block text-xs font-medium text-ink-900">Country</label>
                    <select name="origin_country_id" class="w-full rounded-md border border-line px-2 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                        <option value="">Select a country</option>
                        @foreach ($countries as $country)
                            <option value="{{ $country->id }}" @selected(old('origin_country_id', $tariff->origin_country_id) == $country->id)>{{ $country->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="rounded-lg border border-line p-4">
                <p class="mb-3 text-sm font-semibold text-ink-900">Destination</p>
                <div class="mb-3 flex gap-3">
                    <label class="flex flex-1 cursor-pointer items-center gap-2 rounded-lg border border-line p-2 text-xs text-ink-900 has-[:checked]:border-[var(--brand-primary)] has-[:checked]:bg-[var(--brand-primary)]/5">
                        <input type="radio" name="destination_type" value="state" @checked($destinationType === 'state') onchange="toggleOriginDestinationType('destination', 'state');" class="rounded-full border-line">
                        Nigeria (state)
                    </label>
                    <label class="flex flex-1 cursor-pointer items-center gap-2 rounded-lg border border-line p-2 text-xs text-ink-900 has-[:checked]:border-[var(--brand-primary)] has-[:checked]:bg-[var(--brand-primary)]/5">
                        <input type="radio" name="destination_type" value="country" @checked($destinationType === 'country') onchange="toggleOriginDestinationType('destination', 'country');" class="rounded-full border-line">
                        Country
                    </label>
                </div>

                <div id="destination-state-fields" class="space-y-3" style="{{ $destinationType === 'country' ? 'display:none' : '' }}">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-ink-900">State</label>
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

                <div id="destination-country-fields" style="{{ $destinationType === 'country' ? '' : 'display:none' }}">
                    <label class="mb-1 block text-xs font-medium text-ink-900">Country</label>
                    <select name="destination_country_id" class="w-full rounded-md border border-line px-2 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                        <option value="">Select a country</option>
                        @foreach ($countries as $country)
                            <option value="{{ $country->id }}" @selected(old('destination_country_id', $tariff->destination_country_id) == $country->id)>{{ $country->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
        <p class="-mt-2 text-xs text-ink-500">A city-specific rate always wins over a state-wide one for that city.</p>

        <div class="rounded-lg border border-line p-4">
            <p class="mb-3 text-sm font-semibold text-ink-900">Weight band</p>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <label class="mb-1 block text-xs font-medium text-ink-900">Base weight (kg) <x-required /></label>
                    <input type="number" step="0.01" min="0" name="min_weight" value="{{ old('min_weight', $tariff->min_weight) }}"
                           class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-ink-900">Max weight (kg) <x-required /></label>
                    <input type="number" step="0.01" min="0" name="max_weight" value="{{ old('max_weight', $tariff->max_weight) }}"
                           class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                    <p class="mt-1 text-xs text-ink-500">Weight charge covers up to here — extra kg beyond this is billed below.</p>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-ink-900">Max weight limit (kg) <x-required /></label>
                    <input type="number" step="0.01" min="0" name="max_weight_limit" value="{{ old('max_weight_limit', $tariff->max_weight_limit ?? $tariff->min_weight) }}"
                           class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                    <p class="mt-1 text-xs text-ink-500">The top of this band — heavier shipments match a different rate.</p>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-ink-900">Additional weight (kg) <x-required /></label>
                    <input type="number" step="0.01" min="0.01" name="additional_weight" value="{{ old('additional_weight', $tariff->additional_weight ?? 1) }}"
                           class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                </div>
            </div>
            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs font-medium text-ink-900">Weight base charge <x-required /></label>
                    <input type="number" step="0.01" min="0" name="base_charge" value="{{ old('base_charge', $tariff->base_charge ?? 0) }}"
                           class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                    <p class="mt-1 text-xs text-ink-500">The weight-band's charge — this is the entire freight amount now, with fuel surcharge and empty return added on top.</p>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-ink-900">Additional charge <x-required /></label>
                    <input type="number" step="0.01" min="0" name="additional_charge" value="{{ old('additional_charge', $tariff->additional_charge ?? 0) }}"
                           class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                </div>
            </div>
        </div>

        <div class="rounded-lg border border-line p-4">
            <p class="mb-3 text-sm font-semibold text-ink-900">Fuel surcharge & empty return</p>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div>
                    <label class="mb-1 block text-xs font-medium text-ink-900">Fuel surcharge (%) <x-required /></label>
                    <input type="number" step="0.01" min="0" max="100" name="fuel_surcharge_percentage" value="{{ old('fuel_surcharge_percentage', $tariff->fuel_surcharge_percentage ?? 0) }}"
                           class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                    <p class="mt-1 text-xs text-ink-500">Percentage of the freight total (the weight charge above).</p>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-ink-900">Empty return charge type <x-required /></label>
                    <select name="empty_return_charge_type" class="w-full rounded-md border border-line px-2 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                        @foreach (\App\Models\FleetBillingTariff::EMPTY_RETURN_CHARGE_TYPES as $key => $label)
                            <option value="{{ $key }}" @selected(old('empty_return_charge_type', $tariff->empty_return_charge_type ?? 'flat') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-ink-900">Empty return charge value <x-required /></label>
                    <input type="number" step="0.01" min="0" name="empty_return_charge_value" value="{{ old('empty_return_charge_value', $tariff->empty_return_charge_value ?? 0) }}"
                           class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                    <p class="mt-1 text-xs text-ink-500">Only charged when the shipper marks the trip as empty-return at booking.</p>
                </div>
            </div>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-ink-900">Transit days <span class="text-xs font-normal text-ink-500">(optional)</span></label>
            <input type="number" step="1" min="0" name="transit_days" value="{{ old('transit_days', $tariff->transit_days) }}"
                   class="w-full max-w-[10rem] rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
        </div>

        <label class="flex items-center gap-2 text-sm text-ink-900">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $tariff->exists ? $tariff->is_active : true)) class="rounded border-line">
            Active
        </label>

        <div class="flex justify-end gap-3 pt-2">
            <a href="{{ route('standard-billing.index', ['model' => 'fleet']) }}" class="rounded-md px-4 py-2 text-sm font-medium text-ink-500 hover:bg-surface-50">Cancel</a>
            <button type="submit" class="rounded-md bg-[var(--brand-primary)] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 hover:shadow-md">
                {{ $tariff->exists ? 'Save changes' : 'Add fleet rate' }}
            </button>
        </div>
    </form>

    <script>
        function toggleOriginDestinationType(side, type) {
            document.getElementById(side + '-state-fields').style.display = type === 'state' ? '' : 'none';
            document.getElementById(side + '-country-fields').style.display = type === 'country' ? '' : 'none';
        }

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
