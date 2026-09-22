<x-layouts.app :title="'Bulk Shipment Upload'">

    <div class="mb-5">
        <p class="text-2xl font-semibold text-ink-900">Bulk Shipment Upload — Step 1 of 2</p>
        <p class="mt-1 text-sm text-ink-500">
            Shipper and service details for the whole batch. Once submitted, you'll get a batch
            number and move to uploading the file — you can always come back and re-upload to the
            same batch if the file needs fixing.
        </p>
    </div>

    @if ($errors->any())
        <div class="mb-5 max-w-2xl rounded-xl border border-status-exception/30 bg-status-exception/5 p-4 text-sm text-status-exception">
            <p class="font-medium">Couldn't create this batch</p>
            <ul class="mt-1 list-disc pl-5">
                @foreach ($errors->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="max-w-2xl rounded-xl border border-line bg-surface-0 shadow-sm p-5">
        @if ($originLabel)
            <div class="mb-5 rounded-lg border border-dashed border-line bg-surface-50 p-3 text-sm">
                <span class="text-ink-500">Origin:</span> <span class="font-medium text-ink-900">{{ $originLabel }}</span>
                <span class="text-ink-500"> — this batch books from your own assigned location.</span>
            </div>
        @elseif ($originHubs->isNotEmpty() || $originOutlets->isNotEmpty())
            <div class="mb-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink-900">Origin hub</label>
                    <select name="origin_hub_id" id="origin-hub-select" form="bulk-batch-form" class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
                        <option value="">— None —</option>
                        @foreach ($originHubs as $hub)
                            <option value="{{ $hub->id }}" @selected(old('origin_hub_id') == $hub->id)>{{ $hub->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink-900">Or origin outlet</label>
                    <select name="origin_outlet_id" id="origin-outlet-select" form="bulk-batch-form" class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
                        <option value="">— None —</option>
                        @foreach ($originOutlets as $outlet)
                            <option value="{{ $outlet->id }}" @selected(old('origin_outlet_id') == $outlet->id)>{{ $outlet->name }}</option>
                        @endforeach
                    </select>
                </div>
                <p class="col-span-full text-xs text-ink-500">Pick a hub, or an outlet if this batch is going out from one specifically — not both.</p>
            </div>
        @else
            <div class="mb-5 rounded-lg border border-status-exception/30 bg-status-exception/5 p-3 text-sm text-status-exception">
                Your account isn't assigned to a specific hub or outlet — bulk upload needs a single origin to book from.
            </div>
        @endif

        <form id="bulk-batch-form" method="POST" action="{{ route('shipments.bulk.store-batch') }}" class="space-y-4">
            @csrf

            <div>
                <label class="mb-1 block text-sm font-medium text-ink-900">Client account</label>
                <select name="client_account_id" class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
                    <option value="">Walk-in customer</option>
                    @foreach ($clientAccounts as $account)
                        <option value="{{ $account->id }}" @selected(old('client_account_id') == $account->id)>{{ $account->account_name }} ({{ $account->account_number }})</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-ink-500">Leave as "Walk-in customer" for a cash batch not billed to any registered account.</p>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink-900">Sender name</label>
                    <input type="text" name="sender_name" required value="{{ old('sender_name') }}"
                           class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink-900">Sender phone</label>
                    <input type="text" name="sender_phone" required value="{{ old('sender_phone') }}"
                           class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
                </div>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-ink-900">Sender address</label>
                <input type="text" name="sender_address" required maxlength="150" value="{{ old('sender_address') }}"
                       class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
            </div>

            {{-- The booking hub/outlet isn't necessarily where these
                 shipments are actually being picked up from — anyone
                 can be arranging a remote pickup while booking
                 through their own hub — so origin state/town is its
                 own explicit choice, used as the real origin instead
                 of just assuming the hub's own city. --}}
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink-900">Origin state</label>
                    <select id="origin-state-select" required class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
                        <option value="">— Select —</option>
                        @foreach ($states as $state)
                            <option value="{{ $state->id }}">{{ $state->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink-900">Origin town</label>
                    <select name="origin_city_id" id="origin-city-select" required class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
                        <option value="">— Select a state first —</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-ink-900">Sender email <span class="text-ink-400">(optional)</span></label>
                <input type="email" name="sender_email" value="{{ old('sender_email') }}"
                       class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-ink-900">Billing model</label>
                <select id="billing-model" name="billing_model" required class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
                    <option value="">— Select —</option>
                    @foreach ($billingModels as $key => $label)
                        <option value="{{ $key }}" @selected(old('billing_model') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-ink-500">Fleet Billing isn't offered here — it bills a dedicated contract, not a per-shipment rate.</p>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-ink-900">Service type</label>
                <select id="service-type" name="service_type_id" required class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
                    <option value="">— Select a billing model first —</option>
                    @foreach ($serviceTypes as $serviceType)
                        <option value="{{ $serviceType->id }}" data-billing-model="{{ $serviceType->billing_model }}" @selected(old('service_type_id') == $serviceType->id)>{{ $serviceType->name }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-ink-500">Applies to every shipment in this batch.</p>
            </div>

            <button type="submit" class="w-full rounded-md bg-[var(--brand-primary)] px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:opacity-90">
                Create batch &amp; continue to upload
            </button>
        </form>
    </div>

    <script>
        const billingModelSelect = document.getElementById('billing-model');
        const serviceTypeSelect = document.getElementById('service-type');
        const allServiceOptions = Array.from(serviceTypeSelect.options).filter(o => o.value);

        function syncServiceTypeOptions() {
            const chosen = billingModelSelect.value;
            serviceTypeSelect.innerHTML = '<option value="">— Select —</option>';

            if (!chosen) {
                serviceTypeSelect.innerHTML = '<option value="">— Select a billing model first —</option>';
                return;
            }

            allServiceOptions.filter(o => o.dataset.billingModel === chosen).forEach(function (opt) {
                serviceTypeSelect.appendChild(opt.cloneNode(true));
            });
        }

        billingModelSelect.addEventListener('change', syncServiceTypeOptions);
        if (billingModelSelect.value) syncServiceTypeOptions();

        // Only present for global/regional staff who get an actual
        // origin choice - hub and outlet are mutually exclusive, same
        // pattern used everywhere else this pairing appears.
        const originHubSelect = document.getElementById('origin-hub-select');
        const originOutletSelect = document.getElementById('origin-outlet-select');
        if (originHubSelect && originOutletSelect) {
            originHubSelect.addEventListener('change', function () {
                if (this.value) originOutletSelect.value = '';
            });
            originOutletSelect.addEventListener('change', function () {
                if (this.value) originHubSelect.value = '';
            });
        }

        // Origin state -> town, cascading client-side from the
        // states/cities already loaded for this form — no AJAX round
        // trip needed for a single pair of dropdowns like the bulk
        // template's per-row version needs.
        const citiesByState = @json($states->mapWithKeys(fn ($state) => [$state->id => $state->cities->map(fn ($city) => ['id' => $city->id, 'name' => $city->name])]));
        const originStateSelect = document.getElementById('origin-state-select');
        const originCitySelect = document.getElementById('origin-city-select');

        function syncOriginCityOptions() {
            const stateId = originStateSelect.value;
            originCitySelect.innerHTML = '';

            if (!stateId) {
                originCitySelect.innerHTML = '<option value="">— Select a state first —</option>';
                return;
            }

            originCitySelect.innerHTML = '<option value="">— Select —</option>';
            (citiesByState[stateId] || []).forEach(function (city) {
                const opt = document.createElement('option');
                opt.value = city.id;
                opt.textContent = city.name;
                originCitySelect.appendChild(opt);
            });
        }

        originStateSelect.addEventListener('change', syncOriginCityOptions);
    </script>

</x-layouts.app>
