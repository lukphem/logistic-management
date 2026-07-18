<x-layouts.app :title="'Rate Checker'">

    <p class="mb-5 text-sm text-ink-500">
        Check what a shipment would cost without creating one — runs the exact same pricing pipeline as a real
        booking, so the number here is always what a customer would actually be charged.
    </p>

    <form method="GET" class="max-w-2xl space-y-4 rounded-xl border border-line bg-surface-0 shadow-sm p-5">
        <div>
            <label class="mb-1 block text-sm font-medium text-ink-900">Billing model</label>
            <select id="billing-model" class="w-full max-w-sm rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                <option value="">All</option>
                @foreach (\App\Models\Setting::BILLING_MODELS as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-ink-500">Narrows the service types below to ones using this model.</p>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-ink-900">Route type</label>
            <div class="flex gap-3">
                <label class="flex flex-1 cursor-pointer items-center gap-2 rounded-lg border border-line p-3 text-sm text-ink-900 has-[:checked]:border-[var(--brand-primary)] has-[:checked]:bg-[var(--brand-primary)]/5">
                    <input type="radio" name="route_type" value="domestic" id="route-domestic"
                           @checked(request('route_type', 'domestic') === 'domestic')
                           onchange="document.getElementById('domestic-fields').style.display=''; document.getElementById('international-fields').style.display='none'; filterServiceTypes();">
                    Domestic
                </label>
                <label class="flex flex-1 cursor-pointer items-center gap-2 rounded-lg border border-line p-3 text-sm text-ink-900 has-[:checked]:border-[var(--brand-primary)] has-[:checked]:bg-[var(--brand-primary)]/5">
                    <input type="radio" name="route_type" value="international" id="route-international"
                           @checked(request('route_type') === 'international')
                           onchange="document.getElementById('domestic-fields').style.display='none'; document.getElementById('international-fields').style.display=''; filterServiceTypes();">
                    International
                </label>
            </div>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-ink-900">Service type <x-required /></label>
            <select id="service-type" name="service_type_id" class="w-full max-w-sm rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                <option value="">Select a service type</option>
                @foreach ($serviceTypes as $serviceType)
                    <option value="{{ $serviceType->id }}" data-billing-model="{{ $serviceType->billing_model }}" data-route-type="{{ $serviceType->route_type }}" @selected(request('service_type_id') == $serviceType->id)>{{ $serviceType->name }}</option>
                @endforeach
            </select>
        </div>

        <div id="domestic-fields" class="space-y-4" style="{{ request('route_type') === 'international' ? 'display:none' : '' }}">
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

        <div id="international-fields" style="{{ request('route_type') !== 'international' ? 'display:none' : '' }}">
            <label class="mb-1 block text-sm font-medium text-ink-900">Destination country</label>
            <select name="destination_country_id" class="w-full max-w-sm rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                <option value="">Select a country</option>
                @foreach ($countries as $country)
                    <option value="{{ $country->id }}" @selected(request('destination_country_id') == $country->id)>{{ $country->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-ink-900">Weight (kg) <x-required /></label>
            <input type="number" step="0.01" min="0" name="weight_kg" value="{{ request('weight_kg') }}"
                   class="w-full max-w-[10rem] rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
        </div>

        <div class="flex justify-end pt-2">
            <button type="submit" class="rounded-md bg-[var(--brand-primary)] px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 hover:shadow-md">
                Check rate
            </button>
        </div>
    </form>

    @if ($error)
        <div class="mt-6 max-w-2xl rounded-xl border border-status-exception/30 bg-status-exception/5 p-5">
            <p class="text-sm font-medium text-status-exception">Can't be rated</p>
            <p class="mt-1 text-sm text-ink-900">{{ $error }}</p>
        </div>
    @elseif ($result)
        <div class="mt-6 max-w-2xl rounded-xl border border-line bg-surface-0 p-5 shadow-sm">
            <p class="mb-4 text-sm font-semibold text-ink-900">Quote</p>
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
        const serviceTypeSelect = document.getElementById('service-type');
        const serviceTypeOptions = Array.from(serviceTypeSelect.options).slice(1);

        function filterServiceTypes() {
            const chosenModel = billingModelSelect.value;
            const chosenRoute = document.querySelector('input[name="route_type"]:checked')?.value || 'domestic';

            serviceTypeOptions.forEach(function (option) {
                const modelMatches = chosenModel === '' || option.dataset.billingModel === chosenModel;
                const routeMatches = option.dataset.routeType === '' || option.dataset.routeType === chosenRoute;
                option.hidden = !(modelMatches && routeMatches);
            });

            if (serviceTypeSelect.selectedOptions[0]?.hidden) {
                serviceTypeSelect.value = '';
            }
        }

        billingModelSelect.addEventListener('change', filterServiceTypes);
        filterServiceTypes();

        (function () {
            const citiesByState = @json($cities->groupBy('state_id')->map->map(fn ($c) => ['id' => $c->id, 'name' => $c->name]));
            const districtsByCity = @json($districts->groupBy('city_id')->map->map(fn ($d) => ['id' => $d->id, 'name' => $d->name]));

            function wireCascade(stateSelectId, citySelectId, districtSelectId) {
                const stateSelect = document.getElementById(stateSelectId);
                const citySelect = document.getElementById(citySelectId);
                const districtSelect = districtSelectId ? document.getElementById(districtSelectId) : null;

                stateSelect.addEventListener('change', function () {
                    citySelect.innerHTML = '<option value="">Select a city</option>';
                    if (districtSelect) districtSelect.innerHTML = '<option value="">Select a district</option>';

                    (citiesByState[stateSelect.value] || []).forEach(function (city) {
                        const opt = document.createElement('option');
                        opt.value = city.id;
                        opt.textContent = city.name;
                        citySelect.appendChild(opt);
                    });
                });

                if (districtSelect) {
                    citySelect.addEventListener('change', function () {
                        districtSelect.innerHTML = '<option value="">Select a district</option>';
                        (districtsByCity[citySelect.value] || []).forEach(function (district) {
                            const opt = document.createElement('option');
                            opt.value = district.id;
                            opt.textContent = district.name;
                            districtSelect.appendChild(opt);
                        });
                    });
                }
            }

            wireCascade('origin-state', 'origin-city', 'origin-district');
            wireCascade('destination-state', 'destination-city', 'destination-district');
        })();
    </script>

</x-layouts.app>
