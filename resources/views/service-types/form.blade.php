<x-layouts.app :title="$serviceType->exists ? 'Edit Service Type' : 'Add Service Type'">

    <form method="POST" action="{{ $serviceType->exists ? route('service-types.update', $serviceType) : route('service-types.store') }}" class="max-w-xl space-y-4 rounded-xl border border-line bg-surface-0 shadow-sm p-5">
        @csrf
        @if ($serviceType->exists) @method('PUT') @endif

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
            <label class="mb-1 block text-sm font-medium text-ink-900">Name <x-required /></label>
            <input type="text" name="name" value="{{ old('name', $serviceType->name) }}" placeholder="e.g. Express"
                   class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-ink-900">Code <x-required /></label>
            <input type="text" name="code" value="{{ old('code', $serviceType->code) }}" placeholder="e.g. EXP"
                   class="w-full max-w-[10rem] rounded-md border border-line px-3 py-2 text-sm font-mono uppercase outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-ink-900">Billing model <span class="text-xs font-normal text-ink-500">(optional)</span></label>
            <select name="billing_model" class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                <option value="">Not set — can't be quoted or booked yet</option>
                @foreach (\App\Models\Setting::BILLING_MODELS as $key => $label)
                    <option value="{{ $key }}" @selected(old('billing_model', $serviceType->billing_model) === $key)>{{ $label }}</option>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-ink-500">Which calculation model prices shipments booked under this service type.</p>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-ink-900">Route type <x-required /></label>
            <select id="route_type" name="route_type" onchange="document.getElementById('trade-direction-field').style.display = this.value === 'international' ? '' : 'none';" class="w-full max-w-sm rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                <option value="domestic" @selected(old('route_type', $serviceType->route_type ?? 'domestic') === 'domestic')>Domestic</option>
                <option value="international" @selected(old('route_type', $serviceType->route_type ?? 'domestic') === 'international')>International</option>
            </select>
            <p class="mt-1 text-xs text-ink-500">Every service type is exactly one or the other — e.g. an "International Express" service that should never show up for a domestic booking.</p>
        </div>

        <div id="trade-direction-field" style="{{ old('route_type', $serviceType->route_type ?? 'domestic') === 'international' ? '' : 'display:none' }}">
            <label class="mb-1 block text-sm font-medium text-ink-900">Trade direction <x-required /></label>
            <div class="flex gap-3">
                <label class="flex flex-1 cursor-pointer items-center gap-2 rounded-lg border border-line p-3 text-sm text-ink-900 has-[:checked]:border-[var(--brand-primary)] has-[:checked]:bg-[var(--brand-primary)]/5">
                    <input type="radio" name="trade_direction" value="export" @checked(old('trade_direction', $serviceType->trade_direction) === 'export') class="rounded-full border-line">
                    Export (Nigeria is the origin)
                </label>
                <label class="flex flex-1 cursor-pointer items-center gap-2 rounded-lg border border-line p-3 text-sm text-ink-900 has-[:checked]:border-[var(--brand-primary)] has-[:checked]:bg-[var(--brand-primary)]/5">
                    <input type="radio" name="trade_direction" value="import" @checked(old('trade_direction', $serviceType->trade_direction) === 'import') class="rounded-full border-line">
                    Import (Nigeria is the destination)
                </label>
                <label class="flex flex-1 cursor-pointer items-center gap-2 rounded-lg border border-line p-3 text-sm text-ink-900 has-[:checked]:border-[var(--brand-primary)] has-[:checked]:bg-[var(--brand-primary)]/5">
                    <input type="radio" name="trade_direction" value="cross_trade" @checked(old('trade_direction', $serviceType->trade_direction) === 'cross_trade') class="rounded-full border-line">
                    Cross-Trade (neither side is Nigeria)
                </label>
            </div>
            <p class="mt-1 text-xs text-ink-500">Determines which side of the route Nigeria is on — or, for Cross-Trade, that neither side is — when this service type is used to check a rate or book a shipment.</p>
        </div>

        <label class="flex items-center gap-2 text-sm text-ink-900">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $serviceType->exists ? $serviceType->is_active : true)) class="rounded border-line">
            Active (selectable when booking a shipment)
        </label>

        <div class="flex justify-end gap-3 pt-2">
            <a href="{{ route('service-types.index') }}" class="rounded-md px-4 py-2 text-sm font-medium text-ink-500 hover:bg-surface-50">Cancel</a>
            <button type="submit" class="rounded-md bg-[var(--brand-primary)] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 hover:shadow-md">
                {{ $serviceType->exists ? 'Save changes' : 'Add service type' }}
            </button>
        </div>
    </form>

</x-layouts.app>
