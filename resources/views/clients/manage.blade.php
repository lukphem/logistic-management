<x-layouts.app :title="'Billing — ' . $user->name">

    @if (session('status'))
        <div class="mb-5 max-w-3xl rounded-xl bg-status-delivered/10 px-4 py-3 text-sm font-medium text-status-delivered">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-5 max-w-3xl rounded-xl border border-status-exception/30 bg-status-exception/5 p-4 text-sm text-status-exception">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="mb-5 flex items-center justify-between">
        <div>
            <p class="text-lg font-semibold text-ink-900">{{ $user->name }}</p>
            <p class="text-sm text-ink-500">
                {{ $user->clientProfile?->isOrganization() ? 'Organization — ' . $user->clientProfile->company_name : 'Individual' }}
            </p>
        </div>
        <a href="{{ route('clients.edit', $user) }}" class="text-sm font-medium text-[var(--brand-primary)] hover:underline">Edit profile</a>
    </div>

    <div class="max-w-3xl space-y-6">
        <div class="rounded-xl border border-line bg-surface-0 shadow-sm p-5">
            <div class="flex items-center justify-between">
                <p class="text-sm font-semibold text-ink-900">Overall billing</p>
                <a href="{{ route('client-billing.edit', ['type' => 'portal', 'id' => $user->id]) }}" class="text-xs font-medium text-[var(--brand-primary)] hover:underline">Edit</a>
            </div>
            <p class="mt-1 text-sm text-ink-500">
                By default, this client bills standard — everything below is layered on top only where set up.
                Currently: <span class="font-medium text-ink-900">{{ $user->billingProfile?->billing_type === 'special' ? 'Special (' . rtrim(rtrim(number_format($user->billingProfile->discount_percentage, 2), '0'), '.') . '% flat discount, applies to any service type without its own rate below)' : 'Standard' }}</span>
            </p>
        </div>

        <div class="rounded-xl border border-line bg-surface-0 shadow-sm p-5">
            <p class="mb-1 text-sm font-semibold text-ink-900">Per-service-type discounts</p>
            <p class="mb-4 text-xs text-ink-500">A discount on a specific service type this client is agreed and subscribed for — takes priority over the flat discount above for that service type only.</p>

            @if ($discounts->isNotEmpty())
                <table class="mb-4 w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-line text-xs uppercase tracking-wide text-ink-500">
                            <th class="py-2 font-medium">Service type</th>
                            <th class="py-2 font-medium">Discount</th>
                            <th class="py-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($discounts as $discount)
                            <tr class="border-b border-line last:border-0">
                                <td class="py-2 text-ink-900">{{ $discount->serviceType->name }}</td>
                                <td class="py-2 text-ink-500">{{ rtrim(rtrim(number_format($discount->discount_percentage, 2), '0'), '.') }}%</td>
                                <td class="py-2 text-right">
                                    <form method="POST" action="{{ route('clients.discounts.destroy', [$user, $discount]) }}" onsubmit="return confirm('Remove this discount? This service type will bill standard for {{ $user->name }} afterward.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs font-medium text-status-exception hover:underline">Remove</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

            <form method="POST" action="{{ route('clients.discounts.store', $user) }}" class="flex flex-wrap items-end gap-3">
                @csrf
                <div>
                    <label class="mb-1 block text-xs font-medium text-ink-900">Service type</label>
                    <select name="service_type_id" required class="rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                        <option value="">Select…</option>
                        @foreach ($serviceTypes as $serviceType)
                            <option value="{{ $serviceType->id }}">{{ $serviceType->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-ink-900">Discount %</label>
                    <input type="number" step="0.01" min="0" max="100" name="discount_percentage" required
                           class="w-28 rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                </div>
                <button type="submit" class="rounded-md border border-[var(--brand-primary)] px-4 py-2 text-sm font-semibold text-[var(--brand-primary)] transition hover:bg-[var(--brand-primary)]/5">
                    Add / update
                </button>
            </form>
        </div>

        <div class="rounded-xl border border-line bg-surface-0 shadow-sm p-5">
            <p class="mb-1 text-sm font-semibold text-ink-900">Special rates</p>
            <p class="mb-4 text-xs text-ink-500">A genuinely separate, negotiated tariff for this client — its own weight bands and zone pricing, not a discount off the standard rate. Checked before the shared Standard Billing tariff whenever this client books.</p>

            @forelse ($specialTariffs as $tariff)
                <div class="mb-3 rounded-lg border border-line p-3">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-medium text-ink-900">
                            {{ $tariff->serviceType->name }} — {{ rtrim(rtrim(number_format($tariff->min_weight, 2), '0'), '.') }}–{{ rtrim(rtrim(number_format($tariff->max_weight_limit, 2), '0'), '.') }} kg
                        </p>
                        <form method="POST" action="{{ route('clients.special-tariffs.destroy', [$user, $tariff]) }}" onsubmit="return confirm('Remove this special rate? This weight band will bill standard for {{ $user->name }} afterward.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-xs font-medium text-status-exception hover:underline">Remove</button>
                        </form>
                    </div>
                    <p class="mt-1 text-xs text-ink-500">Overage from {{ rtrim(rtrim(number_format($tariff->max_weight, 2), '0'), '.') }} kg · {{ rtrim(rtrim(number_format($tariff->additional_weight, 2), '0'), '.') }} kg increments</p>
                    <table class="mt-2 w-full text-left text-xs">
                        <thead>
                            <tr class="text-ink-500">
                                <th class="py-1 font-medium">Zone</th>
                                <th class="py-1 font-medium">Charge</th>
                                <th class="py-1 font-medium">Per increment</th>
                                <th class="py-1 font-medium">Transit</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($tariff->zonePrices as $zp)
                                <tr>
                                    <td class="py-1 text-ink-900">{{ $zp->zone->name }}</td>
                                    <td class="py-1 text-ink-500">{{ number_format($zp->charge, 2) }}</td>
                                    <td class="py-1 text-ink-500">{{ number_format($zp->additional_charge, 2) }}</td>
                                    <td class="py-1 text-ink-500">{{ $zp->transit_days ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @empty
                <p class="mb-4 text-sm text-ink-500">No special rates set up yet — this client bills standard everywhere.</p>
            @endforelse

            <form method="POST" action="{{ route('clients.special-tariffs.store', $user) }}" class="space-y-3 border-t border-line pt-4">
                @csrf
                <p class="text-xs font-semibold uppercase tracking-wide text-ink-500">Add a special rate</p>
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-ink-900">Service type</label>
                        <select name="service_type_id" required class="w-full rounded-md border border-line px-2 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                            <option value="">Select…</option>
                            @foreach ($serviceTypes as $serviceType)
                                <option value="{{ $serviceType->id }}">{{ $serviceType->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-ink-900">Min weight</label>
                        <input type="number" step="0.01" min="0" name="min_weight" required class="w-full rounded-md border border-line px-2 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-ink-900">Max weight (overage from)</label>
                        <input type="number" step="0.01" min="0" name="max_weight" required class="w-full rounded-md border border-line px-2 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-ink-900">Max weight limit</label>
                        <input type="number" step="0.01" min="0" name="max_weight_limit" required class="w-full rounded-md border border-line px-2 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-ink-900">Increment size (kg)</label>
                        <input type="number" step="0.01" min="0.01" name="additional_weight" value="1" required class="w-full rounded-md border border-line px-2 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                    </div>
                </div>

                <p class="text-xs font-semibold uppercase tracking-wide text-ink-500">Zone pricing</p>
                <div id="special-zone-rows" class="space-y-2">
                    <div class="zone-row grid grid-cols-2 gap-2 sm:grid-cols-4">
                        <select name="zone_prices[0][zone_id]" required class="rounded-md border border-line px-2 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                            <option value="">Zone…</option>
                            @foreach ($zones as $zone)
                                <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                            @endforeach
                        </select>
                        <input type="number" step="0.01" min="0" name="zone_prices[0][charge]" placeholder="Charge" required class="rounded-md border border-line px-2 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                        <input type="number" step="0.01" min="0" name="zone_prices[0][additional_charge]" placeholder="Per increment" class="rounded-md border border-line px-2 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                        <input type="number" min="0" name="zone_prices[0][transit_days]" placeholder="Transit days" class="rounded-md border border-line px-2 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                    </div>
                </div>
                <button type="button" id="add-zone-row" class="text-xs font-medium text-[var(--brand-primary)] hover:underline">+ Add another zone</button>

                <div class="flex justify-end pt-2">
                    <button type="submit" class="rounded-md bg-[var(--brand-primary)] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 hover:shadow-md">
                        Add special rate
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        (function () {
            const container = document.getElementById('special-zone-rows');
            const addBtn = document.getElementById('add-zone-row');
            let index = 1;

            addBtn.addEventListener('click', function () {
                const template = container.querySelector('.zone-row');
                const clone = template.cloneNode(true);
                clone.querySelectorAll('select, input').forEach(function (el) {
                    el.name = el.name.replace(/zone_prices\[\d+\]/, `zone_prices[${index}]`);
                    if (el.tagName === 'SELECT') {
                        el.selectedIndex = 0;
                    } else {
                        el.value = '';
                    }
                });
                container.appendChild(clone);
                index++;
            });
        })();
    </script>

</x-layouts.app>
