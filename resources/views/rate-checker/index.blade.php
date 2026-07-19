<x-layouts.app :title="'Rate Checker'">

    <p class="mb-5 text-sm text-ink-500">
        Check what a shipment would cost without creating one — runs the exact same pricing pipeline as a real
        booking, so the number here is always what a customer would actually be charged.
    </p>

    <form method="GET" class="max-w-2xl space-y-4 rounded-xl border border-line bg-surface-0 shadow-sm p-5">
        <div>
            <label class="mb-1 block text-sm font-medium text-ink-900">Billing model <x-required /></label>
            <select id="billing-model" name="billing_model" class="w-full max-w-sm rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                <option value="">Select a billing model</option>
                @foreach ($billingModels as $key => $label)
                    <option value="{{ $key }}" @selected(request('billing_model') === $key)>{{ $label }}</option>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-ink-500">The rest of this form depends on which model is picked — different models need different fields.</p>
        </div>

        <div id="model-not-implemented" class="hidden rounded-md border border-dashed border-line p-4 text-sm text-ink-500">
            This billing model hasn't been built yet — nothing to check a rate against.
        </div>

        <div id="model-fields" class="hidden space-y-4">
            <div>
                <label class="mb-1 block text-sm font-medium text-ink-900">Service type <x-required /></label>
                <select id="service-type" name="service_type_id" onchange="syncFieldsForServiceType();" class="w-full max-w-sm rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                    <option value="">Select a service type</option>
                    <optgroup label="Domestic">
                        @foreach ($serviceTypes->where('route_type', 'domestic') as $serviceType)
                            <option value="{{ $serviceType->id }}" data-billing-model="{{ $serviceType->billing_model }}" data-route-type="{{ $serviceType->route_type }}" data-trade-direction="{{ $serviceType->trade_direction }}" @selected(request('service_type_id') == $serviceType->id)>{{ $serviceType->name }}</option>
                        @endforeach
                    </optgroup>
                    <optgroup label="International">
                        @foreach ($serviceTypes->where('route_type', 'international') as $serviceType)
                            <option value="{{ $serviceType->id }}" data-billing-model="{{ $serviceType->billing_model }}" data-route-type="{{ $serviceType->route_type }}" data-trade-direction="{{ $serviceType->trade_direction }}" @selected(request('service_type_id') == $serviceType->id)>{{ $serviceType->name }}{{ $serviceType->trade_direction === 'cross_trade' ? ' (Cross-Trade)' : '' }}</option>
                        @endforeach
                    </optgroup>
                </select>
                <p class="mt-1 text-xs text-ink-500">Which fields appear below depends on this service type — domestic, international, or cross-trade.</p>
            </div>

            @php
                $selectedServiceType = request('service_type_id') ? \App\Models\ServiceType::find(request('service_type_id')) : null;
                $tradeDirection = $selectedServiceType?->trade_direction ?? 'export';
                // Which side Nigeria is on determines which field names
                // carry the Nigeria state/city vs the foreign country —
                // export: Nigeria is origin, country is destination.
                // import: Nigeria is destination, country is origin.
                $nigeriaStateField = $tradeDirection === 'import' ? 'destination_state_id' : 'origin_state_id';
                $nigeriaCityField = $tradeDirection === 'import' ? 'destination_city_id' : 'origin_city_id';
                $foreignCountryField = $tradeDirection === 'import' ? 'origin_country_id' : 'destination_country_id';
            @endphp

            <div id="domestic-fields" class="space-y-4" style="{{ $selectedServiceType?->route_type === 'international' ? 'display:none' : '' }}">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-ink-900">Origin state</label>
                        <select id="origin-state" name="origin_state_id" class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                            <option value="">Select a state</option>
                            @foreach ($states as $state)
                                <option value="{{ $state->id }}" @selected(request('origin_state_id') == $state->id)>{{ $state->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-ink-900">Origin city</label>
                        <select id="origin-city" name="origin_city_id" class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                            <option value="">Select a city</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-ink-900">Origin district <span class="text-xs font-normal text-ink-500">(optional)</span></label>
                        <select id="origin-district" name="origin_district_id" class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                            <option value="">Select a district</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-ink-900">Destination state</label>
                        <select id="destination-state" name="destination_state_id" class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                            <option value="">Select a state</option>
                            @foreach ($states as $state)
                                <option value="{{ $state->id }}" @selected(request('destination_state_id') == $state->id)>{{ $state->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-ink-900">Destination city</label>
                        <select id="destination-city" name="destination_city_id" class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                            <option value="">Select a city</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-ink-900">Destination district <span class="text-xs font-normal text-ink-500">(optional)</span></label>
                        <select id="destination-district" name="destination_district_id" class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                            <option value="">Select a district</option>
                        </select>
                    </div>
                </div>
                <p class="text-xs text-ink-500">District is only needed to preview an onforwarding surcharge tied to a specific district rather than the whole city.</p>
            </div>

            <div id="international-fields" style="{{ $selectedServiceType?->route_type !== 'international' ? 'display:none' : '' }}">
                <p id="trade-direction-label" class="mb-3 text-xs font-medium text-ink-900">
                    {{ match ($tradeDirection) { 'import' => 'Import — Nigeria is the destination', 'cross_trade' => 'Cross-Trade (Third-Country Shipping) — neither side is Nigeria', default => 'Export — Nigeria is the origin' } }}
                </p>

                <div id="intl-directional-fields" style="{{ $tradeDirection === 'cross_trade' ? 'display:none' : '' }}">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <label id="intl-nigeria-state-label" class="mb-1 block text-sm font-medium text-ink-900">Nigeria state ({{ $tradeDirection === 'import' ? 'destination' : 'origin' }})</label>
                            <select id="intl-nigeria-state" name="{{ $nigeriaStateField }}" class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                                <option value="">Select a state</option>
                                @foreach ($states as $state)
                                    <option value="{{ $state->id }}" @selected(request($nigeriaStateField) == $state->id)>{{ $state->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label id="intl-nigeria-city-label" class="mb-1 block text-sm font-medium text-ink-900">Nigeria city <span class="text-xs font-normal text-ink-500">(optional)</span></label>
                            <select id="intl-nigeria-city" name="{{ $nigeriaCityField }}" class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                                <option value="">Select a city</option>
                            </select>
                        </div>
                        <div>
                            <label id="intl-foreign-country-label" class="mb-1 block text-sm font-medium text-ink-900">Foreign country ({{ $tradeDirection === 'import' ? 'origin' : 'destination' }})</label>
                            <select id="intl-foreign-country" name="{{ $foreignCountryField }}" class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                                <option value="">Select a country</option>
                                @foreach ($countries as $country)
                                    <option value="{{ $country->id }}" @selected(request($foreignCountryField) == $country->id)>{{ $country->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <p class="mt-2 text-xs text-ink-500">Nigeria city is only needed to preview an onforwarding surcharge tied to a specific city.</p>
                </div>

                <div id="intl-crosstrade-fields" style="{{ $tradeDirection === 'cross_trade' ? '' : 'display:none' }}">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-ink-900">Origin country</label>
                            <select id="ctp-origin-country" name="origin_country_id" class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                                <option value="">Select a country</option>
                                @foreach ($countries as $country)
                                    <option value="{{ $country->id }}" @selected($tradeDirection === 'cross_trade' && request('origin_country_id') == $country->id)>{{ $country->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-ink-900">Destination country</label>
                            <select id="ctp-destination-country" name="destination_country_id" class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                                <option value="">Select a country</option>
                                @foreach ($countries as $country)
                                    <option value="{{ $country->id }}" @selected($tradeDirection === 'cross_trade' && request('destination_country_id') == $country->id)>{{ $country->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-ink-900">Weight (kg) <x-required /></label>
                <input type="number" step="0.01" min="0" name="weight_kg" value="{{ request('weight_kg') }}"
                       class="w-full max-w-[10rem] rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
            </div>

            @if ($additionalServices->isNotEmpty())
                <div>
                    <label class="mb-2 block text-sm font-medium text-ink-900">Additional services <span class="text-xs font-normal text-ink-500">(optional)</span></label>
                    <div class="space-y-2">
                        @php $selectedOptions = (array) request('additional_service_option_ids', []); @endphp
                        @foreach ($additionalServices as $service)
                            @php
                                $selectedForThisService = collect($service->options)->first(fn ($o) => in_array((string) $o->id, $selectedOptions));
                            @endphp
                            <div class="grid grid-cols-[1fr_1fr] items-center gap-2 rounded-lg border border-line p-2.5">
                                <span class="text-sm text-ink-900">{{ $service->name }}</span>
                                <select name="additional_service_option_ids[]" class="rounded-md border border-line px-2 py-1.5 text-sm outline-none focus:border-[var(--brand-primary)]">
                                    <option value="">None</option>
                                    @foreach ($service->options as $option)
                                        <option value="{{ $option->id }}" @selected($selectedForThisService?->id === $option->id)>{{ $option->name }} (+{{ $option->displayPrice() }})</option>
                                    @endforeach
                                </select>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="flex justify-end pt-2">
                <button type="submit" class="rounded-md bg-[var(--brand-primary)] px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 hover:shadow-md">
                    Check rate
                </button>
            </div>
        </div>
    </form>

    @if ($error)
        <div class="mt-6 max-w-2xl rounded-xl border border-status-exception/30 bg-status-exception/5 p-5">
            <p class="text-sm font-medium text-status-exception">Can't be rated</p>
            <p class="mt-1 text-sm text-ink-900">{{ $error }}</p>
        </div>
    @elseif ($result)
        <div class="mt-6 max-w-2xl rounded-xl border border-line bg-surface-0 p-5 shadow-sm">
            <p class="mb-1 text-sm font-semibold text-ink-900">Quote</p>
            <p class="mb-4 text-sm text-ink-500">
                {{ $result['service_type_name'] }} · {{ $result['origin_label'] ?? '—' }} → {{ $result['destination_label'] ?? '—' }} · {{ rtrim(rtrim(number_format($result['weight_kg'], 2), '0'), '.') }} kg
                @if ($result['billed_weight_kg'] && $result['billed_weight_kg'] != $result['weight_kg'])
                    <span class="text-ink-900">(billed as {{ rtrim(rtrim(number_format($result['billed_weight_kg'], 2), '0'), '.') }} kg)</span>
                @endif
            </p>
            <dl class="mb-4 grid grid-cols-2 gap-4 text-sm sm:grid-cols-4">
                <div>
                    <dt class="text-ink-500">Zone</dt>
                    <dd class="text-ink-900">{{ $result['zone_name'] ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-ink-500">Route type</dt>
                    <dd class="text-ink-900">{{ ucfirst($result['shipping_type']) }}</dd>
                </div>
                <div>
                    <dt class="text-ink-500">Transit days</dt>
                    <dd class="text-ink-900">{{ $result['transit_days'] ?? '—' }}</dd>
                </div>
            </dl>
            <dl class="space-y-1.5 border-t border-line pt-4 text-sm">
                <div class="flex justify-between"><dt class="text-ink-500">Base</dt><dd class="font-mono text-ink-900">{{ number_format($result['base_amount'], 2) }}</dd></div>
                <div class="flex justify-between"><dt class="text-ink-500">Surcharges</dt><dd class="font-mono text-ink-900">{{ number_format($result['surcharge_amount'], 2) }}</dd></div>
                @if ($result['onforwarding_amount'] > 0)
                    <div class="flex justify-between"><dt class="text-ink-500">Onforwarding</dt><dd class="font-mono text-ink-900">{{ number_format($result['onforwarding_amount'], 2) }}</dd></div>
                @endif
                @if ($result['additional_services_amount'] > 0)
                    <div class="flex justify-between"><dt class="text-ink-500">Additional services</dt><dd class="font-mono text-ink-900">{{ number_format($result['additional_services_amount'], 2) }}</dd></div>
                @endif
                @if ($result['discount_amount'] > 0)
                    <div class="flex justify-between"><dt class="text-ink-500">Discount</dt><dd class="font-mono text-status-delivered">−{{ number_format($result['discount_amount'], 2) }}</dd></div>
                @endif
                @if ($result['insurance_amount'] > 0)
                    <div class="flex justify-between"><dt class="text-ink-500">Insurance</dt><dd class="font-mono text-ink-900">{{ number_format($result['insurance_amount'], 2) }}</dd></div>
                @endif
                <div class="flex justify-between"><dt class="text-ink-500">VAT</dt><dd class="font-mono text-ink-900">{{ number_format($result['vat_amount'], 2) }}</dd></div>
                <div class="flex justify-between border-t border-line pt-1.5 text-base font-semibold"><dt class="text-ink-900">Total</dt><dd class="font-mono text-ink-900">{{ number_format($result['total_amount'], 2) }}</dd></div>
            </dl>
            <p class="mt-4 text-xs text-ink-500">No client selected, so no special discount is applied here — this is the standard price.</p>
        </div>
    @endif

    <script>
        const billingModelSelect = document.getElementById('billing-model');
        const modelFields = document.getElementById('model-fields');
        const modelNotImplemented = document.getElementById('model-not-implemented');
        // Only 'standard_billing' has a real form right now — every other
        // billing model shows the "not built yet" message instead, since
        // the fields a future model needs could be completely different.
        const implementedModels = ['standard_billing'];

        function syncModelSection() {
            const chosen = billingModelSelect.value;

            if (!chosen) {
                modelFields.classList.add('hidden');
                modelNotImplemented.classList.add('hidden');
            } else if (implementedModels.includes(chosen)) {
                modelFields.classList.remove('hidden');
                modelNotImplemented.classList.add('hidden');
            } else {
                modelFields.classList.add('hidden');
                modelNotImplemented.classList.remove('hidden');
            }

            filterServiceTypes();
        }

        billingModelSelect.addEventListener('change', syncModelSection);

        const serviceTypeSelect = document.getElementById('service-type');
        const serviceTypeOptions = Array.from(serviceTypeSelect.options).slice(1);

        function filterServiceTypes() {
            const chosenModel = billingModelSelect.value;

            serviceTypeOptions.forEach(function (option) {
                option.hidden = chosenModel !== '' && option.dataset.billingModel !== chosenModel;
            });

            if (serviceTypeSelect.selectedOptions[0]?.hidden) {
                serviceTypeSelect.value = '';
            }
        }

        /**
         * Shows the fields matching the SELECTED SERVICE TYPE's own
         * route_type — domestic or international — and DISABLES every
         * input inside the other section. display:none alone doesn't
         * stop a hidden field from being submitted, and the sections
         * can genuinely share field names (the international section's
         * two sub-views below do too). Disabled fields are excluded
         * from submission entirely, which is what actually prevents a
         * stale value from a hidden section overwriting the visible
         * one's. There's no separate Route Type control — the service
         * type chosen IS the route type, since every service type is
         * already restricted to exactly one.
         */
        function syncFieldsForServiceType() {
            const selected = serviceTypeSelect.selectedOptions[0];
            const type = selected?.dataset.routeType || 'domestic';

            const sections = {
                domestic: document.getElementById('domestic-fields'),
                international: document.getElementById('international-fields'),
            };

            Object.keys(sections).forEach(function (key) {
                const visible = key === type;
                sections[key].style.display = visible ? '' : 'none';
                sections[key].querySelectorAll('input, select').forEach(function (el) {
                    el.disabled = !visible;
                });
            });

            // Re-enabling the whole international section above also
            // re-enables BOTH its sub-views — updateTradeDirection()
            // must run after, to narrow that back down to just the one
            // sub-view that actually matches the selected service type.
            if (type === 'international') {
                updateTradeDirection();
            }

            filterServiceTypes();
        }

        /**
         * A service type's trade_direction decides which side of the
         * route Nigeria is on — export: Nigeria origin, foreign country
         * destination; import: Nigeria destination, foreign country
         * origin; cross_trade: neither side is Nigeria, a genuinely
         * different pair of fields (Origin/Destination country) rather
         * than a renamed version of the same ones. Renaming fields for
         * import/export (rather than keeping separate always-present
         * pairs) means the same state/city/country inputs work for
         * either direction with no duplicate markup; cross_trade swaps
         * to its own two fields entirely, disabling the directional
         * ones so the two sub-views can never both submit values.
         */
        function updateTradeDirection() {
            const selected = serviceTypeSelect.selectedOptions[0];
            const direction = selected?.dataset.tradeDirection || 'export';
            const isCrossTrade = direction === 'cross_trade';
            const isImport = direction === 'import';

            const directionalFields = document.getElementById('intl-directional-fields');
            const crossTradeFields = document.getElementById('intl-crosstrade-fields');

            directionalFields.style.display = isCrossTrade ? 'none' : '';
            crossTradeFields.style.display = isCrossTrade ? '' : 'none';

            directionalFields.querySelectorAll('input, select').forEach(function (el) {
                el.disabled = isCrossTrade;
            });
            crossTradeFields.querySelectorAll('input, select').forEach(function (el) {
                el.disabled = !isCrossTrade;
            });

            if (! isCrossTrade) {
                const stateSelect = document.getElementById('intl-nigeria-state');
                const citySelect = document.getElementById('intl-nigeria-city');
                const countrySelect = document.getElementById('intl-foreign-country');

                stateSelect.name = isImport ? 'destination_state_id' : 'origin_state_id';
                citySelect.name = isImport ? 'destination_city_id' : 'origin_city_id';
                countrySelect.name = isImport ? 'origin_country_id' : 'destination_country_id';

                document.getElementById('intl-nigeria-state-label').textContent = 'Nigeria state (' + (isImport ? 'destination' : 'origin') + ')';
                document.getElementById('intl-foreign-country-label').textContent = 'Foreign country (' + (isImport ? 'origin' : 'destination') + ')';
            }

            document.getElementById('trade-direction-label').textContent = isCrossTrade
                ? 'Cross-Trade (Third-Country Shipping) — neither side is Nigeria'
                : (isImport ? 'Import — Nigeria is the destination' : 'Export — Nigeria is the origin');
        }

        syncFieldsForServiceType();
        syncModelSection(); // restores the right section on reload, e.g. after "Check rate"

        (function () {
            const citiesByState = @json($cities->groupBy('state_id')->map->map(fn ($c) => ['id' => $c->id, 'name' => $c->name]));
            const districtsByCity = @json($districts->groupBy('city_id')->map->map(fn ($d) => ['id' => $d->id, 'name' => $d->name]));

            /**
             * Reloading with a state already in the query string (every
             * "Check rate" click reloads this GET form) previously left
             * City/District empty — they're entirely JS-populated and
             * only responded to a user's manual change event, never to
             * the page simply loading with a value already selected.
             * populateCities()/populateDistricts() are now shared by
             * both the change listeners AND an explicit restore step
             * below, so a reload rebuilds the full cascade and
             * re-selects what was actually chosen.
             */
            function wireCascade(stateSelectId, citySelectId, districtSelectId, initialCityId, initialDistrictId) {
                const stateSelect = document.getElementById(stateSelectId);
                const citySelect = document.getElementById(citySelectId);
                const districtSelect = districtSelectId ? document.getElementById(districtSelectId) : null;

                function populateCities(selectCityId) {
                    citySelect.innerHTML = '<option value="">Select a city</option>';
                    if (districtSelect) districtSelect.innerHTML = '<option value="">Select a district</option>';

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

                function populateDistricts(selectDistrictId) {
                    if (!districtSelect) return;

                    districtSelect.innerHTML = '<option value="">Select a district</option>';

                    (districtsByCity[citySelect.value] || []).forEach(function (district) {
                        const opt = document.createElement('option');
                        opt.value = district.id;
                        opt.textContent = district.name;
                        if (selectDistrictId && String(district.id) === String(selectDistrictId)) {
                            opt.selected = true;
                        }
                        districtSelect.appendChild(opt);
                    });
                }

                stateSelect.addEventListener('change', function () {
                    populateCities();
                });

                if (districtSelect) {
                    citySelect.addEventListener('change', function () {
                        populateDistricts();
                    });
                }

                if (stateSelect.value) {
                    populateCities(initialCityId);

                    if (districtSelect && citySelect.value) {
                        populateDistricts(initialDistrictId);
                    }
                }
            }

            wireCascade('origin-state', 'origin-city', 'origin-district', @json(request('origin_city_id')), @json(request('origin_district_id')));
            wireCascade('destination-state', 'destination-city', 'destination-district', @json(request('destination_city_id')), @json(request('destination_district_id')));
        })();

        // Same idea as wireCascade() above, simplified for the
        // international section's single Nigeria-side state/city pair
        // (no district here — the international form only asked for
        // state selection, city is a bonus for onforwarding purposes).
        (function () {
            const citiesByState = @json($cities->groupBy('state_id')->map->map(fn ($c) => ['id' => $c->id, 'name' => $c->name]));
            const stateSelect = document.getElementById('intl-nigeria-state');
            const citySelect = document.getElementById('intl-nigeria-city');

            function populateCities(selectCityId) {
                citySelect.innerHTML = '<option value="">Select a city</option>';

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
                populateCities(@json(request('origin_city_id')) || @json(request('destination_city_id')));
            }
        })();
    </script>

</x-layouts.app>
