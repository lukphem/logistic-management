<x-layouts.app :title="$user->name">

    @if (session('status'))
        <div class="mb-4 rounded-xl bg-status-delivered/10 px-4 py-3 text-sm font-medium text-status-delivered">
            {{ session('status') }}
        </div>
    @endif

    @if (session('plaintext_api_secret'))
        <div class="mb-4 rounded-xl border border-[var(--brand-primary)]/30 bg-[var(--brand-primary)]/5 px-4 py-3 text-sm text-ink-900">
            <p class="font-semibold">API secret — copy it now, it will not be shown again:</p>
            <code class="mt-1 block break-all rounded-md bg-surface-0 px-3 py-2 font-mono text-xs">{{ session('plaintext_api_secret') }}</code>
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-4 rounded-xl border border-status-exception/30 bg-status-exception/5 p-4 text-sm text-status-exception">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="mb-5 flex items-start justify-between">
        <div class="flex items-center gap-4">
            @if ($account?->logo_url)
                <img src="{{ $account->logo_url }}" alt="{{ $account->company_name ?? $user->name }} logo" class="h-14 w-14 shrink-0 rounded-xl border border-line object-contain bg-surface-0 p-1">
            @elseif ($isOrganization)
                <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-xl border border-line bg-surface-50 text-lg font-semibold text-ink-500">
                    {{ strtoupper(substr($account->company_name ?? $user->name, 0, 1)) }}
                </div>
            @endif
            <div>
                <p class="text-lg font-semibold text-ink-900">{{ $account?->company_name ?? $user->name }}</p>
                <p class="text-sm text-ink-500">
                    {{ $user->email }}
                    <span class="mx-1">·</span>
                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $user->account_status === 'active' ? 'bg-status-delivered/10 text-status-delivered' : 'bg-ink-500/10 text-ink-500' }}">
                        {{ ucfirst($user->account_status) }}
                    </span>
                </p>
            </div>
        </div>
        <a href="{{ route('clients.edit', $user) }}" class="rounded-md border border-line px-4 py-2 text-sm font-medium text-ink-900 shadow-sm transition hover:bg-surface-50">Edit</a>
    </div>

    <div class="mb-5 grid grid-cols-2 gap-x-8 gap-y-2 rounded-xl border border-line bg-surface-0 shadow-sm p-5 text-sm sm:grid-cols-4">
        <div><p class="text-ink-500">Account Number</p><p class="font-semibold text-ink-900">{{ $profile?->account_number ?? '—' }}</p></div>
        <div><p class="text-ink-500">Client Type</p><p class="font-semibold text-ink-900">{{ $isOrganization ? 'Organization' : 'Individual' }}</p></div>
        <div><p class="text-ink-500">Company Name</p><p class="font-semibold text-ink-900">{{ $profile?->company_name ?? '—' }}</p></div>
        <div><p class="text-ink-500">Created By</p><p class="font-semibold text-ink-900">{{ $profile?->createdBy?->name ?? '—' }}</p></div>
        <div><p class="text-ink-500">Business Manager</p><p class="font-semibold text-ink-900">{{ $profile?->businessManager?->name ?? '—' }}</p></div>
        <div><p class="text-ink-500">Billing</p><p class="font-semibold text-ink-900">{{ $user->billingProfile?->billing_type === 'special' ? 'Special' : 'Standard' }}</p></div>
    </div>

    @unless ($isViewingDefault)
        <div class="mb-5 flex items-center justify-between rounded-xl border border-line bg-surface-50 px-4 py-3 text-sm">
            <p class="text-ink-900">
                Viewing <span class="font-semibold">{{ $account->account_name }}</span> — read-only.
                Tabs below reflect this account, but Add/Save actions are disabled until you switch to it.
            </p>
            <form method="POST" action="{{ route('clients.accounts.set-default', [$user, $account]) }}">
                @csrf
                <button type="submit" class="shrink-0 rounded-md border border-[var(--brand-primary)] px-3 py-1.5 text-xs font-semibold text-[var(--brand-primary)] transition hover:bg-[var(--brand-primary)]/5">
                    Switch to this account
                </button>
            </form>
        </div>
    @endunless

    <div class="border-b border-line">
        <nav class="-mb-px flex flex-wrap gap-4">
            @php
                $tabs = [
                    'overview' => 'Overview', 'accounts' => 'Accounts', 'transactions' => 'Transactions', 'tariff' => 'Tariff',
                    'discount' => 'Discount',
                ];
                if ($isOrganization) {
                    $tabs['department'] = 'Department';
                    $tabs['users'] = 'User';
                }
                $tabs += [
                    'service' => 'Service', 'document' => 'Document', 'security' => 'Security',
                    'managerial' => 'Managerial services',
                ];
            @endphp
            @foreach ($tabs as $key => $label)
                <button type="button" id="tab-btn-{{ $key }}" onclick="showClientTab('{{ $key }}')"
                        class="border-b-2 px-1 py-2.5 text-sm font-medium transition {{ $loop->first ? 'border-[var(--brand-primary)] text-ink-900 font-semibold' : 'border-transparent text-ink-500 hover:text-ink-900' }}">
                    {{ $label }}
                </button>
            @endforeach
        </nav>
    </div>

    {{-- ============ OVERVIEW ============ --}}
    <div id="tab-overview" class="mt-5 max-w-3xl space-y-4">
        <div class="rounded-xl border border-line bg-surface-0 shadow-sm p-5">
            <p class="mb-3 text-sm font-semibold text-ink-900">Client information</p>
            <div class="grid grid-cols-1 gap-x-8 gap-y-2 text-sm sm:grid-cols-2">
                <div class="flex justify-between border-b border-line py-1.5"><span class="text-ink-500">Industry</span><span class="text-ink-900">{{ $profile?->industry ?? '—' }}</span></div>
                <div class="flex justify-between border-b border-line py-1.5"><span class="text-ink-500">Country</span><span class="text-ink-900">{{ $profile?->country?->name ?? '—' }}</span></div>
                <div class="flex justify-between border-b border-line py-1.5"><span class="text-ink-500">State</span><span class="text-ink-900">{{ $profile?->state?->name ?? '—' }}</span></div>
                <div class="flex justify-between border-b border-line py-1.5"><span class="text-ink-500">Town</span><span class="text-ink-900">{{ $profile?->cityDisplayName() ?? '—' }}</span></div>
                <div class="flex justify-between border-b border-line py-1.5"><span class="text-ink-500">Territory</span><span class="text-ink-900">{{ $profile?->territory?->name ?? '—' }}</span></div>
                <div class="flex justify-between border-b border-line py-1.5"><span class="text-ink-500">Outlet</span><span class="text-ink-900">{{ $profile?->outlet?->name ?? 'N/A' }}</span></div>
                <div class="flex justify-between border-b border-line py-1.5"><span class="text-ink-500">Contact Person</span><span class="text-ink-900">{{ $profile?->contact_person_name ?? '—' }}</span></div>
                <div class="flex justify-between border-b border-line py-1.5"><span class="text-ink-500">Designation</span><span class="text-ink-900">{{ $profile?->contact_person_role ?? 'N/A' }}</span></div>
            </div>
            @if ($profile?->business_objective)
                <p class="mt-3 text-xs font-medium uppercase tracking-wide text-ink-500">Business objective</p>
                <p class="mt-1 text-sm text-ink-900">{{ $profile->business_objective }}</p>
            @endif
        </div>

        <div class="rounded-xl border border-line bg-surface-0 shadow-sm p-5">
            <p class="mb-3 text-sm font-semibold text-ink-900">Contact</p>
            <div class="grid grid-cols-1 gap-x-8 gap-y-2 text-sm sm:grid-cols-2">
                <div class="flex justify-between border-b border-line py-1.5"><span class="text-ink-500">Phone</span><span class="text-ink-900">{{ $user->phone_number ?? '—' }}</span></div>
                <div class="flex justify-between border-b border-line py-1.5"><span class="text-ink-500">Alternate phone</span><span class="text-ink-900">{{ $profile?->alternate_phone ?? 'N/A' }}</span></div>
                <div class="flex justify-between border-b border-line py-1.5 sm:col-span-2"><span class="text-ink-500">Address</span><span class="text-right text-ink-900">{{ $profile?->address ?? '—' }}</span></div>
                <div class="flex justify-between border-b border-line py-1.5 sm:col-span-2"><span class="text-ink-500">Billing address</span><span class="text-right text-ink-900">{{ $profile?->billing_address ?? 'Same as above' }}</span></div>
            </div>
        </div>
    </div>

    {{-- ============ ACCOUNTS ============ --}}
    <div id="tab-accounts" class="mt-5 max-w-3xl" style="display:none">
        <div class="rounded-xl border border-line bg-surface-0 shadow-sm p-5">
            <p class="mb-1 text-sm font-semibold text-ink-900">Accounts under {{ $user->name }}</p>
            <p class="mb-4 text-xs text-ink-500">Every other tab (Overview, Tariff, Discount, Department, User, Service, Managerial services) reflects whichever account below is marked "In use" — switch to configure a different one. Products, billing, and Business Manager stay exactly as configured per account; nothing is shared or reset when switching.</p>

            <table class="mb-4 w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-line text-xs uppercase tracking-wide text-ink-500">
                        <th class="py-2 font-medium">Account</th>
                        <th class="py-2 font-medium">Number</th>
                        <th class="py-2 font-medium">Type</th>
                        <th class="py-2 font-medium">Business Manager</th>
                        <th class="py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($accounts as $acct)
                        <tr class="border-b border-line last:border-0">
                            <td class="py-2 text-ink-900">
                                {{ $acct->account_name }}
                                @if ($acct->is_default)
                                    <span class="ml-2 inline-flex items-center rounded-full bg-[var(--brand-primary)]/10 px-2 py-0.5 text-xs font-medium text-[var(--brand-primary)]">In use</span>
                                @endif
                            </td>
                            <td class="py-2 text-ink-500">{{ $acct->account_number }}</td>
                            <td class="py-2 text-ink-500">{{ $acct->account_type === 'organization' ? 'Organization' : 'Individual' }}</td>
                            <td class="py-2 text-ink-500">{{ $acct->businessManager?->name ?? '—' }}</td>
                            <td class="py-2 text-right">
                                <a href="{{ route('clients.accounts.show', [$user, $acct]) }}" class="text-xs font-medium text-[var(--brand-primary)] hover:underline">View</a>
                                @unless ($acct->is_default)
                                    <span class="mx-1 text-ink-500">·</span>
                                    <form method="POST" action="{{ route('clients.accounts.set-default', [$user, $acct]) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="text-xs font-medium text-[var(--brand-primary)] hover:underline">Switch to this</button>
                                    </form>
                                    <span class="mx-1 text-ink-500">·</span>
                                    <form method="POST" action="{{ route('clients.accounts.destroy', [$user, $acct]) }}" class="inline" onsubmit="return confirm('Remove this account? This cannot be undone.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs font-medium text-status-exception hover:underline">Remove</button>
                                    </form>
                                @endunless
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <form method="POST" action="{{ route('clients.accounts.store', $user) }}" class="flex flex-wrap items-end gap-3 border-t border-line pt-4">
                @csrf
                <div>
                    <label class="mb-1 block text-xs font-medium text-ink-900">Account name</label>
                    <input type="text" name="account_name" placeholder="e.g. Abuja Account" required class="rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-ink-900">Account type</label>
                    <select name="account_type" required class="rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                        <option value="individual">Individual</option>
                        <option value="organization">Organization</option>
                    </select>
                </div>
                <button type="submit" class="rounded-md border border-[var(--brand-primary)] px-4 py-2 text-sm font-semibold text-[var(--brand-primary)] transition hover:bg-[var(--brand-primary)]/5">+ Add account</button>
            </form>
            <p class="mt-2 text-xs text-ink-500">After adding, switch to it above, then use Edit / Tariff / Discount / etc. to fill in its details — same as configuring any account.</p>
        </div>
    </div>

    {{-- ============ TRANSACTIONS ============ --}}
    <div id="tab-transactions" class="mt-5 max-w-4xl" style="display:none">
        <div class="overflow-x-auto rounded-xl border border-line bg-surface-0 shadow-sm">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-line text-xs uppercase tracking-wide text-ink-500">
                        <th class="px-5 py-3 font-medium">Tracking #</th>
                        <th class="px-5 py-3 font-medium">Status</th>
                        <th class="px-5 py-3 font-medium">Total</th>
                        <th class="px-5 py-3 font-medium">Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($shipments as $shipment)
                        <tr class="border-b border-line last:border-0 hover:bg-[var(--brand-primary)]/5">
                            <td class="px-5 py-3 font-medium text-ink-900">
                                <a href="{{ route('shipments.show', $shipment) }}" class="hover:underline">{{ $shipment->tracking_number }}</a>
                            </td>
                            <td class="px-5 py-3 text-ink-500">{{ ucfirst(str_replace('_', ' ', $shipment->current_status)) }}</td>
                            <td class="px-5 py-3 text-ink-900">{{ number_format($shipment->total_amount, 2) }}</td>
                            <td class="px-5 py-3 text-ink-500">{{ $shipment->created_at->format('j M Y') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-5 py-8 text-center text-sm text-ink-500">No shipments yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ============ TARIFF (special rates) ============ --}}
    <div id="tab-tariff" class="mt-5 max-w-3xl" style="display:none">
        <div class="rounded-xl border border-line bg-surface-0 shadow-sm p-5">
            <p class="mb-1 text-sm font-semibold text-ink-900">Special rates</p>
            <p class="mb-4 text-xs text-ink-500">A genuinely separate, negotiated tariff for this client — its own weight bands and zone pricing, not a discount off the standard rate. Checked before the shared Standard Billing tariff whenever this client books.</p>

            @forelse ($specialTariffs as $tariff)
                <div class="mb-3 rounded-lg border border-line p-3">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-medium text-ink-900">
                            {{ $tariff->serviceType->name }} — {{ rtrim(rtrim(number_format($tariff->min_weight, 2), '0'), '.') }}–{{ rtrim(rtrim(number_format($tariff->max_weight_limit, 2), '0'), '.') }} kg
                        </p>
                        <form method="POST" action="{{ route('clients.special-tariffs.destroy', [$user, $tariff]) }}" onsubmit="return confirm('Remove this special rate?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-xs font-medium text-status-exception hover:underline">Remove</button>
                        </form>
                    </div>
                    <p class="mt-1 text-xs text-ink-500">Overage from {{ rtrim(rtrim(number_format($tariff->max_weight, 2), '0'), '.') }} kg · {{ rtrim(rtrim(number_format($tariff->additional_weight, 2), '0'), '.') }} kg increments</p>
                    <table class="mt-2 w-full text-left text-xs">
                        <thead><tr class="text-ink-500"><th class="py-1 font-medium">Zone</th><th class="py-1 font-medium">Charge</th><th class="py-1 font-medium">Per increment</th><th class="py-1 font-medium">Transit</th></tr></thead>
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

            @if ($isViewingDefault)
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
                    <div><label class="mb-1 block text-xs font-medium text-ink-900">Min weight</label><input type="number" step="0.01" min="0" name="min_weight" required class="w-full rounded-md border border-line px-2 py-2 text-sm outline-none focus:border-[var(--brand-primary)]"></div>
                    <div><label class="mb-1 block text-xs font-medium text-ink-900">Max weight (overage from)</label><input type="number" step="0.01" min="0" name="max_weight" required class="w-full rounded-md border border-line px-2 py-2 text-sm outline-none focus:border-[var(--brand-primary)]"></div>
                    <div><label class="mb-1 block text-xs font-medium text-ink-900">Max weight limit</label><input type="number" step="0.01" min="0" name="max_weight_limit" required class="w-full rounded-md border border-line px-2 py-2 text-sm outline-none focus:border-[var(--brand-primary)]"></div>
                    <div><label class="mb-1 block text-xs font-medium text-ink-900">Increment size (kg)</label><input type="number" step="0.01" min="0.01" name="additional_weight" value="1" required class="w-full rounded-md border border-line px-2 py-2 text-sm outline-none focus:border-[var(--brand-primary)]"></div>
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
                    <button type="submit" class="rounded-md bg-[var(--brand-primary)] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 hover:shadow-md">Add special rate</button>
                </div>
            </form>
            @else
                <p class="border-t border-line pt-4 text-xs text-ink-500">Switch to this account (Accounts tab) to add a special rate.</p>
            @endif
        </div>
    </div>

    {{-- ============ DISCOUNT ============ --}}
    <div id="tab-discount" class="mt-5 max-w-3xl" style="display:none">
        <div class="rounded-xl border border-line bg-surface-0 shadow-sm p-5">
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <p class="text-sm font-semibold text-ink-900">Overall billing</p>
                    <p class="text-xs text-ink-500">Flat fallback discount for any service type without its own rate below.</p>
                </div>
                <a href="{{ route('client-billing.edit', ['type' => 'portal', 'id' => $user->id]) }}" class="text-xs font-medium text-[var(--brand-primary)] hover:underline">Edit</a>
            </div>
            <p class="mb-4 text-sm text-ink-900">
                Currently: {{ $user->billingProfile?->billing_type === 'special' ? 'Special (' . rtrim(rtrim(number_format($user->billingProfile->discount_percentage, 2), '0'), '.') . '% flat discount)' : 'Standard (no flat discount)' }}
            </p>

            <p class="mb-1 text-sm font-semibold text-ink-900">Per-service-type discounts</p>
            <p class="mb-4 text-xs text-ink-500">A discount on a specific service type this client is agreed and subscribed for — takes priority over the flat discount above for that service type only.</p>

            @if ($discounts->isNotEmpty())
                <table class="mb-4 w-full text-left text-sm">
                    <thead><tr class="border-b border-line text-xs uppercase tracking-wide text-ink-500"><th class="py-2 font-medium">Service type</th><th class="py-2 font-medium">Discount</th><th class="py-2"></th></tr></thead>
                    <tbody>
                        @foreach ($discounts as $discount)
                            <tr class="border-b border-line last:border-0">
                                <td class="py-2 text-ink-900">{{ $discount->serviceType->name }}</td>
                                <td class="py-2 text-ink-500">{{ rtrim(rtrim(number_format($discount->discount_percentage, 2), '0'), '.') }}%</td>
                                <td class="py-2 text-right">
                                    <form method="POST" action="{{ route('clients.discounts.destroy', [$user, $discount]) }}" onsubmit="return confirm('Remove this discount?');">
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

            @if ($isViewingDefault)
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
                    <input type="number" step="0.01" min="0" max="100" name="discount_percentage" required class="w-28 rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                </div>
                <button type="submit" class="rounded-md border border-[var(--brand-primary)] px-4 py-2 text-sm font-semibold text-[var(--brand-primary)] transition hover:bg-[var(--brand-primary)]/5">Add / update</button>
            </form>
            @else
                <p class="text-xs text-ink-500">Switch to this account (Accounts tab) to add a discount.</p>
            @endif
        </div>
    </div>

    @if ($isOrganization)
    {{-- ============ DEPARTMENT ============ --}}
    <div id="tab-department" class="mt-5 max-w-2xl" style="display:none">
        <div class="rounded-xl border border-line bg-surface-0 shadow-sm p-5">
            <p class="mb-3 text-sm font-semibold text-ink-900">Departments</p>
            @if ($departments->isNotEmpty())
                <ul class="mb-4 divide-y divide-line">
                    @foreach ($departments as $department)
                        <li class="flex items-center justify-between py-2 text-sm">
                            <span class="text-ink-900">{{ $department->name }}</span>
                            <form method="POST" action="{{ route('clients.departments.destroy', [$user, $department]) }}" onsubmit="return confirm('Remove this department?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-xs font-medium text-status-exception hover:underline">Remove</button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="mb-4 text-sm text-ink-500">No departments set up yet.</p>
            @endif
            @if ($isViewingDefault)
            <form method="POST" action="{{ route('clients.departments.store', $user) }}" class="flex items-end gap-3">
                @csrf
                <div class="flex-1">
                    <label class="mb-1 block text-xs font-medium text-ink-900">Department name</label>
                    <input type="text" name="name" required class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                </div>
                <button type="submit" class="rounded-md border border-[var(--brand-primary)] px-4 py-2 text-sm font-semibold text-[var(--brand-primary)] transition hover:bg-[var(--brand-primary)]/5">Add</button>
            </form>
            @else
                <p class="text-xs text-ink-500">Switch to this account (Accounts tab) to add a department.</p>
            @endif
        </div>
    </div>

    {{-- ============ USER (sub-users) ============ --}}
    <div id="tab-users" class="mt-5 max-w-2xl" style="display:none">
        <div class="rounded-xl border border-line bg-surface-0 shadow-sm p-5">
            <p class="mb-3 text-sm font-semibold text-ink-900">Users under {{ $user->name }}</p>
            @if ($subUsers->isNotEmpty())
                <table class="mb-4 w-full text-left text-sm">
                    <thead><tr class="border-b border-line text-xs uppercase tracking-wide text-ink-500"><th class="py-2 font-medium">Name</th><th class="py-2 font-medium">Email</th><th class="py-2 font-medium">Department</th><th class="py-2"></th></tr></thead>
                    <tbody>
                        @foreach ($subUsers as $subUser)
                            <tr class="border-b border-line last:border-0">
                                <td class="py-2 text-ink-900">{{ $subUser->name }}</td>
                                <td class="py-2 text-ink-500">{{ $subUser->email }}</td>
                                <td class="py-2 text-ink-500">{{ $subUser->clientProfile?->department?->name ?? '—' }}</td>
                                <td class="py-2 text-right">
                                    <form method="POST" action="{{ route('clients.sub-users.destroy', [$user, $subUser]) }}" onsubmit="return confirm('Remove this user?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs font-medium text-status-exception hover:underline">Remove</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p class="mb-4 text-sm text-ink-500">No sub-users yet.</p>
            @endif

            @if ($isViewingDefault)
            <form method="POST" action="{{ route('clients.sub-users.store', $user) }}" class="space-y-3 border-t border-line pt-4">
                @csrf
                <p class="text-xs font-semibold uppercase tracking-wide text-ink-500">Add a user</p>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div><label class="mb-1 block text-xs font-medium text-ink-900">Name</label><input type="text" name="name" required class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]"></div>
                    <div><label class="mb-1 block text-xs font-medium text-ink-900">Email</label><input type="email" name="email" required class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]"></div>
                    <div><label class="mb-1 block text-xs font-medium text-ink-900">Phone</label><input type="text" name="phone_number" required class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]"></div>
                    <div><label class="mb-1 block text-xs font-medium text-ink-900">Password</label><input type="password" name="password" required class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]"></div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-ink-900">Department <span class="font-normal text-ink-500">(optional)</span></label>
                        <select name="department_id" class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                            <option value="">None</option>
                            @foreach ($departments as $department)
                                <option value="{{ $department->id }}">{{ $department->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="flex justify-end">
                    <button type="submit" class="rounded-md bg-[var(--brand-primary)] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 hover:shadow-md">Add user</button>
                </div>
            </form>
            @else
                <p class="border-t border-line pt-4 text-xs text-ink-500">Switch to this account (Accounts tab) to add a user.</p>
            @endif
        </div>
    </div>
    @endif

    {{-- ============ SERVICE ============ --}}
    <div id="tab-service" class="mt-5 max-w-2xl" style="display:none">
        <div class="rounded-xl border border-line bg-surface-0 shadow-sm p-5">
            <p class="mb-1 text-sm font-semibold text-ink-900">Service access</p>
            <p class="mb-4 text-xs text-ink-500">Which service types this client can use. A service type with no toggle set below is available by default — this is for deliberately scoping a client down to a subset.@unless ($isViewingDefault) Switch to this account (Accounts tab) to change it. @endunless</p>
            <div class="divide-y divide-line">
                @foreach ($serviceTypes as $serviceType)
                    <form method="POST" action="{{ route('clients.services.store', $user) }}" class="flex items-center justify-between py-2.5">
                        @csrf
                        <input type="hidden" name="service_type_id" value="{{ $serviceType->id }}">
                        <span class="text-sm text-ink-900">{{ $serviceType->name }}</span>
                        <label class="flex cursor-pointer items-center gap-2 text-xs text-ink-500">
                            <input type="checkbox" name="is_active" value="1" onchange="this.form.submit()" {{ $isViewingDefault ? '' : 'disabled' }}
                                   @checked(!array_key_exists($serviceType->id, $subscriptions->toArray()) || $subscriptions[$serviceType->id]) class="rounded border-line">
                            Enabled
                        </label>
                    </form>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ============ DOCUMENT ============ --}}
    <div id="tab-document" class="mt-5 max-w-2xl" style="display:none">
        <div class="rounded-xl border border-line bg-surface-0 shadow-sm p-5">
            <p class="mb-3 text-sm font-semibold text-ink-900">Documents</p>
            @if ($documents->isNotEmpty())
                <table class="mb-4 w-full text-left text-sm">
                    <thead><tr class="border-b border-line text-xs uppercase tracking-wide text-ink-500"><th class="py-2 font-medium">Type</th><th class="py-2 font-medium">File</th><th class="py-2 font-medium">Uploaded</th><th class="py-2"></th></tr></thead>
                    <tbody>
                        @foreach ($documents as $document)
                            <tr class="border-b border-line last:border-0">
                                <td class="py-2 text-ink-900">{{ \App\Models\ClientDocument::DOCUMENT_TYPES[$document->document_type] ?? $document->document_type }}</td>
                                <td class="py-2"><a href="{{ $document->url }}" target="_blank" class="text-[var(--brand-primary)] hover:underline">{{ $document->original_filename }}</a></td>
                                <td class="py-2 text-ink-500">{{ $document->created_at->format('j M Y') }}</td>
                                <td class="py-2 text-right">
                                    <form method="POST" action="{{ route('clients.documents.destroy', [$user, $document]) }}" onsubmit="return confirm('Remove this document?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs font-medium text-status-exception hover:underline">Remove</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p class="mb-4 text-sm text-ink-500">No documents uploaded yet.</p>
            @endif

            <form method="POST" action="{{ route('clients.documents.store', $user) }}" enctype="multipart/form-data" class="flex flex-wrap items-end gap-3 border-t border-line pt-4">
                @csrf
                <div>
                    <label class="mb-1 block text-xs font-medium text-ink-900">Document type</label>
                    <select name="document_type" required class="rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                        @foreach (\App\Models\ClientDocument::DOCUMENT_TYPES as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-ink-900">File</label>
                    <input type="file" name="file" required class="rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                </div>
                <button type="submit" class="rounded-md bg-[var(--brand-primary)] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 hover:shadow-md">Upload</button>
            </form>
        </div>
    </div>

    {{-- ============ SECURITY (API access) ============ --}}
    <div id="tab-security" class="mt-5 max-w-2xl" style="display:none">
        <div class="rounded-xl border border-line bg-surface-0 shadow-sm p-5">
            <p class="mb-1 text-sm font-semibold text-ink-900">API access</p>
            @if (! $apiClient)
                <p class="mb-4 text-sm text-ink-500">No API access set up yet for this client.</p>
                <form method="POST" action="{{ route('clients.api-access.generate', $user) }}">
                    @csrf
                    <button type="submit" class="rounded-md bg-[var(--brand-primary)] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 hover:shadow-md">Generate API access</button>
                </form>
            @else
                <div class="mb-4 space-y-2 text-sm">
                    <div class="flex justify-between border-b border-line py-1.5"><span class="text-ink-500">API Key</span><code class="font-mono text-xs text-ink-900">{{ $apiClient->api_key }}</code></div>
                    <div class="flex justify-between border-b border-line py-1.5"><span class="text-ink-500">Status</span><span class="text-ink-900">{{ $apiClient->is_active ? 'Active' : 'Inactive' }}</span></div>
                </div>
                <form method="POST" action="{{ route('clients.api-access.generate', $user) }}" class="mb-4" onsubmit="return confirm('Regenerate? The existing key/secret stop working immediately.');">
                    @csrf
                    <button type="submit" class="text-xs font-medium text-status-exception hover:underline">Regenerate key/secret</button>
                </form>

                <form method="POST" action="{{ route('clients.api-access.update', $user) }}" class="mb-6 grid grid-cols-1 gap-3 border-t border-line pt-4 sm:grid-cols-3">
                    @csrf
                    @method('PUT')
                    <div>
                        <label class="mb-1 block text-xs font-medium text-ink-900">Response format</label>
                        <select name="api_response_format" class="w-full rounded-md border border-line px-2 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                            <option value="url" @selected($apiClient->api_response_format === 'url')>URL</option>
                            <option value="base64" @selected($apiClient->api_response_format === 'base64')>Base64</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-ink-900">Rate limit / min</label>
                        <input type="number" min="1" name="rate_limit_per_minute" value="{{ $apiClient->rate_limit_per_minute }}" class="w-full rounded-md border border-line px-2 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                    </div>
                    <div class="flex items-end">
                        <label class="flex cursor-pointer items-center gap-2 text-xs text-ink-900">
                            <input type="checkbox" name="ip_whitelist_enabled" value="1" @checked($apiClient->ip_whitelist_enabled) class="rounded border-line">
                            Enforce IP whitelist
                        </label>
                    </div>
                    <div class="sm:col-span-3">
                        <button type="submit" class="rounded-md border border-[var(--brand-primary)] px-4 py-2 text-xs font-semibold text-[var(--brand-primary)] transition hover:bg-[var(--brand-primary)]/5">Save settings</button>
                    </div>
                </form>

                <p class="mb-2 text-sm font-semibold text-ink-900">IP whitelist</p>
                <ul class="mb-3 divide-y divide-line text-sm">
                    @forelse ($apiClient->ipWhitelists as $entry)
                        <li class="flex items-center justify-between py-2">
                            <span class="text-ink-900">{{ $entry->ip_or_cidr }} <span class="text-xs text-ink-500">{{ $entry->label }}</span></span>
                            <form method="POST" action="{{ route('clients.ip-whitelist.destroy', [$user, $entry]) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-xs font-medium text-status-exception hover:underline">Remove</button>
                            </form>
                        </li>
                    @empty
                        <li class="py-2 text-ink-500">No IPs whitelisted.</li>
                    @endforelse
                </ul>
                <form method="POST" action="{{ route('clients.ip-whitelist.store', $user) }}" class="mb-6 flex items-end gap-2">
                    @csrf
                    <input type="text" name="ip_or_cidr" placeholder="e.g. 197.210.5.0/24" required class="flex-1 rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                    <input type="text" name="label" placeholder="Label (optional)" class="flex-1 rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                    <button type="submit" class="rounded-md border border-[var(--brand-primary)] px-3 py-2 text-xs font-semibold text-[var(--brand-primary)] transition hover:bg-[var(--brand-primary)]/5">Add</button>
                </form>

                <p class="mb-2 text-sm font-semibold text-ink-900">Webhooks</p>
                <ul class="mb-3 divide-y divide-line text-sm">
                    @forelse ($apiClient->webhookSubscriptions as $webhook)
                        <li class="flex items-center justify-between py-2">
                            <span class="text-ink-900 break-all">{{ $webhook->url }} <span class="text-xs text-ink-500">({{ implode(', ', $webhook->events) }})</span></span>
                            <form method="POST" action="{{ route('clients.webhooks.destroy', [$user, $webhook]) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="shrink-0 text-xs font-medium text-status-exception hover:underline">Remove</button>
                            </form>
                        </li>
                    @empty
                        <li class="py-2 text-ink-500">No webhooks configured.</li>
                    @endforelse
                </ul>
                <form method="POST" action="{{ route('clients.webhooks.store', $user) }}" class="space-y-2">
                    @csrf
                    <input type="url" name="url" placeholder="https://example.com/webhook" required class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                    <div class="flex flex-wrap gap-3 text-xs text-ink-900">
                        @foreach (['shipment.created', 'shipment.status_updated', 'shipment.delivered'] as $event)
                            <label class="flex items-center gap-1"><input type="checkbox" name="events[]" value="{{ $event }}" class="rounded border-line">{{ $event }}</label>
                        @endforeach
                    </div>
                    <button type="submit" class="rounded-md border border-[var(--brand-primary)] px-3 py-2 text-xs font-semibold text-[var(--brand-primary)] transition hover:bg-[var(--brand-primary)]/5">Add webhook</button>
                </form>
            @endif
        </div>
    </div>

    {{-- ============ MANAGERIAL SERVICES ============ --}}
    <div id="tab-managerial" class="mt-5 max-w-2xl" style="display:none">
        <form method="POST" action="{{ route('clients.managerial.update', $user) }}" class="rounded-xl border border-line bg-surface-0 shadow-sm p-5">
            @csrf
            @method('PUT')
            <fieldset {{ $isViewingDefault ? '' : 'disabled' }} class="space-y-0">
            @unless ($isViewingDefault)
                <p class="mb-4 text-xs text-ink-500">Switch to this account (Accounts tab) to change these settings.</p>
            @endunless

            <div class="mb-4 flex flex-wrap gap-6">
                <label class="flex cursor-pointer items-center gap-2 text-sm text-ink-900">
                    <input type="checkbox" name="warehouse_access" value="1" @checked($profile?->warehouse_access) class="rounded border-line">
                    Warehouse access
                </label>
                <label class="flex cursor-pointer items-center gap-2 text-sm text-ink-900">
                    <input type="checkbox" name="cod_enabled" value="1" @checked($profile?->cod_enabled) class="rounded border-line">
                    Cash on delivery enabled
                </label>
            </div>

            <div class="mb-4 border-t border-line pt-4">
                <label class="flex cursor-pointer items-center gap-2 text-sm text-ink-900">
                    <input type="checkbox" name="insurance_agreement" value="1" @checked($profile?->insurance_agreement) class="rounded border-line">
                    Insurance agreement in place
                </label>
                <div class="mt-2 grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-ink-900">Agreement date</label>
                        <input type="date" name="insurance_agreement_date" value="{{ $profile?->insurance_agreement_date?->format('Y-m-d') }}" class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-ink-900">Notes</label>
                        <input type="text" name="insurance_agreement_notes" value="{{ $profile?->insurance_agreement_notes }}" class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                    </div>
                </div>
            </div>

            <div class="mb-4 border-t border-line pt-4">
                <p class="mb-2 text-sm font-semibold text-ink-900">Invoice & SLA</p>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-ink-900">Invoice due (days)</label>
                        <input type="number" min="0" name="invoice_due_days" value="{{ $profile?->invoice_due_days }}" placeholder="e.g. 30" class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-ink-900">SLA: pickup within (hours)</label>
                        <input type="number" min="0" name="sla_pickup_hours" value="{{ $profile?->sla_pickup_hours }}" class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-ink-900">SLA: delivery within (days)</label>
                        <input type="number" min="0" name="sla_delivery_days" value="{{ $profile?->sla_delivery_days }}" class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                    </div>
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="rounded-md bg-[var(--brand-primary)] px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 hover:shadow-md">Save</button>
            </div>
            </fieldset>
        </form>
    </div>

    <script>
        function showClientTab(tab) {
            document.querySelectorAll('[id^="tab-"]:not([id^="tab-btn-"])').forEach(function (el) {
                el.style.display = el.id === 'tab-' + tab ? '' : 'none';
            });
            document.querySelectorAll('[id^="tab-btn-"]').forEach(function (btn) {
                const active = btn.id === 'tab-btn-' + tab;
                btn.classList.toggle('border-[var(--brand-primary)]', active);
                btn.classList.toggle('text-ink-900', active);
                btn.classList.toggle('font-semibold', active);
                btn.classList.toggle('border-transparent', !active);
                btn.classList.toggle('text-ink-500', !active);
            });
        }

        (function () {
            const container = document.getElementById('special-zone-rows');
            const addBtn = document.getElementById('add-zone-row');
            if (!addBtn) return;
            let index = 1;

            addBtn.addEventListener('click', function () {
                const template = container.querySelector('.zone-row');
                const clone = template.cloneNode(true);
                clone.querySelectorAll('select, input').forEach(function (el) {
                    el.name = el.name.replace(/zone_prices\[\d+\]/, `zone_prices[${index}]`);
                    if (el.tagName === 'SELECT') { el.selectedIndex = 0; } else { el.value = ''; }
                });
                container.appendChild(clone);
                index++;
            });
        })();
    </script>

</x-layouts.app>
