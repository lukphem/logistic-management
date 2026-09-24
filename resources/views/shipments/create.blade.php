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

        <div class="space-y-4">
            <p class="text-sm font-semibold text-ink-900">Who's this for?</p>
            <div>
                <label class="mb-1 block text-sm font-medium text-ink-900">Account <span class="text-xs font-normal text-ink-500">(search by account number, account name, or client name — determines which billing models/rates apply below)</span></label>
                <input type="text" id="account-number-field" name="account_number" value="{{ old('account_number') }}"
                       list="account-options" autocomplete="off" placeholder="Start typing an account number, account name, or client name…"
                       class="w-full max-w-md rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
                <datalist id="account-options">
                    @foreach ($accountOptions as $accountOption)
                        <option value="{{ $accountOption->account_number }}">{{ $accountOption->account_name }} — {{ $accountOption->client?->name }}</option>
                    @endforeach
                </datalist>
                <p class="mt-1 text-xs text-ink-500">A client can have more than one account (Lagos, Abuja...) — pick the specific one to book against. Leave blank for a walk-in customer.</p>
                <p id="account-number-status" class="mt-1 text-xs"></p>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-ink-900">Or pick a client directly <span class="text-xs font-normal text-ink-500">(uses that client's default account — the account field above takes priority if both are filled in)</span></label>
                <select name="client_user_id" class="w-full max-w-sm rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                    <option value="">Walk-in customer</option>
                    @foreach ($clients as $client)
                        <option value="{{ $client->id }}" @selected(old('client_user_id') == $client->id)>{{ $client->name }} ({{ $client->email }})</option>
                    @endforeach
                </select>
            </div>
        </div>

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

            <div id="fleet-fields" style="{{ $selectedServiceType?->billing_model === 'fleet_billing' ? '' : 'display:none' }}">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-ink-900">Vehicle type <x-required /></label>
                        <select name="vehicle_type_id" class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                            <option value="">Select a vehicle type</option>
                            @foreach ($vehicleTypes as $vehicleType)
                                <option value="{{ $vehicleType->id }}" @selected(old('vehicle_type_id') == $vehicleType->id)>{{ $vehicleType->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-end pb-2">
                        <label class="flex items-center gap-2 text-sm text-ink-900">
                            <input type="checkbox" name="is_empty_return" value="1" @checked(old('is_empty_return')) class="rounded border-line">
                            Empty return / repositioning trip
                            <span class="cursor-help text-ink-400" title="Adds the vehicle's configured empty-return charge on top of the freight — for a trip where the vehicle travels back empty or is repositioned, not carrying a paying load either way.">ⓘ</span>
                        </label>
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

            <div class="flex flex-wrap items-end gap-3">
                <div>
                    <label class="mb-1 block text-xs font-medium text-ink-900">Quantity (pieces) <x-required /></label>
                    <input type="number" step="1" min="1" name="quantity" value="{{ old('quantity', 1) }}" required
                           class="w-24 rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-ink-900">Packaging <span class="text-xs font-normal text-ink-500">(optional)</span></label>
                    <select name="carton_size" class="w-36 rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                        <option value="">Not specified</option>
                        <option value="small" @selected(old('carton_size') === 'small')>Small</option>
                        <option value="medium" @selected(old('carton_size') === 'medium')>Medium</option>
                        <option value="large" @selected(old('carton_size') === 'large')>Large</option>
                    </select>
                </div>
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

            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div class="space-y-3 rounded-lg border border-line p-4">
                    <p class="text-sm font-semibold text-ink-900">Sender</p>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-ink-900">Full name <x-required /></label>
                        <input type="text" name="sender_name" value="{{ old('sender_name') }}" required maxlength="255"
                               pattern="[A-Za-z\s\-'.]+" title="Letters, spaces, hyphens, and apostrophes only"
                               class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-ink-900">Phone <x-required /></label>
                        <input type="tel" name="sender_phone" value="{{ old('sender_phone') }}" required maxlength="20" inputmode="tel"
                               pattern="\+?[0-9\s\-()]{7,20}" title="A valid phone number, 7-20 characters — digits, with an optional leading + and spaces/hyphens/parentheses"
                               class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-ink-900">Email <span class="text-xs font-normal text-ink-500">(optional)</span></label>
                        <input type="email" name="sender_email" value="{{ old('sender_email') }}" maxlength="255"
                               class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-ink-900">Origin address <x-required /></label>
                        <textarea name="origin_address" rows="2" maxlength="2000" required
                                  class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">{{ old('origin_address') }}</textarea>
                    </div>
                </div>
                <div class="space-y-3 rounded-lg border border-line p-4">
                    <p class="text-sm font-semibold text-ink-900">Receiver</p>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-ink-900">Full name <x-required /></label>
                        <input type="text" name="receiver_name" value="{{ old('receiver_name') }}" required maxlength="255"
                               pattern="[A-Za-z\s\-'.]+" title="Letters, spaces, hyphens, and apostrophes only"
                               class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
                    </div>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-ink-900">Phone <x-required /></label>
                            <input type="tel" name="receiver_phone" value="{{ old('receiver_phone') }}" required maxlength="20" inputmode="tel"
                                   pattern="\+?[0-9\s\-()]{7,20}" title="A valid phone number, 7-20 characters — digits, with an optional leading + and spaces/hyphens/parentheses"
                                   class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-ink-900">Alternate phone <span class="text-xs font-normal text-ink-500">(optional)</span></label>
                            <input type="tel" name="receiver_alternate_phone" value="{{ old('receiver_alternate_phone') }}" maxlength="20" inputmode="tel"
                                   pattern="\+?[0-9\s\-()]{7,20}" title="A valid phone number, 7-20 characters — digits, with an optional leading + and spaces/hyphens/parentheses"
                                   class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
                        </div>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-ink-900">Email <span class="text-xs font-normal text-ink-500">(optional)</span></label>
                        <input type="email" name="receiver_email" value="{{ old('receiver_email') }}" maxlength="255"
                               class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-ink-900">Destination address <x-required /></label>
                        <textarea name="destination_address" rows="2" maxlength="2000" required
                                  class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">{{ old('destination_address') }}</textarea>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink-900">Package description <x-required /> <span class="text-xs font-normal text-ink-500">(what's inside — needed for handling/customs)</span></label>
                    <textarea name="package_description" rows="2" maxlength="1000" required
                              class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">{{ old('package_description') }}</textarea>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink-900">Special instructions <span class="text-xs font-normal text-ink-500">(optional)</span></label>
                    <textarea name="special_instructions" rows="2" maxlength="2000"
                              class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">{{ old('special_instructions') }}</textarea>
                </div>
            </div>

            @if ((! $bookingOutlet || $bookingOutlet->can_collect_cash) || $paystackEnabled || (! $bookingOutlet || $bookingOutlet->can_use_wallet))
                <div id="payment-method-section" class="rounded-lg border border-dashed border-line p-4">
                    <p class="mb-3 text-xs font-medium uppercase tracking-wide text-ink-500">
                        Payment method
                        <span id="payment-method-note-required" class="normal-case text-ink-400">— required; no credit facility to defer to</span>
                        <span id="payment-method-note-credit" class="normal-case text-ink-400 hidden">— defaults to deferred (invoiced later), but this account can still pay a specific shipment now instead</span>
                    </p>
                    <div class="flex flex-wrap gap-6">
                        @if (! $bookingOutlet || $bookingOutlet->can_collect_cash)
                            <label class="flex cursor-pointer items-center gap-2 text-sm text-ink-900">
                                <input type="radio" name="payment_method" value="cash" @checked(old('payment_method') === 'cash') class="payment-method-option border-line">
                                Cash — collected now
                            </label>
                        @endif
                        @if ($paystackEnabled)
                            <label class="flex cursor-pointer items-center gap-2 text-sm text-ink-900">
                                <input type="radio" name="payment_method" value="paystack" @checked(old('payment_method') === 'paystack') class="payment-method-option border-line">
                                Paystack — pay after booking
                            </label>
                        @endif
                        @if (! $bookingOutlet || $bookingOutlet->can_use_wallet)
                            <label class="flex cursor-pointer items-center gap-2 text-sm text-ink-900">
                                <input type="radio" name="payment_method" value="wallet" id="payment-method-wallet" @checked(old('payment_method') === 'wallet') class="payment-method-option border-line">
                                Wallet
                            </label>
                        @endif
                        <label id="payment-method-deferred-label" class="hidden cursor-pointer items-center gap-2 text-sm text-ink-900">
                            <input type="radio" name="payment_method" value="deferred" id="payment-method-deferred" @checked(old('payment_method', 'deferred') === 'deferred') class="border-line">
                            Deferred — invoiced later
                        </label>
                    </div>

                    @if (! $bookingOutlet || $bookingOutlet->can_use_wallet)
                        <div id="wallet-source-section" class="mt-3 hidden border-t border-line pt-3">
                            <p class="mb-2 text-xs font-medium uppercase tracking-wide text-ink-500">Pay from</p>
                            <div class="flex flex-wrap gap-6">
                                <label id="wallet-source-client-label" class="hidden cursor-pointer items-center gap-2 text-sm text-ink-900">
                                    <input type="radio" name="wallet_source" value="client" class="border-line">
                                    Client's wallet
                                </label>
                                <label class="flex cursor-pointer items-center gap-2 text-sm text-ink-900">
                                    <input type="radio" name="wallet_source" value="outlet" class="border-line">
                                    Outlet's wallet
                                </label>
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            <div class="rounded-lg border border-dashed border-line p-4">
                <p class="mb-3 text-xs font-medium uppercase tracking-wide text-ink-500">Not on a standard courier waybill — specific to this business</p>
                <div class="flex flex-wrap gap-6">
                    <div id="cod-section" class="hidden">
                        <label class="flex cursor-pointer items-center gap-2 text-sm text-ink-900">
                            <input type="checkbox" id="is-cod" name="is_cod" value="1" @checked(old('is_cod')) class="rounded border-line">
                            Cash on delivery
                        </label>
                        <input type="number" step="0.01" min="0" id="cod-amount" name="cod_amount" value="{{ old('cod_amount') }}" placeholder="Amount to collect"
                               class="mt-2 w-40 rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20" {{ old('is_cod') ? '' : 'disabled' }}>
                        <p class="mt-1 text-xs text-ink-500">Only available for a registered account with Cash on Delivery turned on (Accounts → Account Details → Managerial services).</p>
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
                    <div id="pickup-section">
                        <label class="flex cursor-pointer items-center gap-2 text-sm text-ink-900">
                            <input type="checkbox" id="is-pickup-requested" name="is_pickup_requested" value="1" @checked(old('is_pickup_requested')) class="rounded border-line">
                            <span id="pickup-label">Request pickup</span>
                        </label>
                        <p id="pickup-note" class="mt-1 text-xs text-ink-500">Free unless the selected account has a pickup charge configured.</p>
                    </div>
                </div>
            </div>

            <div class="rounded-lg border border-line bg-surface-50 p-4">
                <div class="flex items-center justify-between gap-3">
                    <p class="text-sm font-medium text-ink-900">Price</p>
                    <button type="button" id="check-price-btn" class="rounded-md border border-[var(--brand-primary)] px-3 py-1.5 text-xs font-semibold text-[var(--brand-primary)] transition hover:bg-[var(--brand-primary)]/5">
                        Check price
                    </button>
                </div>
                <div id="price-preview-placeholder" class="mt-2 text-xs text-ink-500">
                    Fill in the route and weight above, then check the price before creating this shipment.
                </div>
                <div id="price-preview-result" class="mt-2 hidden space-y-1 text-sm text-ink-900">
                    <div class="flex justify-between"><span class="text-ink-500">Base freight</span><span id="pv-base"></span></div>
                    <div id="pv-surcharge-row" class="hidden justify-between"><span class="text-ink-500">Surcharge</span><span id="pv-surcharge"></span></div>
                    <div id="pv-onforwarding-row" class="hidden justify-between"><span class="text-ink-500">Onforwarding</span><span id="pv-onforwarding"></span></div>
                    <div id="pv-additional-row" class="hidden justify-between"><span class="text-ink-500">Additional services</span><span id="pv-additional"></span></div>
                    <div id="pv-discount-row" class="hidden justify-between text-status-delivered"><span>Discount</span><span id="pv-discount"></span></div>
                    <div id="pv-insurance-row" class="hidden justify-between"><span class="text-ink-500">Insurance</span><span id="pv-insurance"></span></div>
                    <div class="flex justify-between"><span class="text-ink-500">VAT</span><span id="pv-vat"></span></div>
                    <div class="flex justify-between border-t border-line pt-1 font-semibold"><span>Total</span><span id="pv-total"></span></div>
                </div>
                <p id="price-preview-error" class="mt-2 hidden text-xs text-status-exception"></p>
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
        const implementedModels = ['standard_billing', 'origin_destination_billing', 'fleet_billing'];

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

            // Vehicle type / empty-return are independent of domestic
            // vs international — shown by billing_model instead, since
            // Fleet Billing can route through either.
            const fleetFields = document.getElementById('fleet-fields');
            const isFleet = selected?.dataset.billingModel === 'fleet_billing';
            fleetFields.style.display = isFleet ? '' : 'none';
            fleetFields.querySelectorAll('input, select').forEach(function (el) {
                el.disabled = !isFleet;
            });

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

        // Filters Billing model / Service type to only what this
        // specific account is actually set up to use, as soon as an
        // account number resolves — never lets something get selected
        // that would fail (or price wrong) at booking time. Options
        // outside the account's list are hidden entirely (not just
        // disabled), matching how Rate Checker's server-side version
        // of this same filtering behaves.
        (function () {
            const field = document.getElementById('account-number-field');
            const status = document.getElementById('account-number-status');
            const billingModelSelect = document.getElementById('billing-model');
            const serviceTypeSelect = document.getElementById('service-type');
            if (!field) return;

            function resetOptions() {
                billingModelSelect.querySelectorAll('option').forEach(o => o.style.display = '');
                serviceTypeSelect.querySelectorAll('option').forEach(o => o.style.display = '');
                resetCodAndPickup();
                applyPaymentMethodVisibility(false, false);
            }

            // A credit account is invoiced later, not paid at booking
            // — the payment-method section (and whatever was picked
            // in it) only makes sense for a walk-in or a genuinely
            // non-credit account actually paying now.
            function applyPaymentMethodVisibility(isCreditAccount, hasAccount) {
                const section = document.getElementById('payment-method-section');
                if (!section) return;
                const deferredLabel = document.getElementById('payment-method-deferred-label');
                const deferredInput = document.getElementById('payment-method-deferred');
                const requiredNote = document.getElementById('payment-method-note-required');
                const creditNote = document.getElementById('payment-method-note-credit');
                const options = section.querySelectorAll('.payment-method-option');
                const clientWalletLabel = document.getElementById('wallet-source-client-label');

                if (isCreditAccount) {
                    // Defaults to deferred (invoiced later), but a
                    // credit client can still choose to pay this one
                    // shipment now instead — all three options stay
                    // available, nothing forced.
                    if (deferredLabel) deferredLabel.classList.remove('hidden');
                    if (deferredInput && !section.querySelector('input[name="payment_method"]:checked')) deferredInput.checked = true;
                    options.forEach(el => el.required = false);
                    requiredNote?.classList.add('hidden');
                    creditNote?.classList.remove('hidden');
                } else {
                    // No credit facility to fall back on — a real
                    // choice between the two actual payment methods
                    // is required, deferred isn't offered at all.
                    if (deferredLabel) deferredLabel.classList.add('hidden');
                    if (deferredInput) deferredInput.checked = false;
                    options.forEach(el => el.required = true);
                    requiredNote?.classList.remove('hidden');
                    creditNote?.classList.add('hidden');
                }

                // The client's own wallet only exists to draw from
                // once there's an actual client account resolved — a
                // walk-in has no account, so only the outlet's own
                // wallet is a real option for them.
                if (clientWalletLabel) {
                    if (hasAccount) {
                        clientWalletLabel.classList.remove('hidden');
                    } else {
                        clientWalletLabel.classList.add('hidden');
                        const clientWalletInput = clientWalletLabel.querySelector('input');
                        if (clientWalletInput?.checked) clientWalletInput.checked = false;
                    }
                }

                syncWalletSourceVisibility();
            }

            // The "pay from" sub-choice only makes sense once Wallet
            // itself is actually selected as the payment method.
            function syncWalletSourceVisibility() {
                const walletSourceSection = document.getElementById('wallet-source-section');
                if (!walletSourceSection) return;
                const walletSelected = document.getElementById('payment-method-wallet')?.checked;
                walletSourceSection.classList.toggle('hidden', !walletSelected);
                walletSourceSection.querySelectorAll('input[name="wallet_source"]').forEach(el => el.required = !!walletSelected);
                if (!walletSelected) {
                    walletSourceSection.querySelectorAll('input[name="wallet_source"]').forEach(el => el.checked = false);
                }
            }

            document.getElementById('payment-method-section')?.addEventListener('change', function (e) {
                if (e.target.name === 'payment_method') syncWalletSourceVisibility();
            });

            function resetCodAndPickup() {
                const codSection = document.getElementById('cod-section');
                const codCheckbox = document.getElementById('is-cod');
                const codAmount = document.getElementById('cod-amount');
                codSection.classList.add('hidden');
                codCheckbox.checked = false;
                codAmount.value = '';
                codAmount.disabled = true;
                document.getElementById('pickup-label').textContent = 'Request pickup';
                document.getElementById('pickup-note').textContent = 'Free unless the selected account has a pickup charge configured.';
            }

            function applyCodAndPickup(data) {
                const codSection = document.getElementById('cod-section');
                if (data.cod_enabled) {
                    codSection.classList.remove('hidden');
                } else {
                    codSection.classList.add('hidden');
                    document.getElementById('is-cod').checked = false;
                    document.getElementById('cod-amount').value = '';
                    document.getElementById('cod-amount').disabled = true;
                }
                const pickupLabel = document.getElementById('pickup-label');
                const pickupNote = document.getElementById('pickup-note');
                if (data.is_pickup_chargeable && data.pickup_charge) {
                    pickupLabel.textContent = 'Request pickup (fee: ' + Number(data.pickup_charge).toLocaleString() + ')';
                    pickupNote.textContent = 'This account is charged for pickup — the fee above is added to the total if selected.';
                } else {
                    pickupLabel.textContent = 'Request pickup';
                    pickupNote.textContent = 'No pickup charge configured for this account — free if selected.';
                }
            }

            function applyFilter(data) {
                billingModelSelect.querySelectorAll('option').forEach(function (opt) {
                    if (!opt.value) return;
                    opt.style.display = data.billing_models.includes(opt.value) ? '' : 'none';
                });
                serviceTypeSelect.querySelectorAll('option').forEach(function (opt) {
                    if (!opt.value) return;
                    opt.style.display = data.service_type_ids.includes(parseInt(opt.value, 10)) ? '' : 'none';
                });
                // A previously selected option that's now hidden would
                // stay silently selected — clear it so the field
                // visibly needs a fresh, valid pick instead.
                if (billingModelSelect.selectedOptions[0]?.style.display === 'none') billingModelSelect.value = '';
                if (serviceTypeSelect.selectedOptions[0]?.style.display === 'none') { serviceTypeSelect.value = ''; syncFieldsForServiceType(); }
                applyCodAndPickup(data);
                applyPaymentMethodVisibility(data.is_credit_account, true);
            }

            function lookupAccount() {
                const value = field.value.trim();
                if (!value) {
                    resetOptions();
                    status.textContent = '';
                    return;
                }
                status.textContent = 'Looking up account…';
                status.className = 'mt-1 text-xs text-ink-500';
                fetch(@json(route('shipments.account-billing-options')) + '?account_number=' + encodeURIComponent(value))
                    .then(r => r.json())
                    .then(function (data) {
                        if (!data.found) {
                            resetOptions();
                            status.textContent = 'No account found with that number.';
                            status.className = 'mt-1 text-xs text-status-exception';
                            return;
                        }
                        applyFilter(data);
                        status.textContent = '✓ ' + data.client_name + ' — ' + data.account_name + ' (options below filtered to what this account can use)';
                        status.className = 'mt-1 text-xs text-status-delivered';
                    })
                    .catch(function () {
                        status.textContent = 'Could not look up this account — try again.';
                        status.className = 'mt-1 text-xs text-status-exception';
                    });
            }

            field.addEventListener('blur', lookupAccount);

            // A validation failure on some OTHER field reloads this page
            // with the account number preserved (old('account_number'))
            // but no blur event ever fires — without this, COD/pickup/
            // billing-model filtering would silently revert to showing
            // everything, even though the account is still the same one
            // just picked.
            // Establishes the default (walk-in, two options required)
            // immediately on load, regardless of whether an account
            // number is already filled in — lookupAccount() below
            // only overrides this when one actually is, so a fresh
            // page load with nothing typed yet still gets the correct
            // required state rather than none at all.
            applyPaymentMethodVisibility(false, false);

            if (field.value.trim()) {
                lookupAccount();
            }
        })();

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

            function loadQuoteIntoForm(context, result, accountNumber) {
                // 0. Account — determines which billing models/rates
                // even show as options below, so this has to run
                // before anything else, and needs the SAME lookup the
                // account field's own blur handler runs, so billing
                // model/service type get filtered to match.
                if (accountNumber) {
                    const accountField = document.getElementById('account-number-field');
                    accountField.value = accountNumber;
                    accountField.dispatchEvent(new Event('blur'));
                }

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

                // 6. Fleet Billing fields, if this quote used that model.
                if (context.vehicle_type_id) {
                    document.querySelector('select[name="vehicle_type_id"]').value = String(context.vehicle_type_id);
                }
                document.querySelector('input[name="is_empty_return"]').checked = !!context.is_empty_return;

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
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                })
                    .then(async function (res) {
                        const body = await res.json();
                        if (!res.ok) throw new Error(body.message || 'Could not load that quote.');
                        return body;
                    })
                    .then(function (body) {
                        loadQuoteIntoForm(body.context, body.result, body.account_number);

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

        // Check Price — posts the current form state to a preview
        // endpoint that runs the exact same pricing pipeline as an
        // actual booking but persists nothing (no Quote, no Shipment).
        // If a quote_number is already loaded, the preview reflects
        // that quote's frozen price (plus fresh insurance, same as
        // store() would) instead of recalculating.
        (function () {
            const btn = document.getElementById('check-price-btn');
            const placeholder = document.getElementById('price-preview-placeholder');
            const resultBox = document.getElementById('price-preview-result');
            const errorBox = document.getElementById('price-preview-error');
            const form = document.getElementById('create-shipment-form');

            function money(value) {
                return Number(value || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }

            function showRow(rowId, valueId, amount) {
                const row = document.getElementById(rowId);
                if (amount && Number(amount) !== 0) {
                    row.classList.remove('hidden');
                    row.classList.add('flex');
                    document.getElementById(valueId).textContent = money(amount);
                } else {
                    row.classList.add('hidden');
                    row.classList.remove('flex');
                }
            }

            btn.addEventListener('click', function () {
                btn.disabled = true;
                btn.textContent = 'Checking…';
                errorBox.classList.add('hidden');

                fetch('{{ route('shipments.preview-price') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: new FormData(form),
                })
                    .then(async function (res) {
                        const body = await res.json();
                        if (!res.ok) throw new Error(body.message || 'Could not price this shipment yet.');
                        return body;
                    })
                    .then(function (body) {
                        const r = body.result;
                        document.getElementById('pv-base').textContent = money(r.base_amount);
                        showRow('pv-surcharge-row', 'pv-surcharge', r.surcharge_amount);
                        showRow('pv-onforwarding-row', 'pv-onforwarding', r.onforwarding_amount);
                        showRow('pv-additional-row', 'pv-additional', r.additional_services_amount);
                        showRow('pv-discount-row', 'pv-discount', r.discount_amount);
                        showRow('pv-insurance-row', 'pv-insurance', r.insurance_amount);
                        document.getElementById('pv-vat').textContent = money(r.vat_amount);
                        document.getElementById('pv-total').textContent = money(r.total_amount);
                        placeholder.classList.add('hidden');
                        resultBox.classList.remove('hidden');
                    })
                    .catch(function (err) {
                        errorBox.textContent = err.message;
                        errorBox.classList.remove('hidden');
                        placeholder.classList.add('hidden');
                        resultBox.classList.add('hidden');
                    })
                    .finally(function () {
                        btn.disabled = false;
                        btn.textContent = 'Check price';
                    });
            });
        })();
    </script>

</x-layouts.app>
