<x-layouts.app :title="'Bulk Shipment Upload'">

    <div class="mb-5">
        <p class="text-2xl font-semibold text-ink-900">Bulk Shipment Upload</p>
        <p class="mt-1 text-sm text-ink-500">
            Client account, origin, and service type apply to the whole batch below.
            Everything else — receiver, destination, weight, description — comes from
            the file, one row per shipment. Up to {{ number_format($rowLimit) }} rows per upload.
        </p>
    </div>

    @if ($errors->any())
        <div class="mb-5 max-w-2xl rounded-xl border border-status-exception/30 bg-status-exception/5 p-4 text-sm text-status-exception">
            <p class="font-medium">Couldn't process this upload</p>
            <ul class="mt-1 list-disc pl-5">
                @foreach ($errors->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="max-w-2xl rounded-xl border border-line bg-surface-0 shadow-sm p-5">
        <div class="mb-5 flex items-center justify-between rounded-lg border border-dashed border-line bg-surface-50 p-4">
            <div>
                <p class="text-sm font-medium text-ink-900">Need the template?</p>
                <p class="text-xs text-ink-500">Includes dropdowns for destination state and city, built from what's currently in the system.</p>
            </div>
            <a href="{{ route('shipments.bulk.template') }}" class="rounded-md border border-[var(--brand-primary)] px-4 py-2 text-sm font-semibold text-[var(--brand-primary)] transition hover:bg-[var(--brand-primary)]/5 whitespace-nowrap">
                Download template
            </a>
        </div>

        <form method="POST" action="{{ route('shipments.bulk.preview') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf

            <div>
                <label class="mb-1 block text-sm font-medium text-ink-900">Client account</label>
                <select name="client_account_id" required class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
                    <option value="">— Select —</option>
                    @foreach ($clientAccounts as $account)
                        <option value="{{ $account->id }}" @selected(old('client_account_id') == $account->id)>{{ $account->account_name }} ({{ $account->account_number }})</option>
                    @endforeach
                </select>
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
            <p class="text-xs text-ink-500">Applies to every shipment in this batch, same as the client account.</p>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink-900">Origin hub</label>
                    <select name="origin_hub_id" id="origin-hub-select" class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
                        <option value="">— None —</option>
                        @foreach ($hubs as $hub)
                            <option value="{{ $hub->id }}" @selected(old('origin_hub_id') == $hub->id)>{{ $hub->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink-900">Or origin outlet</label>
                    <select name="origin_outlet_id" id="origin-outlet-select" class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
                        <option value="">— None —</option>
                        @foreach ($outlets as $outlet)
                            <option value="{{ $outlet->id }}" data-hub="{{ $outlet->hub_id }}" @selected(old('origin_outlet_id') == $outlet->id)>{{ $outlet->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <p class="text-xs text-ink-500">Pick a hub, or an outlet if this batch is going out from one specifically — not both.</p>

            <div>
                <label class="mb-1 block text-sm font-medium text-ink-900">Service type</label>
                <select name="service_type_id" required class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
                    <option value="">— Select —</option>
                    @foreach ($serviceTypes as $serviceType)
                        <option value="{{ $serviceType->id }}" @selected(old('service_type_id') == $serviceType->id)>{{ $serviceType->name }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-ink-500">Applies to every shipment in this batch.</p>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-ink-900">Upload file</label>
                <input type="file" name="file" required accept=".xlsx,.xls,.csv"
                       class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
                <p class="mt-1 text-xs text-ink-500">.xlsx, .xls, or .csv — from the template above, or your own file with matching column headers.</p>
            </div>

            <button type="submit" class="w-full rounded-md bg-[var(--brand-primary)] px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:opacity-90">
                Preview upload
            </button>
        </form>
    </div>

    <script>
        // Picking an outlet clears the hub field and vice versa - the
        // two are mutually exclusive origins, matching the same
        // pattern used throughout the operational scan pages.
        document.getElementById('origin-hub-select').addEventListener('change', function () {
            if (this.value) document.getElementById('origin-outlet-select').value = '';
        });
        document.getElementById('origin-outlet-select').addEventListener('change', function () {
            if (this.value) document.getElementById('origin-hub-select').value = '';
        });
    </script>

</x-layouts.app>
