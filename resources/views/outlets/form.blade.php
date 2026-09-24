<x-layouts.app :title="$outlet->exists ? 'Edit Outlet' : 'Add Outlet'">

    <form method="POST" action="{{ $outlet->exists ? route('outlets.update', $outlet) : route('outlets.store') }}" class="max-w-xl space-y-4 rounded-xl border border-line bg-surface-0 shadow-sm p-5">
        @csrf
        @if ($outlet->exists) @method('PUT') @endif

        @if ($errors->any())
            <div class="rounded-md bg-status-exception/10 px-4 py-3 text-sm text-status-exception">
                <ul class="list-inside list-disc">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div>
            <label class="mb-1 block text-sm font-medium text-ink-900">Hub <x-required /></label>
            <select name="hub_id" class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                @foreach ($hubs as $hub)
                    <option value="{{ $hub->id }}" @selected(old('hub_id', $outlet->hub_id) == $hub->id)>{{ $hub->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-ink-900">Outlet name <x-required /></label>
            <input type="text" name="name" value="{{ old('name', $outlet->name) }}" placeholder="e.g. Ikeja Agent Counter"
                   class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-ink-900">Code <x-required /></label>
            <input type="text" name="code" value="{{ old('code', $outlet->code) }}" placeholder="e.g. LOS-01-OUT-03"
                   class="w-full rounded-md border border-line px-3 py-2 text-sm font-mono outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-ink-900">Short code <span class="text-xs font-normal text-ink-500">(3 characters — used in client account numbers)</span></label>
            <input type="text" name="short_code" value="{{ old('short_code', $outlet->short_code) }}" maxlength="3" placeholder="e.g. LOS"
                   class="w-full max-w-[8rem] rounded-md border border-line px-3 py-2 text-sm font-mono uppercase outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
            @if (! $outlet->exists)
                <p class="mt-1 text-xs text-ink-500">Leave blank to auto-generate from the outlet name.</p>
            @endif
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-ink-900">Address <x-required /></label>
            <textarea name="address" rows="2"
                      class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">{{ old('address', $outlet->address) }}</textarea>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="mb-1 block text-sm font-medium text-ink-900">Latitude</label>
                <input type="text" name="latitude" value="{{ old('latitude', $outlet->latitude) }}"
                       class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-ink-900">Longitude</label>
                <input type="text" name="longitude" value="{{ old('longitude', $outlet->longitude) }}"
                       class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
            </div>
        </div>

        <label class="flex items-center gap-2 text-sm text-ink-900">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $outlet->is_active ?? true)) class="rounded border-line">
            Active
        </label>

        <label class="flex items-center gap-2 text-sm text-ink-900">
            <input type="checkbox" name="can_collect_cash" value="1" @checked(old('can_collect_cash', $outlet->can_collect_cash ?? true)) class="rounded border-line">
            Can collect cash
        </label>

        <label class="flex items-center gap-2 text-sm text-ink-900">
            <input type="checkbox" name="can_use_wallet" value="1" @checked(old('can_use_wallet', $outlet->can_use_wallet ?? true)) class="rounded border-line">
            Can settle with wallet
        </label>

        <label class="flex items-center gap-2 text-sm text-ink-900">
            <input type="checkbox" name="can_collect_online" value="1" @checked(old('can_collect_online', $outlet->can_collect_online ?? true)) class="rounded border-line">
            Can collect online (Paystack)
        </label>

        <div>
            <label class="mb-2 block text-sm font-medium text-ink-900">Billing methods available here</label>
            <div class="flex flex-wrap gap-4">
                @foreach (\App\Models\Setting::current()->supportedBillingModels() as $value => $label)
                    @php $enabled = old('enabled_billing_models', array_diff(array_keys(\App\Models\Setting::current()->supportedBillingModels()), $outlet->disabled_billing_models ?? [])); @endphp
                    <label class="flex items-center gap-2 text-sm text-ink-900">
                        <input type="checkbox" name="enabled_billing_models[]" value="{{ $value }}" @checked(in_array($value, $enabled)) class="rounded border-line">
                        {{ $label }}
                    </label>
                @endforeach
            </div>
            <p class="mt-1 text-xs text-ink-500">Which billing methods a walk-in shipment booked at this outlet can use — e.g. an agent counter that only ever does Fleet or Origin-to-Destination for walk-ins, never Zoning and Weight.</p>
        </div>

        <div>
            <label class="mb-2 block text-sm font-medium text-ink-900">Service types available here</label>
            <div class="flex flex-wrap gap-4">
                @foreach ($serviceTypes as $serviceType)
                    @php $enabledTypes = old('enabled_service_type_ids', array_diff($serviceTypes->pluck('id')->all(), $outlet->disabled_service_type_ids ?? [])); @endphp
                    <label class="flex items-center gap-2 text-sm text-ink-900">
                        <input type="checkbox" name="enabled_service_type_ids[]" value="{{ $serviceType->id }}" @checked(in_array($serviceType->id, $enabledTypes)) class="rounded border-line">
                        {{ $serviceType->name }}
                    </label>
                @endforeach
            </div>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-ink-900">Discount on standard tariff <x-required /></label>
            <div class="flex items-center gap-2">
                <input type="number" step="0.01" min="0" max="100" name="discount_percentage" value="{{ old('discount_percentage', $outlet->discount_percentage ?? 0) }}"
                       class="w-28 rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
                <span class="text-sm text-ink-500">%</span>
            </div>
            <p class="mt-1 text-xs text-ink-500">Applied to the standard tariff for a walk-in shipment booked at this outlet — 0 means no discount.</p>
        </div>

        <div class="flex justify-end gap-3 pt-2">
            <a href="{{ route('outlets.index') }}" class="rounded-md px-4 py-2 text-sm font-medium text-ink-500 hover:bg-surface-50">Cancel</a>
            <button type="submit" class="rounded-md bg-[var(--brand-primary)] px-4 py-2 text-sm font-semibold text-white hover:opacity-90">
                {{ $outlet->exists ? 'Save changes' : 'Add outlet' }}
            </button>
        </div>
    </form>

</x-layouts.app>
