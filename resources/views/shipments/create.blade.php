<x-layouts.app :title="'Create Shipment'">

    <p class="mb-5 text-sm text-ink-500">
        Same Route → Type → Service Type flow as the Rate Checker. Have a Quote ID? Load it below and the price is
        locked in at whatever it was quoted at. No Quote ID? Fill this in and it prices fresh, at today's rates,
        the moment you submit.
    </p>

    @if ($errors->any())
        <div class="mb-5 max-w-2xl rounded-xl border border-status-exception/30 bg-status-exception/5 p-4 text-sm text-status-exception">
            <p class="font-medium">Couldn't create this shipment</p>
            <ul class="mt-1 list-disc pl-5">
                @foreach ($errors->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="mb-5 max-w-2xl rounded-xl border border-line bg-surface-0 shadow-sm p-5">
        <label class="mb-1 block text-sm font-medium text-ink-900">Have a Quote ID?</label>
        <div class="flex gap-2">
            <input type="text" id="quote-lookup-input" placeholder="e.g. QT-7K2XPB"
                   class="w-48 rounded-md border border-line px-3 py-2 text-sm font-mono uppercase outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
            <button type="button" id="load-quote-btn" class="rounded-md border border-[var(--brand-primary)] px-4 py-2 text-sm font-semibold text-[var(--brand-primary)] transition hover:bg-[var(--brand-primary)]/5">
                Load quote
            </button>
        </div>
        <p id="quote-lookup-error" class="mt-2 hidden text-xs text-status-exception"></p>
        <div id="quote-lookup-success" class="mt-3 hidden rounded-md bg-[var(--brand-primary)]/5 px-3 py-2 text-sm text-ink-900">
            Quote <span id="loaded-quote-number" class="font-mono font-semibold"></span> loaded — locked total
            <span id="loaded-quote-total" class="font-mono font-semibold"></span>, expires
            <span id="loaded-quote-expiry"></span>. Fields below have been filled in — adjust addresses and other
            shipment details, then create the shipment.
        </div>
    </div>

    <form method="POST" action="{{ route('shipments.store') }}" id="create-shipment-form" class="max-w-2xl space-y-4 rounded-xl border border-line bg-surface-0 shadow-sm p-5">
        @csrf
        <input type="hidden" name="quote_number" id="quote-number-field" value="{{ old('quote_number') }}">
        <div>
            <label class="mb-1 block text-sm font-medium text-ink-900">Billing model <x-required /></label>
            <select id="billing-model" name="billing_model" class="w-full max-w-sm rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                <option value="">Select a billing model</option>
                @foreach ($billingModels as $key => $label)
                    <option value="{{ $key }}" @selected(old('billing_model') === $key)>{{ $label }}</option>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-ink-500">The rest of this form depends on which model is picked — different models need different fields.</p>
        </div>

        <div id="model-not-implemented" class="hidden rounded-md border border-dashed border-line p-4 text-sm text-ink-500">
            This billing model hasn't been built yet — nothing to check a rate against.
        </div>

        <div id="model-fields" class="hidden space-y-4">
            @php
                $selectedRouteType = old('route_type', 'domestic');
                // No default here on purpose — Type must be actively
                // chosen before Service Type reveals, matching the
                // sequential Route -> Type -> Service Type flow.
                $selectedTradeDirectionChoice = old('trade_direction');
                $showServiceTypeSelector = $selectedRouteType === 'domestic' || ($selectedRouteType === 'international' && $selectedTradeDirectionChoice);
            @endphp

            <div>
                <label class="mb-1 block text-sm font-medium text-ink-900">Route <x-required /></label>
                <div class="flex gap-3">
                    <label class="flex flex-1 cursor-pointer items-center gap-2 rounded-lg border border-line p-3 text-sm text-ink-900 has-[:checked]:border-[var(--brand-primary)] has-[:checked]:bg-[var(--brand-primary)]/5">
                        <input type="radio" name="route_type" value="domestic" @checked($selectedRouteType === 'domestic') onchange="onRouteTypeChange();">
                        Domestic
                    </label>
                    <label class="flex flex-1 cursor-pointer items-center gap-2 rounded-lg border border-line p-3 text-sm text-ink-900 has-[:checked]:border-[var(--brand-primary)] has-[:checked]:bg-[var(--brand-primary)]/5">
                        <input type="radio" name="route_type" value="international" @checked($selectedRouteType === 'international') onchange="onRouteTypeChange();">
                        International
                    </label>
                </div>
            </div>

            <div id="trade-direction-selector" style="{{ $selectedRouteType === 'international' ? '' : 'display:none' }}">
                <label class="mb-1 block text-sm font-medium text-ink-900">Type <x-required /></label>
                <div class="flex gap-3">
                    <label class="flex flex-1 cursor-pointer items-center gap-2 rounded-lg border border-line p-3 text-sm text-ink-900 has-[:checked]:border-[var(--brand-primary)] has-[:checked]:bg-[var(--brand-primary)]/5">
                        <input type="radio" name="trade_direction" value="export" @checked($selectedTradeDirectionChoice === 'export') onchange="onTradeDirectionChoiceChange();">
                        Export
                    </label>
                    <label class="flex flex-1 cursor-pointer items-center gap-2 rounded-lg border border-line p-3 text-sm text-ink-900 has-[:checked]:border-[var(--brand-primary)] has-[:checked]:bg-[var(--brand-primary)]/5">
                        <input type="radio" name="trade_direction" value="import" @checked($selectedTradeDirectionChoice === 'import') onchange="onTradeDirectionChoiceChange();">
                        Import
                    </label>
                    <label class="flex flex-1 cursor-pointer items-center gap-2 rounded-lg border border-line p-3 text-sm text-ink-900 has-[:checked]:border-[var(--brand-primary)] has-[:checked]:bg-[var(--brand-primary)]/5">
                        <input type="radio" name="trade_direction" value="cross_trade" @checked($selectedTradeDirectionChoice === 'cross_trade') onchange="onTradeDirectionChoiceChange();">
                        Cross-Trade
                    </label>
                </div>
            </div>

            <div id="service-type-selector" style="{{ $showServiceTypeSelector ? '' : 'display:none' }}">
                <label class="mb-1 block text-sm font-medium text-ink-900">Service type <x-required /></label>
                <select id="service-type" name="service_type_id" onchange="syncFieldsForServiceType();" class="w-full max-w-sm rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                    <option value="">Select a service type</option>
                    @foreach ($serviceTypes as $serviceType)
                        <option value="{{ $serviceType->id }}" data-billing-model="{{ $serviceType->billing_model }}" data-route-type="{{ $serviceType->route_type }}" data-trade-direction="{{ $serviceType->trade_direction }}" @selected(old('service_type_id') == $serviceType->id)>{{ $serviceType->name }}{{ $serviceType->trade_direction === 'cross_trade' ? ' (Cross-Trade)' : '' }}</option>
                    @endforeach
                </select>
            </div>

            @php
                $selectedServiceType = old('service_type_id') ? \App\Models\ServiceType::find(old('service_type_id')) : null;
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
                                <option value="{{ $state->id }}" @selected(old('origin_state_id') == $state->id)>{{ $state->name }}</option>
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
                                <option value="{{ $state->id }}" @selected(old('destination_state_id') == $state->id)>{{ $state->name }}</option>
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
                                    <option value="{{ $state->id }}" @selected(old($nigeriaStateField) == $state->id)>{{ $state->name }}</option>
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
                                    <option value="{{ $country->id }}" @selected(old($foreignCountryField) == $country->id)>{{ $country->name }}</option>
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
                                    <option value="{{ $country->id }}" @selected($tradeDirection === 'cross_trade' && old('origin_country_id') == $country->id)>{{ $country->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-ink-900">Destination country</label>
                            <select id="ctp-destination-country" name="destination_country_id" class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                                <option value="">Select a country</option>
                                @foreach ($countries as $country)
                                    <option value="{{ $country->id }}" @selected($tradeDirection === 'cross_trade' && old('destination_country_id') == $country->id)>{{ $country->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-ink-900">Weight & dimensions</label>
                <div class="flex flex-wrap items-end gap-3">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-ink-900">Weight (kg) <x-required /></label>
                        <input type="number" step="0.01" min="0" name="weight_kg" value="{{ old('weight_kg') }}"
                               class="w-24 rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-ink-900">Length (cm)</label>
                        <input type="number" step="0.1" min="0" id="dim-length" name="length_cm" value="{{ old('length_cm') }}"
                               class="w-24 rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-ink-900">Width (cm)</label>
                        <input type="number" step="0.1" min="0" id="dim-width" name="width_cm" value="{{ old('width_cm') }}"
                               class="w-24 rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-ink-900">Height (cm)</label>
                        <input type="number" step="0.1" min="0" id="dim-height" name="height_cm" value="{{ old('height_cm') }}"
                               class="w-24 rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-ink-900">Volumetric weight (kg)</label>
                        <input type="text" id="volumetric-preview" value="" readonly tabindex="-1"
                               class="w-24 rounded-md border border-line bg-surface-50 px-3 py-2 text-sm text-ink-500 outline-none">
                    </div>
                </div>
                <p class="mt-1 text-xs text-ink-500">Dimensions are optional — if given, priced by whichever is greater, actual weight or the volumetric weight they work out to.</p>
            </div>

            @if ($additionalServices->isNotEmpty())
                <div>
                    <label class="mb-2 block text-sm font-medium text-ink-900">Additional services <span class="text-xs font-normal text-ink-500">(optional)</span></label>
                    <div class="space-y-2">
                        @php $selectedOptions = (array) old('additional_service_option_ids', []); @endphp
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
        </div>

        <div class="space-y-4 border-t border-line pt-4">
            <p class="text-sm font-semibold text-ink-900">Shipment details</p>

            <div>
                <label class="mb-1 block text-sm font-medium text-ink-900">Client <span class="text-xs font-normal text-ink-500">(optional — leave blank for a walk-in customer)</span></label>
                <select name="client_user_id" class="w-full max-w-sm rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                    <option value="">Walk-in customer</option>
                    @foreach ($clients as $client)
                        <option value="{{ $client->id }}" @selected(old('client_user_id') == $client->id)>{{ $client->name }} ({{ $client->email }})</option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink-900">Origin address <x-required /></label>
                    <textarea name="origin_address" rows="2" required
                              class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">{{ old('origin_address') }}</textarea>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink-900">Destination address <x-required /></label>
                    <textarea name="destination_address" rows="2" required
                              class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">{{ old('destination_address') }}</textarea>
                </div>
            </div>

            <div class="flex flex-wrap gap-6">
                <div>
                    <label class="flex cursor-pointer items-center gap-2 text-sm text-ink-900">
                        <input type="checkbox" id="is-cod" name="is_cod" value="1" @checked(old('is_cod')) class="rounded border-line">
                        Cash on delivery
                    </label>
                    <input type="number" step="0.01" min="0" id="cod-amount" name="cod_amount" value="{{ old('cod_amount') }}" placeholder="Amount to collect"
                           class="mt-2 w-40 rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20" {{ old('is_cod') ? '' : 'disabled' }}>
                </div>
                <div>
                    <label class="flex cursor-pointer items-center gap-2 text-sm text-ink-900">
                        <input type="checkbox" id="is-insured" name="insured" value="1" @checked(old('insured')) class="rounded border-line">
                        Insure this shipment
                    </label>
                    <input type="number" step="0.01" min="0" id="declared-value" name="declared_value" value="{{ old('declared_value') }}" placeholder="Declared value"
                           class="mt-2 w-40 rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20" {{ old('insured') ? '' : 'disabled' }}>
                    <p class="mt-1 text-xs text-ink-500">1% of declared value. Entered here, at booking — never part of a Quote ID's frozen price, so this is always added fresh.</p>
                </div>
            </div>

            <div class="flex justify-end pt-2">
                <button type="submit" class="rounded-md bg-[var(--brand-primary)] px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 hover:shadow-md">
                    Create shipment
                </button>
            </div>
        </div>
    </form>

    <script>
        // Live preview only — the real calculation happens server-side
        // in PricingEngine using the same divisor, this just saves a
        // round trip to see roughly what a set of dimensions works out
        // to before checking the actual rate.
        (function () {
            const divisor = @json((float) $volumetricDivisor);
            const lengthInput = document.getElementById('dim-length');
            const widthInput = document.getElementById('dim-width');
            const heightInput = document.getElementById('dim-height');
            const preview = document.getElementById('volumetric-preview');

            function updatePreview() {
                const l = parseFloat(lengthInput.value) || 0;
                const w = parseFloat(widthInput.value) || 0;
                const h = parseFloat(heightInput.value) || 0;

                if (l > 0 && w > 0 && h > 0) {
                    const volumetric = (l * w * h) / divisor;
                    preview.value = volumetric.toFixed(2).replace(/\.?0+$/, '');
                } else {
                    preview.value = '';
                }
            }

            [lengthInput, widthInput, heightInput].forEach(function (input) {
                input.addEventListener('input', updatePreview);
            });

            updatePreview();
        })();

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
        const tradeDirectionSelector = document.getElementById('trade-direction-selector');
        const serviceTypeSelector = document.getElementById('service-type-selector');

        /**
         * Service Type only ever shows options matching every choice
         * made above it — Billing Model, Route, and (for International)
         * Type — since a service type not matching all three couldn't
         * actually be booked under this combination anyway.
         */
        function filterServiceTypes() {
            const chosenModel = billingModelSelect.value;
            const chosenRoute = document.querySelector('input[name="route_type"]:checked')?.value || 'domestic';
            const chosenDirection = document.querySelector('input[name="trade_direction"]:checked')?.value;

            serviceTypeOptions.forEach(function (option) {
                const modelMatches = chosenModel === '' || option.dataset.billingModel === chosenModel;
                const routeMatches = option.dataset.routeType === chosenRoute;
                const directionMatches = chosenRoute !== 'international' || option.dataset.tradeDirection === chosenDirection;
                option.hidden = !(modelMatches && routeMatches && directionMatches);
            });

            if (serviceTypeSelect.selectedOptions[0]?.hidden) {
                serviceTypeSelect.value = '';
            }
        }

        /**
         * Route decides whether Type even applies — Domestic reveals
         * Service Type immediately, International reveals Type first
         * and hides Service Type until one is actively chosen (no
         * default pre-selected server-side either, see the top of
         * this form).
         */
        function onRouteTypeChange() {
            const isInternational = document.querySelector('input[name="route_type"]:checked')?.value === 'international';

            tradeDirectionSelector.style.display = isInternational ? '' : 'none';

            if (isInternational) {
                document.querySelectorAll('input[name="trade_direction"]').forEach(function (radio) {
                    radio.checked = false;
                });
                serviceTypeSelector.style.display = 'none';
                serviceTypeSelect.value = '';
            } else {
                serviceTypeSelector.style.display = '';
            }

            syncFieldsForServiceType();
        }

        function onTradeDirectionChoiceChange() {
            serviceTypeSelector.style.display = '';
            filterServiceTypes();
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
         * one's.
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

            wireCascade('origin-state', 'origin-city', 'origin-district', @json(old('origin_city_id')), @json(old('origin_district_id')));
            wireCascade('destination-state', 'destination-city', 'destination-district', @json(old('destination_city_id')), @json(old('destination_district_id')));
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
                populateCities(@json(old('origin_city_id')) || @json(old('destination_city_id')));
            }
        })();

        // Load Quote ID — looks the quote up, then drives the exact
        // same JS this page shares with Rate Checker (onRouteTypeChange,
        // onTradeDirectionChoiceChange, syncFieldsForServiceType, the
        // state->city->district cascades) to reproduce the same section
        // visibility and field values a person would get by picking
        // everything by hand. Fields stay editable afterwards — this is
        // a starting point, not a lock.
        (function () {
            const lookupInput = document.getElementById('quote-lookup-input');
            const lookupBtn = document.getElementById('load-quote-btn');
            const errorEl = document.getElementById('quote-lookup-error');
            const successEl = document.getElementById('quote-lookup-success');
            const quoteNumberField = document.getElementById('quote-number-field');

            function setSelectAndFireChange(id, value) {
                const el = document.getElementById(id);
                if (!el) return;
                el.value = value ?? '';
                el.dispatchEvent(new Event('change'));
            }

            function setValue(id, value) {
                const el = document.getElementById(id);
                if (el) el.value = value ?? '';
            }

            function loadQuoteIntoForm(context, result) {
                // 1. Billing model — only one is implemented, always this.
                billingModelSelect.value = 'standard_billing';
                syncModelSection();

                // 2. Service type, and the route/trade radios that match it.
                const serviceTypeId = String(context.service_type_id ?? '');
                const matchingOption = serviceTypeOptions.find(function (o) { return o.value === serviceTypeId; });

                if (matchingOption) {
                    const routeType = matchingOption.dataset.routeType;
                    const direction = matchingOption.dataset.tradeDirection;

                    document.querySelectorAll('input[name="route_type"]').forEach(function (r) {
                        r.checked = r.value === routeType;
                    });
                    onRouteTypeChange();

                    if (routeType === 'international') {
                        document.querySelectorAll('input[name="trade_direction"]').forEach(function (r) {
                            r.checked = r.value === direction;
                        });
                        onTradeDirectionChoiceChange();
                    }

                    serviceTypeSelect.value = serviceTypeId;
                    syncFieldsForServiceType();

                    // 3. Location fields, matching the section just revealed.
                    if (routeType === 'domestic') {
                        setSelectAndFireChange('origin-state', context.origin_state_id);
                        setSelectAndFireChange('origin-city', context.origin_city_id);
                        setValue('origin-district', context.origin_district_id);

                        setSelectAndFireChange('destination-state', context.destination_state_id);
                        setSelectAndFireChange('destination-city', context.destination_city_id);
                        setValue('destination-district', context.destination_district_id);
                    } else if (direction === 'cross_trade') {
                        setValue('ctp-origin-country', context.origin_country_id);
                        setValue('ctp-destination-country', context.destination_country_id);
                    } else {
                        const isImport = direction === 'import';
                        const nigeriaStateValue = isImport ? context.destination_state_id : context.origin_state_id;
                        const nigeriaCityValue = isImport ? context.destination_city_id : context.origin_city_id;
                        const foreignCountryValue = isImport ? context.origin_country_id : context.destination_country_id;

                        setSelectAndFireChange('intl-nigeria-state', nigeriaStateValue);
                        setValue('intl-nigeria-city', nigeriaCityValue);
                        setValue('intl-foreign-country', foreignCountryValue);
                    }
                }

                // 4. Weight & dimensions.
                document.querySelector('input[name="weight_kg"]').value = context.weight_kg ?? '';
                setValue('dim-length', context.length_cm);
                setValue('dim-width', context.width_cm);
                setValue('dim-height', context.height_cm);
                document.getElementById('dim-length').dispatchEvent(new Event('input'));

                // 5. Additional services already selected on the quote.
                (context.additional_service_option_ids || []).forEach(function (optId) {
                    document.querySelectorAll('select[name="additional_service_option_ids[]"] option[value="' + optId + '"]').forEach(function (opt) {
                        opt.selected = true;
                    });
                });

                quoteNumberField.value = lookupInput.value.trim().toUpperCase();
            }

            lookupBtn.addEventListener('click', function () {
                const code = lookupInput.value.trim();
                if (!code) return;

                lookupBtn.disabled = true;
                lookupBtn.textContent = 'Loading…';
                errorEl.classList.add('hidden');
                successEl.classList.add('hidden');

                fetch('/quotes/' + encodeURIComponent(code), {
                    headers: { 'Accept': 'application/json' },
                })
                    .then(async function (res) {
                        const body = await res.json();
                        if (!res.ok) throw new Error(body.message || 'Could not load that quote.');
                        return body;
                    })
                    .then(function (body) {
                        loadQuoteIntoForm(body.context, body.result);

                        document.getElementById('loaded-quote-number').textContent = body.quote_number;
                        document.getElementById('loaded-quote-total').textContent = Number(body.result.total_amount).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                        document.getElementById('loaded-quote-expiry').textContent = new Date(body.expires_at).toLocaleString();
                        successEl.classList.remove('hidden');
                    })
                    .catch(function (err) {
                        errorEl.textContent = err.message;
                        errorEl.classList.remove('hidden');
                    })
                    .finally(function () {
                        lookupBtn.disabled = false;
                        lookupBtn.textContent = 'Load quote';
                    });
            });
        })();

        // Cash-on-delivery / insurance amount fields only make sense
        // once their checkbox is on — disabled (not just visually
        // hidden) so a stray leftover value never submits unintended.
        (function () {
            function wireToggle(checkboxId, fieldId) {
                const checkbox = document.getElementById(checkboxId);
                const field = document.getElementById(fieldId);
                checkbox.addEventListener('change', function () {
                    field.disabled = !checkbox.checked;
                    if (!checkbox.checked) field.value = '';
                });
            }

            wireToggle('is-cod', 'cod-amount');
            wireToggle('is-insured', 'declared-value');
        })();
    </script>

</x-layouts.app>
