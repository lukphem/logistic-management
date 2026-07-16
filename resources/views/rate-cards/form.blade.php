<x-layouts.app :title="$rateCard->exists ? 'Edit Rate Card' : 'New Rate Card'">

    @if (session('status'))
        <div class="mb-5 rounded-xl bg-status-delivered/10 px-4 py-3 text-sm font-medium text-status-delivered">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-5 rounded-xl bg-status-exception/10 px-4 py-3 text-sm text-status-exception">
            <ul class="list-inside list-disc">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ $rateCard->exists ? route('rate-cards.update', $rateCard) : route('rate-cards.store') }}" class="space-y-6">
        @csrf
        @if ($rateCard->exists) @method('PUT') @endif

        <div class="rounded-xl border border-line bg-surface-0 p-5 shadow-sm">
            <h2 class="mb-4 text-sm font-semibold text-ink-900">Basics</h2>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink-900">Name <x-required /></label>
                    <input type="text" name="name" value="{{ old('name', $rateCard->name) }}" placeholder="e.g. Express — Lagos Intra-city"
                           class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink-900">Service type <x-required /></label>
                    <select name="service_type" class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                        @foreach ($serviceNames as $key => $label)
                            <option value="{{ $key }}" @selected(old('service_type', $rateCard->service_type) === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink-900">Priority</label>
                    <input type="number" name="priority" value="{{ old('priority', $rateCard->priority ?? 0) }}"
                           class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
                    <p class="mt-1 text-xs text-ink-500">When a service type has more than one active card, the highest priority wins.</p>
                </div>
                <div class="flex items-end pb-2">
                    <label class="flex items-center gap-2 text-sm text-ink-900">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $rateCard->exists ? $rateCard->is_active : true)) class="rounded border-line">
                        Active
                    </label>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-line bg-surface-0 p-5 shadow-sm">
            <h2 class="mb-1 text-sm font-semibold text-ink-900">Billing model <x-required /></h2>
            <p class="mb-4 text-xs text-ink-500">Determines how the price is calculated. Fields below change based on your selection.</p>

            <select id="billing_model" name="billing_model"
                    class="mb-5 w-full max-w-sm rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                @foreach ($billingModels as $value => $label)
                    <option value="{{ $value }}" @selected(old('billing_model', $rateCard->billing_model ?? 'flat') === $value)>{{ $label }}</option>
                @endforeach
            </select>

            @php $config = old() ? old() : ($rateCard->model_config ?? []); @endphp

            <div data-model-group="flat" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink-900">Flat amount</label>
                    <input type="number" step="0.01" name="amount" value="{{ old('amount', $config['amount'] ?? '') }}"
                           class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                </div>
            </div>

            <div data-model-group="distance" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink-900">Rate per km</label>
                    <input type="number" step="0.01" name="per_km" value="{{ old('per_km', $config['per_km'] ?? '') }}"
                           class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                </div>
            </div>

            <div data-model-group="weight" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink-900">Rate per kg</label>
                    <input type="number" step="0.01" name="per_kg" value="{{ old('per_kg', $config['per_kg'] ?? '') }}"
                           class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                </div>
            </div>

            <div data-model-group="volumetric" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink-900">Volumetric divisor</label>
                    <input type="number" step="1" name="divisor" value="{{ old('divisor', $config['divisor'] ?? 5000) }}"
                           class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink-900">Rate per chargeable kg</label>
                    <input type="number" step="0.01" name="per_kg" value="{{ old('per_kg', $config['per_kg'] ?? '') }}"
                           class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                </div>
            </div>

            <div data-model-group="hybrid" class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink-900">Base fare</label>
                    <input type="number" step="0.01" name="base_fare" value="{{ old('base_fare', $config['base_fare'] ?? '') }}"
                           class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink-900">Rate per km</label>
                    <input type="number" step="0.01" name="per_km" value="{{ old('per_km', $config['per_km'] ?? '') }}"
                           class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink-900">Rate per kg</label>
                    <input type="number" step="0.01" name="per_kg" value="{{ old('per_kg', $config['per_kg'] ?? '') }}"
                           class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                </div>
            </div>

            <div data-model-group="service_multiplier" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink-900">Multiplier (e.g. 1.5 = +50%)</label>
                    <input type="number" step="0.01" name="multiplier" value="{{ old('multiplier', $config['multiplier'] ?? 1) }}"
                           class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                </div>
            </div>

            <div data-model-group="time_surcharge" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink-900">Peak-hour multiplier</label>
                    <input type="number" step="0.01" name="peak_multiplier" value="{{ old('peak_multiplier', $config['peak_multiplier'] ?? '') }}"
                           class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink-900">Weekend multiplier</label>
                    <input type="number" step="0.01" name="weekend_multiplier" value="{{ old('weekend_multiplier', $config['weekend_multiplier'] ?? '') }}"
                           class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                </div>
            </div>

            <div data-model-group="contract" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink-900">Fixed contract amount</label>
                    <input type="number" step="0.01" name="fixed_amount" value="{{ old('fixed_amount', $config['fixed_amount'] ?? '') }}"
                           class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                </div>
            </div>

            <div data-model-group="truckload" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink-900">Rate per truckload</label>
                    <input type="number" step="0.01" name="rate_per_truckload" value="{{ old('rate_per_truckload', $config['rate_per_truckload'] ?? '') }}"
                           class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                    <p class="mt-1 text-xs text-ink-500">Multiplied by the number of truckloads entered when the shipment is booked.</p>
                </div>
            </div>

            <div data-model-group="carton_rate" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink-900">Rate per carton</label>
                    <input type="number" step="0.01" name="rate_per_carton" value="{{ old('rate_per_carton', $config['rate_per_carton'] ?? '') }}"
                           class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                    <p class="mt-1 text-xs text-ink-500">Multiplied by the number of cartons entered when the shipment is booked.</p>
                </div>
            </div>

            <div data-model-group="zone_to_zone">
                <p class="text-sm text-ink-500">
                    Zone-to-zone pricing is a matrix, not a single value —
                    @if ($rateCard->exists)
                        manage the origin/destination price pairs below.
                    @else
                        save this rate card first, then manage the origin/destination price pairs on the edit page.
                    @endif
                </p>
            </div>

            <div data-model-group="origin_destination_weight">
                <p class="text-sm text-ink-500">
                    This is a full rate table (zone pair × weight band × service type), not a single value —
                    @if ($rateCard->exists)
                        manage the rows below.
                    @else
                        save this rate card first, then add rate rows on the edit page.
                    @endif
                    Each city's zone is assigned once under Setups → Billing → Zone Mapping.
                </p>
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="rounded-md bg-[var(--brand-primary)] px-5 py-2.5 text-sm font-semibold text-white hover:opacity-90">
                {{ $rateCard->exists ? 'Save changes' : 'Create rate card' }}
            </button>
        </div>
    </form>

    @if ($rateCard->exists && $rateCard->billing_model === 'zone_to_zone')
        <div class="mt-8 rounded-xl border border-line bg-surface-0 p-5 shadow-sm">
            <h2 class="mb-4 text-sm font-semibold text-ink-900">Zone-to-zone prices</h2>

            <table class="mb-5 w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-line text-xs uppercase tracking-wide text-ink-500">
                        <th class="py-2 font-medium">Origin</th>
                        <th class="py-2 font-medium">Destination</th>
                        <th class="py-2 font-medium">Price</th>
                        <th class="py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($matrixEntries as $entry)
                        <tr class="border-b border-line last:border-0">
                            <td class="py-2 text-ink-900">{{ $entry->originZone->name }}</td>
                            <td class="py-2 text-ink-900">{{ $entry->destinationZone->name }}</td>
                            <td class="py-2 font-mono text-ink-900">{{ number_format($entry->price, 2) }}</td>
                            <td class="py-2 text-right">
                                <form method="POST" action="{{ route('rate-cards.zone-prices.destroy', [$rateCard, $entry]) }}" onsubmit="return confirm('Remove this price pair?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-sm font-medium text-status-exception transition-colors hover:text-status-exception/70">Remove</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="py-6 text-center text-sm text-ink-500">No zone prices set yet.</td></tr>
                    @endforelse
                </tbody>
            </table>

            <form method="POST" action="{{ route('rate-cards.zone-prices.store', $rateCard) }}" class="flex flex-wrap items-end gap-4">
                @csrf
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink-900">Origin zone</label>
                    <select name="origin_zone_id" class="w-44 rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                        @foreach ($zones as $zone)
                            <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink-900">Destination zone</label>
                    <select name="destination_zone_id" class="w-44 rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                        @foreach ($zones as $zone)
                            <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink-900">Price</label>
                    <input type="number" step="0.01" name="price" class="w-32 rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                </div>
                <button type="submit" class="rounded-md bg-[var(--brand-primary)] px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:opacity-90 hover:shadow-md">
                    Save price
                </button>
            </form>
        </div>
    @endif

    @if ($rateCard->exists && $rateCard->billing_model === 'origin_destination_weight')
        <div class="mt-8 rounded-xl border border-line bg-surface-0 p-5 shadow-sm">
            <h2 class="mb-1 text-sm font-semibold text-ink-900">Rate table</h2>
            <p class="mb-4 text-xs text-ink-500">
                From Zone / To Zone are resolved automatically from each shipment's origin/destination city via
                Setups → Billing → Zone Mapping — no need for a row per city pair, only per zone pair.
            </p>

            <table class="mb-5 w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-line text-xs uppercase tracking-wide text-ink-500">
                        <th class="py-2 font-medium">From Zone</th>
                        <th class="py-2 font-medium">To Zone</th>
                        <th class="py-2 font-medium">Weight (kg)</th>
                        <th class="py-2 font-medium">Service</th>
                        <th class="py-2 font-medium">Price</th>
                        <th class="py-2 font-medium">Transit</th>
                        <th class="py-2 font-medium">Extra/kg</th>
                        <th class="py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($weightRates as $rate)
                        <tr class="border-b border-line last:border-0">
                            <td class="py-2 text-ink-900">{{ $rate->fromZone->name }}</td>
                            <td class="py-2 text-ink-900">{{ $rate->toZone->name }}</td>
                            <td class="py-2 text-ink-500">{{ rtrim(rtrim(number_format($rate->min_weight, 2), '0'), '.') }}–{{ rtrim(rtrim(number_format($rate->max_weight, 2), '0'), '.') }}</td>
                            <td class="py-2 text-ink-900">{{ ucfirst($rate->service_type) }}</td>
                            <td class="py-2 font-mono text-ink-900">{{ number_format($rate->price, 2) }}</td>
                            <td class="py-2 text-ink-500">{{ $rate->transit_days ?? '—' }}{{ $rate->transit_days ? ' day' . ($rate->transit_days == 1 ? '' : 's') : '' }}</td>
                            <td class="py-2 font-mono text-ink-500">{{ number_format($rate->extra_amount_per_extra_kg, 2) }}</td>
                            <td class="py-2 text-right">
                                <form method="POST" action="{{ route('rate-cards.weight-rates.destroy', [$rateCard, $rate]) }}" onsubmit="return confirm('Remove this rate row?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-sm font-medium text-status-exception transition-colors hover:text-status-exception/70">Remove</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="py-6 text-center text-sm text-ink-500">No rate rows set yet.</td></tr>
                    @endforelse
                </tbody>
            </table>

            <form method="POST" action="{{ route('rate-cards.weight-rates.store', $rateCard) }}" class="flex flex-wrap items-end gap-3">
                @csrf
                <div>
                    <label class="mb-1 block text-xs font-medium text-ink-900">From Zone</label>
                    <select name="from_zone_id" class="w-36 rounded-md border border-line px-2 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                        @foreach ($zones as $zone)
                            <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-ink-900">To Zone</label>
                    <select name="to_zone_id" class="w-36 rounded-md border border-line px-2 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                        @foreach ($zones as $zone)
                            <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-ink-900">Min weight</label>
                    <input type="number" step="0.01" min="0" name="min_weight" class="w-24 rounded-md border border-line px-2 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-ink-900">Max weight</label>
                    <input type="number" step="0.01" min="0" name="max_weight" class="w-24 rounded-md border border-line px-2 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-ink-900">Service type</label>
                    <select name="service_type" class="w-32 rounded-md border border-line px-2 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                        @foreach ($serviceNames as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-ink-900">Price</label>
                    <input type="number" step="0.01" min="0" name="price" class="w-24 rounded-md border border-line px-2 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-ink-900">Transit days</label>
                    <input type="number" min="0" name="transit_days" class="w-20 rounded-md border border-line px-2 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-ink-900">Extra/kg</label>
                    <input type="number" step="0.01" min="0" name="extra_amount_per_extra_kg" class="w-24 rounded-md border border-line px-2 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                </div>
                <button type="submit" class="rounded-md bg-[var(--brand-primary)] px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:opacity-90 hover:shadow-md">
                    Add row
                </button>
            </form>
        </div>
    @endif

    <script>
        (function () {
            const select = document.getElementById('billing_model');
            const groups = document.querySelectorAll('[data-model-group]');

            function sync() {
                groups.forEach(function (group) {
                    group.style.display = group.dataset.modelGroup === select.value ? '' : 'none';
                });
            }

            select.addEventListener('change', sync);
            sync();
        })();
    </script>

</x-layouts.app>
