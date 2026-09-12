<x-layouts.app :title="$user->exists ? 'Edit Client' : 'Add Client'">

    @if ($errors->any())
        <div class="mb-5 max-w-2xl rounded-xl border border-status-exception/30 bg-status-exception/5 p-4 text-sm text-status-exception">
            <p class="font-medium">Couldn't save this client</p>
            <ul class="mt-1 list-disc pl-5">
                @foreach ($errors->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ $user->exists ? route('clients.update', $user) : route('clients.store') }}"
          enctype="multipart/form-data" class="max-w-2xl space-y-6">
        @csrf
        @if ($user->exists) @method('PUT') @endif

        {{-- ============ 1. ACCOUNT CREDENTIALS — who logs in ============ --}}
        <div class="rounded-xl border border-line bg-surface-0 shadow-sm p-5">
            <p class="text-sm font-semibold text-ink-900">Account credentials</p>
            <p class="mt-0.5 mb-4 text-xs text-ink-500">Used to log in and receive notifications.</p>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink-900">Full name <x-required /></label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                           class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink-900">Email <x-required /></label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                           class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink-900">Phone <x-required /></label>
                    <input type="text" name="phone_number" value="{{ old('phone_number', $user->phone_number) }}" required
                           class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink-900">Alternate phone <span class="text-xs font-normal text-ink-500">(optional)</span></label>
                    <input type="text" name="alternate_phone" value="{{ old('alternate_phone', $profile->alternate_phone) }}"
                           class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-sm font-medium text-ink-900">
                        Password @if (! $user->exists) <x-required /> @endif
                        @if ($user->exists) <span class="text-xs font-normal text-ink-500">(leave blank to keep current)</span> @endif
                    </label>
                    <input type="password" name="password" {{ $user->exists ? '' : 'required' }}
                           class="w-full max-w-sm rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
                </div>
            </div>
        </div>

        {{-- ============ 2. ACCOUNT TYPE — determines everything below ============ --}}
        <div class="rounded-xl border border-line bg-surface-0 shadow-sm p-5">
            <p class="text-sm font-semibold text-ink-900">Account type</p>
            <p class="mt-0.5 mb-3 text-xs text-ink-500">Determines which details are needed below.</p>
            @if ($user->exists && $profile->isOrganization())
                {{-- Already an organization — account_type is fixed, no toggle shown. --}}
                <input type="hidden" name="account_type" value="organization">
                <span class="inline-flex items-center rounded-full bg-[var(--brand-primary)]/10 px-3 py-1 text-sm font-medium text-[var(--brand-primary)]">Organization</span>
            @else
                <div class="flex gap-4">
                    <label class="flex cursor-pointer items-center gap-2 text-sm text-ink-900">
                        <input type="radio" name="account_type" value="individual" id="type-individual"
                               @checked(old('account_type', 'individual') === 'individual') class="border-line">
                        Individual
                    </label>
                    <label class="flex cursor-pointer items-center gap-2 text-sm text-ink-900">
                        <input type="radio" name="account_type" value="organization" id="type-organization"
                               @checked(old('account_type') === 'organization') class="border-line">
                        Organization
                    </label>
                </div>
                @if ($user->exists)
                    <p class="mt-2 text-xs text-ink-500">Individual clients can be upgraded to an organization later from their billing page — this only sets the type at creation.</p>
                @endif
            @endif
        </div>

        {{-- ============ 3. IDENTITY DETAILS — whichever set applies ============ --}}
        <div id="identity-card" class="rounded-xl border border-line bg-surface-0 shadow-sm p-5">
            <p class="text-sm font-semibold text-ink-900" id="identity-heading">Identity details</p>
            <p class="mt-0.5 mb-4 text-xs text-ink-500" id="identity-subheading"></p>

            <div id="individual-fields" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink-900">ID type <x-required /></label>
                    <select name="id_type" class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                        <option value="">Select ID type</option>
                        @foreach (\App\Models\ClientProfile::ID_TYPES as $key => $label)
                            <option value="{{ $key }}" @selected(old('id_type', $profile->id_type) === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink-900">ID number <x-required /></label>
                    <input type="text" name="id_number" value="{{ old('id_number', $profile->id_number) }}"
                           class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
                </div>
            </div>

            <div id="organization-fields" class="space-y-4">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-ink-900">Company name <x-required /></label>
                        <input type="text" name="company_name" value="{{ old('company_name', $profile->company_name) }}"
                               {{ $user->exists && $profile->isOrganization() ? 'readonly' : '' }}
                               class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20 {{ $user->exists && $profile->isOrganization() ? 'bg-surface-50' : '' }}">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-ink-900">RC / registration number <x-required /></label>
                        <input type="text" name="rc_number" value="{{ old('rc_number', $profile->rc_number) }}"
                               {{ $user->exists && $profile->isOrganization() ? 'readonly' : '' }}
                               class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20 {{ $user->exists && $profile->isOrganization() ? 'bg-surface-50' : '' }}">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-ink-900">TIN <span class="text-xs font-normal text-ink-500">(optional)</span></label>
                        <input type="text" name="tin" value="{{ old('tin', $profile->tin) }}"
                               class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-ink-900">Contact person <x-required /></label>
                        <input type="text" name="contact_person_name" value="{{ old('contact_person_name', $profile->contact_person_name) }}"
                               class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-ink-900">Contact person's role <span class="text-xs font-normal text-ink-500">(optional)</span></label>
                        <input type="text" name="contact_person_role" value="{{ old('contact_person_role', $profile->contact_person_role) }}"
                               class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-ink-900">Industry <span class="text-xs font-normal text-ink-500">(optional)</span></label>
                        <input type="text" name="industry" value="{{ old('industry', $profile->industry) }}"
                               class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
                    </div>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink-900">Business objective <span class="text-xs font-normal text-ink-500">(optional)</span></label>
                    <textarea name="business_objective" rows="2"
                              class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">{{ old('business_objective', $profile->business_objective) }}</textarea>
                </div>

                {{-- ============ LOGO — organization only ============ --}}
                <div class="border-t border-line pt-4">
                    <label class="mb-1 block text-sm font-medium text-ink-900">Company logo <span class="text-xs font-normal text-ink-500">(optional)</span></label>
                    <div class="flex items-center gap-4">
                        @if ($profile->logo_url ?? null)
                            <img src="{{ $profile->logo_url }}" alt="Current logo" class="h-14 w-14 rounded-lg border border-line object-contain bg-surface-50">
                        @endif
                        <input type="file" name="logo" accept="image/*"
                               class="w-full max-w-sm rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
                    </div>
                    <p class="mt-1 text-xs text-ink-500">Shown on this client's Overview tab. PNG or JPG, up to 2MB.</p>
                </div>
            </div>
        </div>

        {{-- ============ 4. LOCATION & OUTLET — cascading, relational ============ --}}
        <div class="rounded-xl border border-line bg-surface-0 shadow-sm p-5">
            <p class="text-sm font-semibold text-ink-900">Location & outlet</p>
            <p class="mt-0.5 mb-4 text-xs text-ink-500">Country narrows State, State narrows City and sets Territory automatically, City narrows which Outlet serves them.</p>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink-900">Address <span class="text-xs font-normal text-ink-500">(optional)</span></label>
                    <textarea name="address" rows="2"
                              class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">{{ old('address', $profile->address) }}</textarea>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink-900">Billing address <span class="text-xs font-normal text-ink-500">(optional — leave blank if same as address)</span></label>
                    <textarea name="billing_address" rows="2"
                              class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">{{ old('billing_address', $profile->billing_address) }}</textarea>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-ink-900">Country <span class="text-xs font-normal text-ink-500">(optional)</span></label>
                    <select id="loc-country" name="country_id" class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                        <option value="">Select a country</option>
                        @foreach ($countries as $country)
                            <option value="{{ $country->id }}" @selected(old('country_id', $profile->country_id) == $country->id)>{{ $country->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink-900">State <span class="text-xs font-normal text-ink-500">(optional)</span></label>
                    <select id="loc-state" name="state_id" class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                        <option value="">Select a state</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink-900">City <span class="text-xs font-normal text-ink-500">(optional — pick a suggestion or type your own)</span></label>
                    <input type="text" id="loc-city" name="city_name" list="loc-city-list" autocomplete="off"
                           value="{{ old('city_name', $profile->cityDisplayName() ?? '') }}"
                           class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
                    <datalist id="loc-city-list"></datalist>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink-900">Territory</label>
                    <input type="text" id="loc-territory-display" readonly value="{{ old('state_id', $profile->state_id) ? $profile->territory?->name : '' }}"
                           placeholder="Set automatically from State"
                           class="w-full rounded-md border border-line bg-surface-50 px-3 py-2 text-sm text-ink-500 outline-none">
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-sm font-medium text-ink-900">Outlet <span class="text-xs font-normal text-ink-500">(optional — which outlet serves this client)</span></label>
                    <select id="loc-outlet" name="outlet_id" class="w-full max-w-sm rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                        <option value="">Select a city first</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- ============ 5. STAFF ASSIGNMENT — internal, decided last ============ --}}
        <div class="rounded-xl border border-line bg-surface-0 shadow-sm p-5">
            <p class="text-sm font-semibold text-ink-900">Staff assignment</p>
            <p class="mt-0.5 mb-3 text-xs text-ink-500">Who owns this client relationship day to day.</p>
            <div>
                <label class="mb-1 block text-sm font-medium text-ink-900">Business manager <span class="text-xs font-normal text-ink-500">(optional)</span></label>
                <select name="business_manager_id" class="w-full max-w-sm rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                    <option value="">Unassigned</option>
                    @foreach ($staffUsers as $staff)
                        <option value="{{ $staff->id }}" @selected(old('business_manager_id', $profile->business_manager_id) == $staff->id)>{{ $staff->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="flex justify-end pt-2">
            <button type="submit" class="rounded-md bg-[var(--brand-primary)] px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 hover:shadow-md">
                {{ $user->exists ? 'Save changes' : 'Create client' }}
            </button>
        </div>
    </form>

    <script>
        (function () {
            const individualRadio = document.getElementById('type-individual');
            const organizationRadio = document.getElementById('type-organization');
            const individualFields = document.getElementById('individual-fields');
            const organizationFields = document.getElementById('organization-fields');
            const identityHeading = document.getElementById('identity-heading');
            const identitySubheading = document.getElementById('identity-subheading');
            const isFixedOrganization = !individualRadio; // already an organization - no toggle rendered

            function sync() {
                const isOrg = isFixedOrganization || (organizationRadio && organizationRadio.checked);
                individualFields.style.display = isOrg ? 'none' : '';
                organizationFields.style.display = isOrg ? '' : 'none';
                identityHeading.textContent = isOrg ? 'Company details' : 'Identity details (KYC)';
                identitySubheading.textContent = isOrg
                    ? 'Registration and contact information for the business.'
                    : "Required to verify who this client is.";
            }

            if (individualRadio) individualRadio.addEventListener('change', sync);
            if (organizationRadio) organizationRadio.addEventListener('change', sync);
            sync();
        })();

        // Country -> State -> City -> Outlet, plus Territory auto-set
        // from State — all pre-loaded once, filtered client-side (same
        // technique already used on Rate Checker/Create Shipment, not
        // a new pattern).
        (function () {
            const statesByCountry = @json($states->groupBy('country_id')->map->map(fn ($s) => ['id' => $s->id, 'name' => $s->name]));
            const territoryByState = @json($states->mapWithKeys(fn ($s) => [$s->id => $s->territory?->name]));
            const citiesByState = @json($cities->groupBy('state_id')->map->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'operational_hub_id' => $c->operational_hub_id]));
            const outletsByHub = @json($outlets->groupBy('hub_id')->map->map(fn ($o) => ['id' => $o->id, 'name' => $o->name]));

            const countrySelect = document.getElementById('loc-country');
            const stateSelect = document.getElementById('loc-state');
            const cityInput = document.getElementById('loc-city');
            const cityList = document.getElementById('loc-city-list');
            const territoryDisplay = document.getElementById('loc-territory-display');
            const outletSelect = document.getElementById('loc-outlet');

            const initialStateId = @json(old('state_id', $profile->state_id));
            const initialCityText = @json(old('city_name', $profile->cityDisplayName()));
            const initialOutletId = @json(old('outlet_id', $profile->outlet_id));

            function populateStates(selectStateId) {
                stateSelect.innerHTML = '<option value="">Select a state</option>';
                (statesByCountry[countrySelect.value] || []).forEach(function (state) {
                    const opt = document.createElement('option');
                    opt.value = state.id;
                    opt.textContent = state.name;
                    if (selectStateId && String(state.id) === String(selectStateId)) opt.selected = true;
                    stateSelect.appendChild(opt);
                });
            }

            function populateCityListAndOutlets() {
                cityList.innerHTML = '';
                outletSelect.innerHTML = '<option value="">Select a city first</option>';
                territoryDisplay.value = territoryByState[stateSelect.value] || '';

                (citiesByState[stateSelect.value] || []).forEach(function (city) {
                    const opt = document.createElement('option');
                    opt.value = city.name;
                    cityList.appendChild(opt);
                });

                populateOutletsForTypedCity();
            }

            function populateOutletsForTypedCity(selectOutletId) {
                const typed = cityInput.value.trim().toLowerCase();
                const matchedCity = (citiesByState[stateSelect.value] || []).find(function (c) {
                    return c.name.toLowerCase() === typed;
                });

                outletSelect.innerHTML = '';
                if (!matchedCity || !matchedCity.operational_hub_id) {
                    outletSelect.innerHTML = '<option value="">' + (matchedCity ? 'No outlet set up for this city yet' : 'Select a known city first') + '</option>';
                    return;
                }

                const opts = outletsByHub[matchedCity.operational_hub_id] || [];
                outletSelect.innerHTML = '<option value="">Select an outlet</option>';
                opts.forEach(function (outlet) {
                    const opt = document.createElement('option');
                    opt.value = outlet.id;
                    opt.textContent = outlet.name;
                    if (selectOutletId && String(outlet.id) === String(selectOutletId)) opt.selected = true;
                    outletSelect.appendChild(opt);
                });
                if (opts.length === 0) {
                    outletSelect.innerHTML = '<option value="">No outlets set up at this hub yet</option>';
                }
            }

            countrySelect.addEventListener('change', function () {
                populateStates();
                populateCityListAndOutlets();
            });
            stateSelect.addEventListener('change', function () {
                populateCityListAndOutlets();
            });
            cityInput.addEventListener('input', function () {
                populateOutletsForTypedCity();
            });

            // Restore on page reload (validation failure) or edit.
            if (countrySelect.value) {
                populateStates(initialStateId);
                if (stateSelect.value || initialStateId) {
                    if (!stateSelect.value && initialStateId) stateSelect.value = initialStateId;
                    populateCityListAndOutlets();
                    cityInput.value = initialCityText || cityInput.value;
                    populateOutletsForTypedCity(initialOutletId);
                }
            }
        })();
    </script>

</x-layouts.app>
