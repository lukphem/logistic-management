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
          class="max-w-2xl space-y-6 rounded-xl border border-line bg-surface-0 shadow-sm p-5">
        @csrf
        @if ($user->exists) @method('PUT') @endif

        <div>
            <p class="mb-3 text-sm font-semibold text-ink-900">Account</p>
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
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink-900">
                        Password @if (! $user->exists) <x-required /> @endif
                        @if ($user->exists) <span class="text-xs font-normal text-ink-500">(leave blank to keep current)</span> @endif
                    </label>
                    <input type="password" name="password" {{ $user->exists ? '' : 'required' }}
                           class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
                </div>
            </div>
        </div>

        @if ($user->exists && $profile->isOrganization())
            {{-- Already an organization — account_type is fixed, no toggle shown. --}}
            <input type="hidden" name="account_type" value="organization">
        @else
            <div>
                <p class="mb-2 text-sm font-semibold text-ink-900">Account type</p>
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
                    <p class="mt-1 text-xs text-ink-500">Individual clients can be upgraded to an organization later from their billing page — this only sets the type at creation.</p>
                @endif
            </div>
        @endif

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

        <div id="organization-fields" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
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
        </div>

        <div>
            <p class="mb-3 text-sm font-semibold text-ink-900">Address</p>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink-900">Address <span class="text-xs font-normal text-ink-500">(optional)</span></label>
                    <textarea name="address" rows="2"
                              class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">{{ old('address', $profile->address) }}</textarea>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink-900">City <span class="text-xs font-normal text-ink-500">(optional)</span></label>
                    <select name="city_id" class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                        <option value="">Select a city</option>
                        @foreach ($cities as $city)
                            <option value="{{ $city->id }}" @selected(old('city_id', $profile->city_id) == $city->id)>{{ $city->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-sm font-medium text-ink-900">Billing address <span class="text-xs font-normal text-ink-500">(optional — leave blank if same as address above)</span></label>
                    <textarea name="billing_address" rows="2"
                              class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">{{ old('billing_address', $profile->billing_address) }}</textarea>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink-900">Country <span class="text-xs font-normal text-ink-500">(optional)</span></label>
                    <select name="country_id" class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                        <option value="">Select a country</option>
                        @foreach ($countries as $country)
                            <option value="{{ $country->id }}" @selected(old('country_id', $profile->country_id) == $country->id)>{{ $country->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink-900">State <span class="text-xs font-normal text-ink-500">(optional)</span></label>
                    <select name="state_id" class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                        <option value="">Select a state</option>
                        @foreach ($states as $state)
                            <option value="{{ $state->id }}" @selected(old('state_id', $profile->state_id) == $state->id)>{{ $state->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink-900">Territory <span class="text-xs font-normal text-ink-500">(optional)</span></label>
                    <select name="territory_id" class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                        <option value="">Select a territory</option>
                        @foreach ($territories as $territory)
                            <option value="{{ $territory->id }}" @selected(old('territory_id', $profile->territory_id) == $territory->id)>{{ $territory->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink-900">Express center <span class="text-xs font-normal text-ink-500">(optional)</span></label>
                    <input type="text" name="express_center" value="{{ old('express_center', $profile->express_center) }}"
                           class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-sm font-medium text-ink-900">Industry <span class="text-xs font-normal text-ink-500">(optional)</span></label>
                    <input type="text" name="industry" value="{{ old('industry', $profile->industry) }}"
                           class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-sm font-medium text-ink-900">Business objective <span class="text-xs font-normal text-ink-500">(optional)</span></label>
                    <textarea name="business_objective" rows="2"
                              class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">{{ old('business_objective', $profile->business_objective) }}</textarea>
                </div>
            </div>
        </div>

        <div>
            <p class="mb-3 text-sm font-semibold text-ink-900">Staff assignment</p>
            <div>
                <label class="mb-1 block text-sm font-medium text-ink-900">Business manager <span class="text-xs font-normal text-ink-500">(optional — the staff member who owns this client relationship)</span></label>
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
            const isFixedOrganization = !individualRadio; // already an organization - no toggle rendered

            function sync() {
                const isOrg = isFixedOrganization || (organizationRadio && organizationRadio.checked);
                individualFields.style.display = isOrg ? 'none' : '';
                organizationFields.style.display = isOrg ? '' : 'none';
            }

            if (individualRadio) individualRadio.addEventListener('change', sync);
            if (organizationRadio) organizationRadio.addEventListener('change', sync);
            sync();
        })();
    </script>

</x-layouts.app>
