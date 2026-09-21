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
        @else
            <div class="mb-5 rounded-lg border border-status-exception/30 bg-status-exception/5 p-3 text-sm text-status-exception">
                Your account isn't assigned to a specific hub or outlet — bulk upload needs a single origin to book from.
            </div>
        @endif

        <form method="POST" action="{{ route('shipments.bulk.store-batch') }}" class="space-y-4">
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
    </script>

</x-layouts.app>
