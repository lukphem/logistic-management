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
                    <label class="mb-1 block text-sm font-medium text-ink-900">Name</label>
                    <input type="text" name="name" value="{{ old('name', $rateCard->name) }}" placeholder="e.g. Express — Lagos Intra-city"
                           class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink-900">Service type</label>
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
            <h2 class="mb-1 text-sm font-semibold text-ink-900">Billing model</h2>
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
