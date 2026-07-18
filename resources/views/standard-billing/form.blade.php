<x-layouts.app :title="$tariff->exists ? 'Edit Tariff' : 'Add Tariff'">

    @if (session('status'))
        <div class="mb-5 rounded-xl bg-status-delivered/10 px-4 py-3 text-sm font-medium text-status-delivered">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ $tariff->exists ? route('standard-billing.update', $tariff) : route('standard-billing.store') }}" class="max-w-2xl space-y-4 rounded-xl border border-line bg-surface-0 shadow-sm p-5">
        @csrf
        @if ($tariff->exists) @method('PUT') @endif

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
            <label class="mb-1 block text-sm font-medium text-ink-900">Service type <x-required /></label>
            <select name="service_type_id" class="w-full max-w-sm rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                @foreach ($serviceTypes as $serviceType)
                    <option value="{{ $serviceType->id }}" @selected(old('service_type_id', $tariff->service_type_id) == $serviceType->id)>{{ $serviceType->name }}</option>
                @endforeach
            </select>
            @if ($serviceTypes->isEmpty())
                <p class="mt-1 text-xs text-status-exception">No service type is set to "Standard Billing" yet — set one under Setups → Billing → Service Types first.</p>
            @endif
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div>
                <label class="mb-1 block text-sm font-medium text-ink-900">Min weight (kg) <x-required /></label>
                <input type="number" step="0.01" min="0" name="min_weight" value="{{ old('min_weight', $tariff->min_weight) }}"
                       class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-ink-900">Max weight (kg) <x-required /></label>
                <input type="number" step="0.01" min="0" name="max_weight" value="{{ old('max_weight', $tariff->max_weight) }}"
                       class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
                <p class="mt-1 text-xs text-ink-500">Also the overage threshold — weight above this is billed in increments below.</p>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-ink-900">Additional weight (kg) <x-required /></label>
                <input type="number" step="0.01" min="0.01" name="additional_weight" value="{{ old('additional_weight', $tariff->additional_weight ?? 1) }}"
                       class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
                <p class="mt-1 text-xs text-ink-500">The increment size overage is charged in.</p>
            </div>
        </div>

        <label class="flex items-center gap-2 text-sm text-ink-900">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $tariff->exists ? $tariff->is_active : true)) class="rounded border-line">
            Active
        </label>

        <div class="flex justify-end gap-3 pt-2">
            <a href="{{ route('standard-billing.index') }}" class="rounded-md px-4 py-2 text-sm font-medium text-ink-500 hover:bg-surface-50">Cancel</a>
            <button type="submit" class="rounded-md bg-[var(--brand-primary)] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 hover:shadow-md">
                {{ $tariff->exists ? 'Save changes' : 'Create tariff' }}
            </button>
        </div>
    </form>

    @if ($tariff->exists)
        <div class="mt-8 max-w-3xl rounded-xl border border-line bg-surface-0 p-5 shadow-sm">
            <h2 class="mb-1 text-sm font-semibold text-ink-900">Zone prices</h2>
            <p class="mb-4 text-xs text-ink-500">
                Every zone is listed — fill in whichever ones this tariff actually prices and save them all at once.
                Leave Charge blank for a zone this tariff doesn't cover.
            </p>

            <form method="POST" action="{{ route('standard-billing.zone-prices.update', $tariff) }}">
                @csrf @method('PUT')

                @if ($errors->any())
                    <div class="mb-4 rounded-md bg-status-exception/10 px-4 py-3 text-sm text-status-exception">
                        <ul class="list-inside list-disc">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <table class="mb-5 w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-line text-xs uppercase tracking-wide text-ink-500">
                            <th class="py-2 pr-3 font-medium">Zone</th>
                            <th class="py-2 pr-3 font-medium">Charge</th>
                            <th class="py-2 pr-3 font-medium">Additional charge</th>
                            <th class="py-2 font-medium">Transit days</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($zoneRows as $row)
                            <tr class="border-b border-line last:border-0">
                                <td class="py-2 pr-3 text-ink-900">{{ $row->zone->name }}</td>
                                <td class="py-2 pr-3">
                                    <input type="number" step="0.01" min="0" name="zone_prices[{{ $row->zone->id }}][charge]"
                                           value="{{ old('zone_prices.' . $row->zone->id . '.charge', $row->charge) }}" placeholder="—"
                                           class="w-28 rounded-md border border-line px-2 py-1.5 text-sm outline-none focus:border-[var(--brand-primary)]">
                                </td>
                                <td class="py-2 pr-3">
                                    <input type="number" step="0.01" min="0" name="zone_prices[{{ $row->zone->id }}][additional_charge]"
                                           value="{{ old('zone_prices.' . $row->zone->id . '.additional_charge', $row->additional_charge) }}" placeholder="0"
                                           class="w-28 rounded-md border border-line px-2 py-1.5 text-sm outline-none focus:border-[var(--brand-primary)]">
                                </td>
                                <td class="py-2">
                                    <input type="number" min="0" name="zone_prices[{{ $row->zone->id }}][transit_days]"
                                           value="{{ old('zone_prices.' . $row->zone->id . '.transit_days', $row->transit_days) }}" placeholder="—"
                                           class="w-20 rounded-md border border-line px-2 py-1.5 text-sm outline-none focus:border-[var(--brand-primary)]">
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="py-6 text-center text-sm text-ink-500">No zones exist yet — create one under Billing → Zones first.</td></tr>
                        @endforelse
                    </tbody>
                </table>

                @if ($zoneRows->isNotEmpty())
                    <div class="flex justify-end">
                        <button type="submit" class="rounded-md bg-[var(--brand-primary)] px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 hover:shadow-md">
                            Save zone prices
                        </button>
                    </div>
                @endif
            </form>
        </div>
    @endif

</x-layouts.app>
