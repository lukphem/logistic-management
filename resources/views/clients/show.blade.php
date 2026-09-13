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

    @unless ($isViewingDefault)
        <div class="mb-4 flex items-center justify-between rounded-xl border border-line bg-surface-50 px-4 py-3 text-sm">
            <p class="text-ink-900">
                Viewing <span class="font-semibold">{{ $account->account_name }}</span> — read-only.
                Sections reflect this account, but Add/Save actions are disabled until you switch to it.
            </p>
            <form method="POST" action="{{ route('clients.accounts.set-default', [$user, $account]) }}">
                @csrf
                <button type="submit" class="shrink-0 rounded-md border border-[var(--brand-primary)] px-3 py-1.5 text-xs font-semibold text-[var(--brand-primary)] transition hover:bg-[var(--brand-primary)]/5">
                    Switch to this account
                </button>
            </form>
        </div>
    @endunless

    {{-- Top bar: identity + quick facts + quick action, stays fixed while sections change below --}}
    <div class="mb-5 flex flex-wrap items-center justify-between gap-4 rounded-xl border border-line bg-surface-0 shadow-sm p-4">
        <div class="flex items-center gap-4">
            @if ($account?->logo_url)
                <img src="{{ $account->logo_url }}" alt="{{ $account->company_name ?? $user->name }} logo" class="h-12 w-12 shrink-0 rounded-xl border border-line object-contain bg-surface-0 p-1">
            @elseif ($isOrganization)
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl border border-line bg-surface-50 text-base font-semibold text-ink-500">
                    {{ strtoupper(substr($account->company_name ?? $user->name, 0, 1)) }}
                </div>
            @else
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl border border-line bg-surface-50 text-ink-500">
                    <x-icon name="user" class="h-5 w-5" />
                </div>
            @endif
            <div>
                <p class="text-base font-semibold text-ink-900">{{ $account?->company_name ?? $user->name }}</p>
                <p class="text-xs text-ink-500">
                    {{ $user->email }}
                    <span class="mx-1">·</span>
                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $user->account_status === 'active' ? 'bg-status-delivered/10 text-status-delivered' : 'bg-ink-500/10 text-ink-500' }}">
                        {{ ucfirst($user->account_status) }}
                    </span>
                </p>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-x-6 gap-y-1 text-xs text-ink-500">
            <div><span class="block text-ink-500">Account #</span><span class="font-mono font-semibold text-ink-900">{{ $profile?->account_number ?? '—' }}</span></div>
            <div><span class="block text-ink-500">Type</span><span class="font-semibold text-ink-900">{{ $isOrganization ? 'Organization' : 'Individual' }}</span></div>
            <div><span class="block text-ink-500">Billing</span><span class="font-semibold text-ink-900">{{ $user->billingProfile?->billing_type === 'special' ? 'Special' : 'Standard' }}</span></div>
            <a href="{{ route('clients.edit', $user) }}" class="rounded-md border border-line px-4 py-2 text-sm font-medium text-ink-900 shadow-sm transition hover:bg-surface-50">Edit</a>
        </div>
    </div>

    <div class="flex flex-col gap-5 lg:flex-row">
        {{-- Left sidebar: persistent section nav --}}
        <nav class="shrink-0 lg:w-56">
            <ul class="space-y-0.5">
                @php
                    $sections = [
                        'overview' => ['label' => 'Overview', 'icon' => 'dashboard'],
                        'accounts' => ['label' => 'Accounts', 'icon' => 'building'],
                        'transactions' => ['label' => 'Transactions', 'icon' => 'box'],
                        'billing' => ['label' => 'Billing Setup', 'icon' => 'sliders'],
                    ];
                    if ($isOrganization) {
                        $sections['department'] = ['label' => 'Department', 'icon' => 'layers'];
                        $sections['users'] = ['label' => 'User', 'icon' => 'user'];
                    }
                    $sections += [
                        'document' => ['label' => 'Document', 'icon' => 'document'],
                        'security' => ['label' => 'Security', 'icon' => 'shield'],
                        'managerial' => ['label' => 'Managerial services', 'icon' => 'briefcase'],
                    ];
                @endphp
                @foreach ($sections as $key => $section)
                    <li>
                        <button type="button" id="side-nav-{{ $key }}" onclick="showClientTab('{{ $key }}')"
                                class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-left text-sm font-medium transition text-ink-500 hover:bg-surface-50 hover:text-ink-900">
                            <x-icon :name="$section['icon']" class="h-4 w-4 shrink-0" />
                            {{ $section['label'] }}
                        </button>
                    </li>
                @endforeach
            </ul>
        </nav>

        {{-- Main content: one section visible at a time --}}
        <div class="min-w-0 flex-1">

    {{-- ============ OVERVIEW ============ --}}
    <div id="tab-overview" class="space-y-5" style="display:none">
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            <div class="rounded-xl border border-line bg-surface-0 shadow-sm p-4">
                <p class="text-xs text-ink-500">Shipments</p>
                <p class="mt-1 text-2xl font-semibold text-ink-900">{{ $shipments->count() }}</p>
            </div>
            <div class="rounded-xl border border-line bg-surface-0 shadow-sm p-4">
                <p class="text-xs text-ink-500">Active discounts</p>
                <p class="mt-1 text-2xl font-semibold text-ink-900">{{ $discounts->count() }}</p>
            </div>
            <div class="rounded-xl border border-line bg-surface-0 shadow-sm p-4">
                <p class="text-xs text-ink-500">Special rates</p>
                <p class="mt-1 text-2xl font-semibold text-ink-900">{{ $specialTariffs->count() }}</p>
            </div>
            <div class="rounded-xl border border-line bg-surface-0 shadow-sm p-4">
                <p class="text-xs text-ink-500">Documents</p>
                <p class="mt-1 text-2xl font-semibold text-ink-900">{{ $documents->count() }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
            <div class="rounded-xl border border-line bg-surface-0 shadow-sm p-5">
                <p class="mb-3 text-sm font-semibold text-ink-900">Client information</p>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between border-b border-line py-1.5"><dt class="text-ink-500">Industry</dt><dd class="text-ink-900">{{ $profile?->industry ?? '—' }}</dd></div>
                    <div class="flex justify-between border-b border-line py-1.5"><dt class="text-ink-500">Country</dt><dd class="text-ink-900">{{ $profile?->country?->name ?? '—' }}</dd></div>
                    <div class="flex justify-between border-b border-line py-1.5"><dt class="text-ink-500">State</dt><dd class="text-ink-900">{{ $profile?->state?->name ?? '—' }}</dd></div>
                    <div class="flex justify-between border-b border-line py-1.5"><dt class="text-ink-500">Town</dt><dd class="text-ink-900">{{ $profile?->cityDisplayName() ?? '—' }}</dd></div>
                    <div class="flex justify-between border-b border-line py-1.5"><dt class="text-ink-500">Territory</dt><dd class="text-ink-900">{{ $profile?->territory?->name ?? '—' }}</dd></div>
                    <div class="flex justify-between border-b border-line py-1.5"><dt class="text-ink-500">Outlet</dt><dd class="text-ink-900">{{ $profile?->outlet?->name ?? 'N/A' }}</dd></div>
                    <div class="flex justify-between border-b border-line py-1.5"><dt class="text-ink-500">Contact Person</dt><dd class="text-ink-900">{{ $profile?->contact_person_name ?? '—' }}</dd></div>
                    <div class="flex justify-between py-1.5"><dt class="text-ink-500">Designation</dt><dd class="text-ink-900">{{ $profile?->contact_person_role ?? 'N/A' }}</dd></div>
                </dl>
                @if ($profile?->business_objective)
                    <p class="mt-3 text-xs font-medium uppercase tracking-wide text-ink-500">Business objective</p>
                    <p class="mt-1 text-sm text-ink-900">{{ $profile->business_objective }}</p>
                @endif
            </div>

            <div class="space-y-5">
                <div class="rounded-xl border border-line bg-surface-0 shadow-sm p-5">
                    <p class="mb-3 text-sm font-semibold text-ink-900">Contact</p>
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between border-b border-line py-1.5"><dt class="text-ink-500">Phone</dt><dd class="text-ink-900">{{ $user->phone_number ?? '—' }}</dd></div>
                        <div class="flex justify-between border-b border-line py-1.5"><dt class="text-ink-500">Alternate phone</dt><dd class="text-ink-900">{{ $profile?->alternate_phone ?? 'N/A' }}</dd></div>
                        <div class="flex justify-between border-b border-line py-1.5"><dt class="text-ink-500">Address</dt><dd class="text-right text-ink-900">{{ $profile?->address ?? '—' }}</dd></div>
                        <div class="flex justify-between py-1.5"><dt class="text-ink-500">Billing address</dt><dd class="text-right text-ink-900">{{ $profile?->billing_address ?? 'Same as above' }}</dd></div>
                    </dl>
                </div>

                <div class="rounded-xl border border-line bg-surface-0 shadow-sm p-5">
                    <p class="mb-3 text-sm font-semibold text-ink-900">Account record</p>
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between border-b border-line py-1.5"><dt class="text-ink-500">Created by</dt><dd class="text-ink-900">{{ $profile?->createdBy?->name ?? '—' }}</dd></div>
                        <div class="flex justify-between py-1.5"><dt class="text-ink-500">Business manager</dt><dd class="text-ink-900">{{ $profile?->businessManager?->name ?? '—' }}{{ $profile?->businessManager?->staff_short_code ? ' (' . $profile->businessManager->staff_short_code . ')' : '' }}</dd></div>
                    </dl>
                </div>

                <div class="rounded-xl border border-status-exception/30 bg-status-exception/5 p-5">
                    <p class="mb-1 text-sm font-semibold text-status-exception">Danger zone</p>
                    <p class="mb-3 text-xs text-ink-500">Permanently removes {{ $user->name }} — every account, tariff, discount, and document. Blocked if they have any shipment history, so it never silently disconnects past shipments from their client record.</p>
                    <form method="POST" action="{{ route('clients.destroy', $user) }}" onsubmit="return confirm('Permanently remove {{ $user->name }}? This cannot be undone.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="rounded-md border border-status-exception px-3 py-1.5 text-xs font-semibold text-status-exception transition hover:bg-status-exception/10">Delete client</button>
                    </form>
                </div>
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
                            <td class="py-2 text-ink-500">{{ $acct->businessManager?->name ?? '—' }}{{ $acct->businessManager?->staff_short_code ? ' (' . $acct->businessManager->staff_short_code . ')' : '' }}</td>
                            <td class="py-2 text-right">
                                <a href="{{ route('clients.accounts.show', [$user, $acct]) }}" class="text-xs font-medium text-[var(--brand-primary)] hover:underline">View</a>
                                <span class="mx-1 text-ink-500">·</span>
                                <button type="button" onclick="document.getElementById('billing-info-{{ $acct->id }}').classList.toggle('hidden')" class="text-xs font-medium text-[var(--brand-primary)] hover:underline">Billing &amp; Invoicing</button>
                                @if ($acct->is_default && $acct->account_type === 'individual')
                                    <span class="mx-1 text-ink-500">·</span>
                                    <button type="button" onclick="document.getElementById('upgrade-account-{{ $acct->id }}').classList.toggle('hidden')" class="text-xs font-medium text-[var(--brand-primary)] hover:underline">Upgrade to Organization</button>
                                @endif
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
                        <tr id="billing-info-{{ $acct->id }}" class="hidden border-b border-line last:border-0 bg-surface-50">
                            <td colspan="5" class="p-4">
                                <p class="mb-3 text-xs text-ink-500">Feeds invoice preparation for this specific account — contact and billing address, tax details, and invoicing terms all belong to the account, not the client as a whole, since {{ $user->name }}'s other accounts can be billed completely differently.</p>
                                <form method="POST" action="{{ route('clients.accounts.billing-info.update', [$user, $acct]) }}" class="space-y-4">
                                    @csrf
                                    @method('PUT')
                                    @if ($errors->{'billingInfo' . $acct->id}->any())
                                        <div class="rounded-md border border-status-exception/30 bg-status-exception/5 p-2 text-xs text-status-exception">
                                            <ul class="list-disc space-y-0.5 pl-4">
                                                @foreach ($errors->{'billingInfo' . $acct->id}->all() as $message)
                                                    <li>{{ $message }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif

                                    <div>
                                        <p class="mb-2 text-xs font-semibold text-ink-700">Contact &amp; address</p>
                                        @unless ($acct->is_default)
                                            <label class="mb-2 flex cursor-pointer items-center gap-2 text-xs text-ink-900">
                                                <input type="checkbox" name="use_default_contact" value="1" @checked($acct->use_default_contact)
                                                       onchange="document.getElementById('own-contact-{{ $acct->id }}').classList.toggle('hidden', this.checked)"
                                                       class="rounded border-line">
                                                Same as {{ $user->defaultAccount?->account_name ?? 'the main account' }}
                                                <span class="cursor-help text-ink-400" title="Keeps this account's contact/address always matching the main account's current details — not a one-time copy, so it stays in sync if the main account's info changes later.">ⓘ</span>
                                            </label>
                                        @endunless
                                        <div id="own-contact-{{ $acct->id }}" class="grid grid-cols-1 gap-3 sm:grid-cols-3 {{ ! $acct->is_default && $acct->use_default_contact ? 'hidden' : '' }}">
                                            <div>
                                                <label class="mb-1 block text-xs font-medium text-ink-900">Contact person</label>
                                                <input type="text" name="contact_person_name" value="{{ $acct->contact_person_name }}" class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                                            </div>
                                            <div>
                                                <label class="mb-1 block text-xs font-medium text-ink-900">Address</label>
                                                <input type="text" name="address" value="{{ $acct->address }}" class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                                            </div>
                                            <div>
                                                <label class="mb-1 block text-xs font-medium text-ink-900">Billing address</label>
                                                <input type="text" name="billing_address" value="{{ $acct->billing_address }}" placeholder="Same as address above" class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                                            </div>
                                        </div>
                                    </div>

                                    <div>
                                        <p class="mb-2 text-xs font-semibold text-ink-700">Tax &amp; VAT</p>
                                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                                            <div>
                                                <label class="mb-1 block text-xs font-medium text-ink-900">Client Tax ID</label>
                                                <input type="text" name="tin" value="{{ $acct->tin }}" class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                                            </div>
                                            <div>
                                                <label class="mb-2 flex cursor-pointer items-center gap-2 pt-6 text-sm text-ink-900">
                                                    <input type="checkbox" name="is_vatable" value="1" @checked($acct->is_vatable)
                                                           onchange="document.getElementById('vat-rate-{{ $acct->id }}').classList.toggle('hidden', !this.checked)"
                                                           class="rounded border-line">
                                                    VAT applies
                                                </label>
                                            </div>
                                            <div id="vat-rate-{{ $acct->id }}" class="{{ $acct->is_vatable ? '' : 'hidden' }}">
                                                <label class="mb-1 flex items-center gap-1 text-xs font-medium text-ink-900">VAT %
                                                    <span class="cursor-help text-ink-400" title="Leave blank to use the company-wide VAT rate from Settings — only fill this in if this account is taxed at a different rate.">ⓘ</span>
                                                </label>
                                                <input type="number" step="0.01" min="0" max="100" name="vat_percentage" value="{{ $acct->vat_percentage }}" placeholder="Company default" class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                                            </div>
                                        </div>
                                    </div>

                                    <div>
                                        <p class="mb-2 text-xs font-semibold text-ink-700">Charges</p>
                                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                            <div class="rounded-lg border border-line p-3">
                                                <label class="mb-2 flex cursor-pointer items-center gap-2 text-sm text-ink-900">
                                                    <input type="checkbox" name="is_pickup_chargeable" value="1" @checked($acct->is_pickup_chargeable)
                                                           onchange="document.getElementById('pickup-charge-{{ $acct->id }}').classList.toggle('hidden', !this.checked)"
                                                           class="rounded border-line">
                                                    Charge for pickup
                                                </label>
                                                <div id="pickup-charge-{{ $acct->id }}" class="{{ $acct->is_pickup_chargeable ? '' : 'hidden' }}">
                                                    <input type="number" step="0.01" min="0" name="pickup_charge" value="{{ $acct->pickup_charge }}" placeholder="Pickup charge amount" class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                                                </div>
                                            </div>
                                            <div class="rounded-lg border border-line p-3">
                                                <label class="mb-2 flex cursor-pointer items-center gap-2 text-sm text-ink-900">
                                                    <input type="checkbox" name="is_onforwarding_chargeable" value="1" @checked($acct->is_onforwarding_chargeable)
                                                           onchange="document.getElementById('onforwarding-charge-{{ $acct->id }}').classList.toggle('hidden', !this.checked)"
                                                           class="rounded border-line">
                                                    Charge for onforwarding
                                                </label>
                                                <div id="onforwarding-charge-{{ $acct->id }}" class="{{ $acct->is_onforwarding_chargeable ? '' : 'hidden' }}">
                                                    <input type="number" step="0.01" min="0" name="onforwarding_charge" value="{{ $acct->onforwarding_charge }}" placeholder="Onforwarding charge amount" class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div>
                                        <p class="mb-2 text-xs font-semibold text-ink-700">Delivery &amp; invoicing</p>
                                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                            <div>
                                                <label class="mb-1 flex items-center gap-1 text-xs font-medium text-ink-900">Maximum Delivery Attempt
                                                    <span class="cursor-help text-ink-400" title="Once a shipment on this account reaches this many scans marked as a delivery attempt, it's flagged — leave blank for no limit.">ⓘ</span>
                                                </label>
                                                <input type="number" min="1" name="maximum_delivery_attempts" value="{{ $acct->maximum_delivery_attempts }}" placeholder="No limit" class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                                            </div>
                                            <div>
                                                <label class="mb-1 block text-xs font-medium text-ink-900">Invoice Number of Days</label>
                                                <input type="number" min="0" name="invoice_due_days" value="{{ $acct->invoice_due_days }}" placeholder="e.g. 30" class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex justify-end">
                                        <button type="submit" class="rounded-md bg-[var(--brand-primary)] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:opacity-90">Save changes</button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                        @if ($acct->is_default && $acct->account_type === 'individual')
                            <tr id="upgrade-account-{{ $acct->id }}" class="hidden border-b border-line last:border-0 bg-surface-50">
                                <td colspan="5" class="p-4">
                                    <p class="mb-3 text-xs text-ink-500">Converts this account from Individual to Organization — the account keeps its number and history, but the ID type/number fields are replaced with company details below. This cannot be undone from here.</p>
                                    <form method="POST" action="{{ route('clients.upgrade', $user) }}" class="space-y-3">
                                        @csrf
                                        @if ($errors->any())
                                            <div class="rounded-md border border-status-exception/30 bg-status-exception/5 p-2 text-xs text-status-exception">
                                                <ul class="list-disc space-y-0.5 pl-4">
                                                    @foreach ($errors->all() as $message)
                                                        <li>{{ $message }}</li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        @endif
                                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                            <div>
                                                <label class="mb-1 block text-xs font-medium text-ink-900">Company name <x-required /></label>
                                                <input type="text" name="company_name" required class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                                            </div>
                                            <div>
                                                <label class="mb-1 block text-xs font-medium text-ink-900">RC number <x-required /></label>
                                                <input type="text" name="rc_number" required class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                                            </div>
                                            <div>
                                                <label class="mb-1 block text-xs font-medium text-ink-900">TIN</label>
                                                <input type="text" name="tin" class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                                            </div>
                                            <div>
                                                <label class="mb-1 block text-xs font-medium text-ink-900">Industry</label>
                                                <input type="text" name="industry" class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                                            </div>
                                            <div>
                                                <label class="mb-1 block text-xs font-medium text-ink-900">Contact person <x-required /></label>
                                                <input type="text" name="contact_person_name" required class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                                            </div>
                                            <div>
                                                <label class="mb-1 block text-xs font-medium text-ink-900">Contact person role</label>
                                                <input type="text" name="contact_person_role" class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                                            </div>
                                        </div>
                                        <div class="flex justify-end">
                                            <button type="submit" class="rounded-md bg-[var(--brand-primary)] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:opacity-90">Upgrade account</button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        @endif
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
    <div id="tab-billing" class="mt-5 max-w-4xl" style="display:none">
        @if ($accounts->count() > 1)
            <div class="mb-5 rounded-xl border border-line bg-surface-50 p-4">
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-ink-500">Configuring billing for</label>
                <select onchange="if (this.value) window.location.href = this.value;" class="w-full max-w-sm rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                    @foreach ($accounts as $acct)
                        <option value="{{ route('clients.accounts.show', [$user, $acct]) }}?tab=billing" @selected($acct->id === $account->id)>
                            {{ $acct->account_name }}{{ $acct->is_default ? ' (Default)' : '' }}
                        </option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-ink-500">Every account under this client can be configured directly here — no need to switch which one is Default first.</p>
            </div>
        @endif
        @if (true)
        <form method="POST" action="{{ route('clients.billing-models.update', [$user, $account]) }}" class="mb-5 rounded-xl border border-line bg-surface-0 shadow-sm p-5">
            @csrf
            @method('PUT')
            <p class="mb-1 text-sm font-semibold text-ink-900">Billing models available to this account</p>
            <p class="mb-3 text-xs text-ink-500">Every billing model is Standard by default (company-wide rates) until a discount or special rate is set below. Turn a model off entirely if this client never uses it.</p>
            <div class="flex flex-wrap gap-6">
                @foreach ($billingModels as $key => $label)
                    <label class="flex cursor-pointer items-center gap-2 text-sm text-ink-900">
                        <input type="checkbox" name="enabled_billing_models[]" value="{{ $key }}" @checked($account->usesBillingModel($key)) class="rounded border-line">
                        {{ $label }}
                    </label>
                @endforeach
            </div>
            <button type="submit" class="mt-3 rounded-md border border-[var(--brand-primary)] px-3 py-1.5 text-xs font-semibold text-[var(--brand-primary)] transition hover:bg-[var(--brand-primary)]/5">Save</button>
        </form>
        @endif

        @php
            $firstEnabledBillingModel = collect($billingModels)->keys()->first(function ($key) use ($serviceTypes, $account) {
                return $serviceTypes->where('billing_model', $key)->isNotEmpty() && $account->usesBillingModel($key);
            });
        @endphp
        {{-- One clickable sub-tab per billing model — a model the
             account isn't enabled for still shows, just locked, so
             it's a visible "not available" rather than hidden
             entirely. --}}
        <div class="mb-4 flex flex-wrap gap-2 border-b border-line pb-3">
            @foreach ($billingModels as $modelKey => $modelLabel)
                @php $modelHasServiceTypes = $serviceTypes->where('billing_model', $modelKey)->isNotEmpty(); @endphp
                @continue(! $modelHasServiceTypes)
                @php $modelEnabled = $account->usesBillingModel($modelKey); @endphp
                <button type="button"
                        @if ($modelEnabled) onclick="showBillingModelTab('{{ $modelKey }}')" @endif
                        id="billing-model-nav-{{ $modelKey }}"
                        @unless ($modelEnabled) disabled title="Not enabled for this account — turn it on above" @endunless
                        class="rounded-lg px-3 py-1.5 text-sm font-medium transition {{ ! $modelEnabled ? 'cursor-not-allowed text-ink-500/40' : ($modelKey === $firstEnabledBillingModel ? 'bg-[var(--brand-primary)]/10 text-[var(--brand-primary)]' : 'text-ink-500 hover:bg-surface-50 hover:text-ink-900') }}">
                    {{ $modelLabel }}
                    @unless ($modelEnabled)
                        <span class="ml-1 text-xs">🔒</span>
                    @endunless
                </button>
            @endforeach
        </div>

        @foreach ($billingModels as $modelKey => $modelLabel)
            @php $modelServiceTypes = $serviceTypes->where('billing_model', $modelKey); @endphp
            @if ($modelServiceTypes->isNotEmpty())
            <div id="billing-model-{{ $modelKey }}" class="mb-5 rounded-xl border border-line bg-surface-0 shadow-sm p-5" style="display:none">
                <div class="mb-3 flex items-center justify-between">
                    <p class="text-sm font-semibold text-ink-900">{{ $modelLabel }}</p>
                    @unless ($account->usesBillingModel($modelKey))
                        <span class="inline-flex items-center rounded-full bg-ink-500/10 px-2.5 py-0.5 text-xs font-medium text-ink-500">Disabled for this account</span>
                    @endunless
                </div>

                @if ($account->usesBillingModel($modelKey))
                    @php
                        $isSpecialMode = $account->isSpecialFor($modelKey);
                        $allowsFallback = $account->allowsFallbackToStandard($modelKey);
                    @endphp
                    <div class="mb-4 flex items-center justify-between rounded-lg border border-line bg-surface-50 p-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-ink-500">Mode</p>
                            <div class="mt-1 inline-flex rounded-md border border-line p-0.5 text-xs">
                                <span class="rounded px-2.5 py-1 font-medium {{ ! $isSpecialMode ? 'bg-[var(--brand-primary)] text-white' : 'text-ink-500' }}">Standard</span>
                                <span class="rounded px-2.5 py-1 font-medium {{ $isSpecialMode ? 'bg-[var(--brand-primary)] text-white' : 'text-ink-500' }}">Special</span>
                            </div>
                            <p class="mt-1 text-xs text-ink-500">
                                {{ $isSpecialMode ? 'Discount is switched off — only the special rates below apply.' : 'Discount applies below; special rates (if any) are ignored.' }}
                            </p>
                        </div>
                        @if (true)
                        <form method="POST" action="{{ route('clients.billing-mode.update', [$user, $account]) }}">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="billing_model" value="{{ $modelKey }}">
                            <input type="hidden" name="mode" value="{{ $isSpecialMode ? 'standard' : 'special' }}">
                            <button type="submit" class="rounded-md border border-[var(--brand-primary)] px-3 py-1.5 text-xs font-semibold text-[var(--brand-primary)] transition hover:bg-[var(--brand-primary)]/5">
                                Switch to {{ $isSpecialMode ? 'Standard' : 'Special' }}
                            </button>
                        </form>
                        @endif
                    </div>

                    @if ($isSpecialMode && true)
                    <div class="mb-4 rounded-lg border border-line p-3">
                        <form method="POST" action="{{ route('clients.billing-fallback.update', [$user, $account]) }}">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="billing_model" value="{{ $modelKey }}">
                            <label class="flex cursor-pointer items-start gap-2 text-sm text-ink-900">
                                <input type="checkbox" name="allow_fallback" value="1" onchange="this.form.submit()" @checked($allowsFallback) class="mt-0.5 rounded border-line">
                                <span>
                                    Fall back to the Standard rate when no special rate covers a shipment
                                    <span class="block text-xs text-ink-500">
                                        {{ $allowsFallback
                                            ? 'On — a coverage gap prices at the Standard rate (with its discount) instead of being blocked.'
                                            : 'Off (default) — a coverage gap blocks the shipment until a matching special rate is added.' }}
                                    </span>
                                </span>
                            </label>
                        </form>
                    </div>
                    @endif

                    @unless ($isSpecialMode)
                    {{-- Service types under this model: on/off + discount, one row each --}}
                    <table class="mb-4 w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-line text-xs uppercase tracking-wide text-ink-500">
                                <th class="py-2 font-medium">Service type</th>
                                <th class="py-2 font-medium">Access</th>
                                <th class="py-2 font-medium">Discount (Standard)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($modelServiceTypes as $serviceType)
                                <tr class="border-b border-line last:border-0">
                                    <td class="py-2 text-ink-900">{{ $serviceType->name }}</td>
                                    <td class="py-2">
                                        <form method="POST" action="{{ route('clients.services.store', [$user, $account]) }}">
                                            @csrf
                                            <input type="hidden" name="service_type_id" value="{{ $serviceType->id }}">
                                            <label class="flex cursor-pointer items-center gap-2 text-xs text-ink-500">
                                                <input type="checkbox" name="is_active" value="1" onchange="this.form.submit()" {{ true ? '' : 'disabled' }}
                                                       @checked(!array_key_exists($serviceType->id, $subscriptions->toArray()) || $subscriptions[$serviceType->id]) class="rounded border-line">
                                                Enabled
                                            </label>
                                        </form>
                                    </td>
                                    <td class="py-2">
                                        @if ($isSpecialMode)
                                            <span class="text-xs text-ink-500" title="Switched off — this billing model is in Special mode">Off (Special mode)</span>
                                        @elseif (true)
                                        <form method="POST" action="{{ route('clients.discounts.store', [$user, $account]) }}" class="flex items-center gap-2">
                                            @csrf
                                            <input type="hidden" name="service_type_id" value="{{ $serviceType->id }}">
                                            <div>
                                                <input type="number" step="0.01" min="0" max="100" name="discount_percentage"
                                                       value="{{ old('service_type_id') == $serviceType->id ? old('discount_percentage') : ($discounts[$serviceType->id]->discount_percentage ?? '') }}" placeholder="0"
                                                       class="w-20 rounded-md border px-2 py-1 text-xs outline-none focus:border-[var(--brand-primary)] {{ old('service_type_id') == $serviceType->id && $errors->has('discount_percentage') ? 'border-status-exception' : 'border-line' }}">
                                                @if (old('service_type_id') == $serviceType->id)
                                                    @error('discount_percentage')
                                                        <p class="mt-0.5 text-xs text-status-exception">{{ $message }}</p>
                                                    @enderror
                                                @endif
                                            </div>
                                            <span class="text-xs text-ink-500">%</span>
                                            <button type="submit" class="text-xs font-medium text-[var(--brand-primary)] hover:underline">Save</button>
                                        </form>
                                        @if (isset($discounts[$serviceType->id]))
                                            <form method="POST" action="{{ route('clients.discounts.destroy', [$user, $discounts[$serviceType->id]]) }}" onsubmit="return confirm('Remove this discount?');" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-xs font-medium text-status-exception hover:underline">Clear</button>
                                            </form>
                                        @endif
                                        @else
                                            <span class="text-xs text-ink-500">{{ isset($discounts[$serviceType->id]) ? rtrim(rtrim(number_format($discounts[$serviceType->id]->discount_percentage, 2), '0'), '.') . '%' : '—' }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @endunless

                    @if ($isSpecialMode)
                    {{-- ===== Special rates, shaped per billing model ===== --}}
                    @if ($modelKey === 'standard_billing')
                        <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-ink-500">Special rates (Zoning & Weight)</p>
                        <div class="mb-3 overflow-x-auto rounded-xl border border-line bg-surface-0 shadow-sm">
                            <table class="w-full text-left text-sm">
                                <thead>
                                    <tr class="border-b border-line text-xs uppercase tracking-wide text-ink-500">
                                        <th class="px-5 py-3 font-medium">Service type</th>
                                        <th class="px-5 py-3 font-medium">Weight band</th>
                                        <th class="px-5 py-3 font-medium">Additional wt.</th>
                                        <th class="px-5 py-3 font-medium">Zone</th>
                                        <th class="px-5 py-3 font-medium">Charge</th>
                                        <th class="px-5 py-3 font-medium">Additional charge</th>
                                        <th class="px-5 py-3 font-medium">Transit days</th>
                                        <th class="px-5 py-3 font-medium">Status</th>
                                        <th class="px-5 py-3"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($specialTariffs as $tariff)
                                        @php $rowCount = max($tariff->zonePrices->count(), 1); @endphp
                                        @forelse ($tariff->zonePrices as $i => $price)
                                            <tr class="border-b border-line last:border-0 odd:bg-surface-0 even:bg-surface-50/50 hover:bg-[var(--brand-primary)]/5 transition-colors">
                                                @if ($i === 0)
                                                    <td class="px-5 py-3 font-medium text-ink-900" rowspan="{{ $rowCount }}">{{ $tariff->serviceType->name }}</td>
                                                    <td class="px-5 py-3 text-ink-500" rowspan="{{ $rowCount }}">
                                                        {{ rtrim(rtrim(number_format($tariff->min_weight, 2), '0'), '.') }}–{{ rtrim(rtrim(number_format($tariff->max_weight_limit, 2), '0'), '.') }} kg
                                                        @if ((float) $tariff->max_weight !== (float) $tariff->min_weight)
                                                            <br><span class="text-xs">overage from {{ rtrim(rtrim(number_format($tariff->max_weight, 2), '0'), '.') }} kg</span>
                                                        @endif
                                                    </td>
                                                    <td class="px-5 py-3 text-ink-500" rowspan="{{ $rowCount }}">{{ rtrim(rtrim(number_format($tariff->additional_weight, 2), '0'), '.') }} kg</td>
                                                @endif
                                                <td class="px-5 py-3 text-ink-900">{{ $price->zone->name }}</td>
                                                <td class="px-5 py-3 font-mono text-ink-900">{{ number_format($price->charge, 2) }}</td>
                                                <td class="px-5 py-3 font-mono text-ink-500">{{ number_format($price->additional_charge, 2) }}</td>
                                                <td class="px-5 py-3 text-ink-500">{{ $price->transit_days ?? '—' }}</td>
                                                @if ($i === 0)
                                                    <td class="px-5 py-3" rowspan="{{ $rowCount }}">
                                                        <span class="inline-flex items-center rounded-full bg-status-delivered/10 px-2.5 py-0.5 text-xs font-medium text-status-delivered">Active</span>
                                                    </td>
                                                    <td class="px-5 py-3 text-right" rowspan="{{ $rowCount }}">
                                                        <button type="button" onclick="document.getElementById('edit-standard-{{ $tariff->id }}').classList.toggle('hidden')" class="text-sm font-medium text-[var(--brand-primary)] hover:underline">Edit</button>
                                                        <form method="POST" action="{{ route('clients.special-tariffs.destroy', [$user, $tariff]) }}" class="inline" onsubmit="return confirm('Remove this special rate?');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="ml-3 text-sm font-medium text-status-exception hover:underline">Remove</button>
                                                        </form>
                                                    </td>
                                                @endif
                                            </tr>
                                        @empty
                                            <tr class="border-b border-line last:border-0 odd:bg-surface-0 even:bg-surface-50/50 hover:bg-[var(--brand-primary)]/5 transition-colors">
                                                <td class="px-5 py-3 font-medium text-ink-900">{{ $tariff->serviceType->name }}</td>
                                                <td class="px-5 py-3 text-ink-500">{{ rtrim(rtrim(number_format($tariff->min_weight, 2), '0'), '.') }}–{{ rtrim(rtrim(number_format($tariff->max_weight_limit, 2), '0'), '.') }} kg</td>
                                                <td class="px-5 py-3 text-ink-500">{{ rtrim(rtrim(number_format($tariff->additional_weight, 2), '0'), '.') }} kg</td>
                                                <td class="px-5 py-3 text-ink-500" colspan="3">No zone prices set yet.</td>
                                                <td class="px-5 py-3">
                                                    <span class="inline-flex items-center rounded-full bg-status-delivered/10 px-2.5 py-0.5 text-xs font-medium text-status-delivered">Active</span>
                                                </td>
                                                <td class="px-5 py-3 text-right">
                                                    <button type="button" onclick="document.getElementById('edit-standard-{{ $tariff->id }}').classList.toggle('hidden')" class="text-sm font-medium text-[var(--brand-primary)] hover:underline">Edit</button>
                                                    <form method="POST" action="{{ route('clients.special-tariffs.destroy', [$user, $tariff]) }}" class="inline" onsubmit="return confirm('Remove this special rate?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="ml-3 text-sm font-medium text-status-exception hover:underline">Remove</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @endforelse
                                        <tr id="edit-standard-{{ $tariff->id }}" class="hidden border-b border-line last:border-0 bg-surface-50">
                                            <td colspan="9" class="p-4">
                                                <form method="POST" action="{{ route('clients.special-tariffs.update', [$user, $tariff]) }}" class="space-y-3">
                                                    @csrf
                                                    @method('PUT')
                                                    @if ($errors->{'standardTariff' . $tariff->id}->any())
                                                        <div class="rounded-md border border-status-exception/30 bg-status-exception/5 p-2 text-xs text-status-exception">
                                                            <ul class="list-disc space-y-0.5 pl-4">
                                                                @foreach ($errors->{'standardTariff' . $tariff->id}->all() as $message)
                                                                    <li>{{ $message }}</li>
                                                                @endforeach
                                                            </ul>
                                                        </div>
                                                    @endif
                                                    <div class="mb-3 max-w-sm">
                                                        <label class="mb-1 block text-xs font-medium text-ink-900">Service type</label>
                                                        <select name="service_type_id" required class="w-full rounded-md border border-line px-2 py-1.5 text-xs outline-none focus:border-[var(--brand-primary)]">
                                                            @foreach ($modelServiceTypes as $serviceType)
                                                                <option value="{{ $serviceType->id }}" @selected($tariff->service_type_id === $serviceType->id)>{{ $serviceType->name }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                                                        <input type="number" step="0.01" min="0" name="min_weight" value="{{ $tariff->min_weight }}" required placeholder="Min weight" class="rounded-md border border-line px-2 py-1.5 text-xs outline-none focus:border-[var(--brand-primary)]">
                                                        <input type="number" step="0.01" min="0" name="max_weight" value="{{ $tariff->max_weight }}" required placeholder="Max weight" class="rounded-md border border-line px-2 py-1.5 text-xs outline-none focus:border-[var(--brand-primary)]">
                                                        <input type="number" step="0.01" min="0.01" name="additional_weight" value="{{ $tariff->additional_weight }}" required placeholder="Increment size" class="rounded-md border border-line px-2 py-1.5 text-xs outline-none focus:border-[var(--brand-primary)]">
                                                        <input type="number" step="0.01" min="0" name="max_weight_limit" value="{{ $tariff->max_weight_limit }}" required placeholder="Max weight limit" class="rounded-md border border-line px-2 py-1.5 text-xs outline-none focus:border-[var(--brand-primary)]">
                                                    </div>
                                                    <div class="zone-rows-container space-y-2">
                                                        @foreach ($tariff->zonePrices as $i => $zp)
                                                            <div class="zone-row grid grid-cols-2 gap-2 sm:grid-cols-4">
                                                                <select name="zone_prices[{{ $i }}][zone_id]" required class="rounded-md border border-line px-2 py-1.5 text-xs outline-none focus:border-[var(--brand-primary)]">
                                                                    @foreach ($zones as $zone)
                                                                        <option value="{{ $zone->id }}" @selected($zp->zone_id === $zone->id)>{{ $zone->name }}</option>
                                                                    @endforeach
                                                                </select>
                                                                <input type="number" step="0.01" min="0" name="zone_prices[{{ $i }}][charge]" value="{{ $zp->charge }}" placeholder="Charge" required class="rounded-md border border-line px-2 py-1.5 text-xs outline-none focus:border-[var(--brand-primary)]">
                                                                <input type="number" step="0.01" min="0" name="zone_prices[{{ $i }}][additional_charge]" value="{{ $zp->additional_charge }}" placeholder="Per increment" class="rounded-md border border-line px-2 py-1.5 text-xs outline-none focus:border-[var(--brand-primary)]">
                                                                <input type="number" min="0" name="zone_prices[{{ $i }}][transit_days]" value="{{ $zp->transit_days }}" placeholder="Transit days" class="rounded-md border border-line px-2 py-1.5 text-xs outline-none focus:border-[var(--brand-primary)]">
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                    <button type="button" class="add-zone-row-btn text-xs font-medium text-[var(--brand-primary)] hover:underline">+ Add another zone</button>
                                                    <div class="flex justify-end">
                                                        <button type="submit" class="rounded-md bg-[var(--brand-primary)] px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition hover:opacity-90">Save changes</button>
                                                    </div>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="9" class="px-5 py-8 text-center text-sm text-status-exception">No special rates yet — shipments for this weight/zone will be <strong>blocked</strong> until one is added below (Special mode never falls back to the standard rate).</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if (true)
                        <div class="flex items-center justify-between border-t border-line pt-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-ink-500">Add a special rate</p>
                            <button type="button" onclick="document.getElementById('import-standard-{{ $account->id }}').classList.toggle('hidden')" class="text-xs font-medium text-[var(--brand-primary)] hover:underline">Bulk import (CSV) ▾</button>
                        </div>
                        <div id="import-standard-{{ $account->id }}" class="hidden mb-3 rounded-lg border border-line bg-surface-50 p-3">
                            <p class="mb-2 text-xs text-ink-500">One row per zone — several rows with the same product/weight range are combined into one rate with multiple zone prices. <a href="{{ route('clients.tariff-template', 'standard') }}" class="font-medium text-[var(--brand-primary)] hover:underline">Download a template</a> to see the exact columns.</p>
                            <form method="POST" action="{{ route('clients.special-tariffs.import', [$user, $account]) }}" enctype="multipart/form-data" class="flex items-center gap-2">
                                @csrf
                                <input type="file" name="file" accept=".csv,.txt" required class="block flex-1 text-xs text-ink-900 file:mr-2 file:rounded-md file:border-0 file:bg-[var(--brand-primary)]/10 file:px-3 file:py-1.5 file:text-xs file:font-medium file:text-[var(--brand-primary)]">
                                <button type="submit" class="shrink-0 rounded-md border border-[var(--brand-primary)] px-3 py-1.5 text-xs font-semibold text-[var(--brand-primary)] transition hover:bg-[var(--brand-primary)]/5">Import</button>
                            </form>
                        </div>
                        <form method="POST" action="{{ route('clients.special-tariffs.store', [$user, $account]) }}" class="space-y-4">
                            @csrf
                            @if ($errors->standardTariff->any())
                                <div class="rounded-md border border-status-exception/30 bg-status-exception/5 p-3 text-xs text-status-exception">
                                    <ul class="list-disc space-y-0.5 pl-4">
                                        @foreach ($errors->standardTariff->all() as $message)
                                            <li>{{ $message }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <div>
                                <p class="mb-2 text-xs font-semibold text-ink-700">Product</p>
                                <div class="mb-3">
                                    <label class="mb-1 block text-xs font-medium text-ink-900">Route</label>
                                    <div class="flex gap-2">
                                        <label class="flex cursor-pointer items-center gap-1.5 rounded-md border border-line px-3 py-1.5 text-xs text-ink-900 has-[:checked]:border-[var(--brand-primary)] has-[:checked]:bg-[var(--brand-primary)]/5">
                                            <input type="radio" name="_route_type_standard" value="domestic" checked onchange="filterServiceTypesByRoute(this, 'standard')">
                                            Domestic
                                        </label>
                                        <label class="flex cursor-pointer items-center gap-1.5 rounded-md border border-line px-3 py-1.5 text-xs text-ink-900 has-[:checked]:border-[var(--brand-primary)] has-[:checked]:bg-[var(--brand-primary)]/5">
                                            <input type="radio" name="_route_type_standard" value="international" onchange="filterServiceTypesByRoute(this, 'standard')">
                                            International
                                        </label>
                                    </div>
                                </div>
                                <div class="mb-4 max-w-sm">
                                    <label class="mb-1 block text-xs font-medium text-ink-900">Service type</label>
                                    <select name="service_type_id" data-route-select="standard" required class="w-full rounded-md border border-line px-2 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                                        <option value="">Select…</option>
                                        @foreach ($modelServiceTypes as $serviceType)
                                            <option value="{{ $serviceType->id }}" data-route-type="{{ $serviceType->route_type }}" @style(['display: none' => $serviceType->route_type !== 'domestic'])>{{ $serviceType->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <p class="mb-2 text-xs font-semibold text-ink-700">Weight range</p>
                                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                                    <div>
                                        <label class="mb-1 flex items-center gap-1 text-xs font-medium text-ink-900">Min weight (kg)
                                            <span class="cursor-help text-ink-400" title="The lightest shipment this rate covers.">ⓘ</span>
                                        </label>
                                        <input type="number" step="0.01" min="0" name="min_weight" required class="w-full rounded-md border border-line px-2 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                                    </div>
                                    <div>
                                        <label class="mb-1 flex items-center gap-1 text-xs font-medium text-ink-900">Max weight (kg)
                                            <span class="cursor-help text-ink-400" title="The weight the base zone charge covers up to. Anything heavier bills in 'Increment size' steps at the zone's 'Per increment' rate, up to Max weight limit.">ⓘ</span>
                                        </label>
                                        <input type="number" step="0.01" min="0" name="max_weight" required class="w-full rounded-md border border-line px-2 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                                    </div>
                                    <div>
                                        <label class="mb-1 flex items-center gap-1 text-xs font-medium text-ink-900">Increment size (kg)
                                            <span class="cursor-help text-ink-400" title="How weight above 'Max weight' is billed — e.g. 1kg steps, each charged at the zone's Per increment rate.">ⓘ</span>
                                        </label>
                                        <input type="number" step="0.01" min="0.01" name="additional_weight" value="1" required class="w-full rounded-md border border-line px-2 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                                    </div>
                                    <div>
                                        <label class="mb-1 flex items-center gap-1 text-xs font-medium text-ink-900">Max weight limit (kg)
                                            <span class="cursor-help text-ink-400" title="The heaviest shipment this rate will ever price — nothing above this weight uses this rate at all, even at the overage rate.">ⓘ</span>
                                        </label>
                                        <input type="number" step="0.01" min="0" name="max_weight_limit" required class="w-full rounded-md border border-line px-2 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                                    </div>
                                </div>
                            </div>

                            <div>
                                <p class="mb-2 text-xs font-semibold text-ink-700">Zone pricing</p>
                                <div class="zone-rows-container space-y-2">
                                    <div class="zone-row grid grid-cols-2 gap-2 sm:grid-cols-4">
                                        <select name="zone_prices[0][zone_id]" required title="Which delivery zone this price applies to." class="rounded-md border border-line px-2 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                                            <option value="">Zone…</option>
                                            @foreach ($zones as $zone)
                                                <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                                            @endforeach
                                        </select>
                                        <input type="number" step="0.01" min="0" name="zone_prices[0][charge]" placeholder="Charge" required title="Base price for this zone, up to Max weight." class="rounded-md border border-line px-2 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                                        <input type="number" step="0.01" min="0" name="zone_prices[0][additional_charge]" placeholder="Per increment" title="Price per Increment size step above Max weight, for this zone." class="rounded-md border border-line px-2 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                                        <input type="number" min="0" name="zone_prices[0][transit_days]" placeholder="Transit days" title="Expected delivery days for this zone (optional)." class="rounded-md border border-line px-2 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                                    </div>
                                </div>
                                <button type="button" class="add-zone-row-btn mt-2 text-xs font-medium text-[var(--brand-primary)] hover:underline">+ Add another zone</button>
                            </div>

                            <div class="flex justify-end pt-2">
                                <button type="submit" class="rounded-md bg-[var(--brand-primary)] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 hover:shadow-md">Add special rate</button>
                            </div>
                        </form>
                        @endif

                    @elseif ($modelKey === 'origin_destination_billing')
                        <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-ink-500">Special rates (Origin to Destination)</p>
                        <div class="mb-3 overflow-x-auto rounded-xl border border-line bg-surface-0 shadow-sm">
                            <table class="w-full text-left text-sm">
                                <thead>
                                    <tr class="border-b border-line text-xs uppercase tracking-wide text-ink-500">
                                        <th class="px-5 py-3 font-medium">Service type</th>
                                        <th class="px-5 py-3 font-medium">Origin</th>
                                        <th class="px-5 py-3 font-medium">Destination</th>
                                        <th class="px-5 py-3 font-medium">Weight band</th>
                                        <th class="px-5 py-3 font-medium">Base charge</th>
                                        <th class="px-5 py-3 font-medium">Additional</th>
                                        <th class="px-5 py-3 font-medium">Transit days</th>
                                        <th class="px-5 py-3 font-medium">Status</th>
                                        <th class="px-5 py-3"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($odTariffs as $tariff)
                                        <tr class="border-b border-line last:border-0 odd:bg-surface-0 even:bg-surface-50/50 hover:bg-[var(--brand-primary)]/5 transition-colors">
                                            <td class="px-5 py-3 font-medium text-ink-900">{{ $tariff->serviceType->name }}</td>
                                            <td class="px-5 py-3 text-ink-900">{{ $tariff->originCountry?->name ?? $tariff->originState?->name }}</td>
                                            <td class="px-5 py-3 text-ink-900">{{ $tariff->destinationCountry?->name ?? $tariff->destinationState?->name }}</td>
                                            <td class="px-5 py-3 text-ink-500">
                                                {{ rtrim(rtrim(number_format($tariff->min_weight, 2), '0'), '.') }}–{{ rtrim(rtrim(number_format($tariff->max_weight_limit, 2), '0'), '.') }} kg
                                                @if ((float) $tariff->max_weight !== (float) $tariff->min_weight)
                                                    <br><span class="text-xs">overage from {{ rtrim(rtrim(number_format($tariff->max_weight, 2), '0'), '.') }} kg</span>
                                                @endif
                                            </td>
                                            <td class="px-5 py-3 font-mono text-ink-900">{{ number_format($tariff->base_charge, 2) }}</td>
                                            <td class="px-5 py-3 text-ink-500">
                                                <span class="font-mono">{{ number_format($tariff->additional_charge, 2) }}</span>
                                                <span class="text-xs">/ {{ rtrim(rtrim(number_format($tariff->additional_weight, 2), '0'), '.') }}kg</span>
                                            </td>
                                            <td class="px-5 py-3 text-ink-500">{{ $tariff->transit_days ?? '—' }}</td>
                                            <td class="px-5 py-3">
                                                <span class="inline-flex items-center rounded-full bg-status-delivered/10 px-2.5 py-0.5 text-xs font-medium text-status-delivered">Active</span>
                                            </td>
                                            <td class="px-5 py-3 text-right">
                                                <button type="button" onclick="document.getElementById('edit-od-{{ $tariff->id }}').classList.toggle('hidden')" class="text-sm font-medium text-[var(--brand-primary)] hover:underline">Edit</button>
                                                <form method="POST" action="{{ route('clients.od-tariffs.destroy', [$user, $tariff]) }}" class="inline" onsubmit="return confirm('Remove this special rate?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="ml-3 text-sm font-medium text-status-exception hover:underline">Remove</button>
                                                </form>
                                            </td>
                                        </tr>
                                        <tr id="edit-od-{{ $tariff->id }}" class="hidden border-b border-line last:border-0 bg-surface-50">
                                            <td colspan="9" class="p-4">
                                                <form method="POST" action="{{ route('clients.od-tariffs.update', [$user, $tariff]) }}" class="space-y-2">
                                                    @csrf
                                                    @method('PUT')
                                                    @if ($errors->{'odTariff' . $tariff->id}->any())
                                                        <div class="rounded-md border border-status-exception/30 bg-status-exception/5 p-2 text-xs text-status-exception">
                                                            <ul class="list-disc space-y-0.5 pl-4">
                                                                @foreach ($errors->{'odTariff' . $tariff->id}->all() as $message)
                                                                    <li>{{ $message }}</li>
                                                                @endforeach
                                                            </ul>
                                                        </div>
                                                    @endif
                                                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                                                        <select name="service_type_id" required class="rounded-md border border-line px-2 py-1.5 text-xs outline-none focus:border-[var(--brand-primary)]">
                                                            @foreach ($modelServiceTypes as $serviceType)
                                                                <option value="{{ $serviceType->id }}" @selected($tariff->service_type_id === $serviceType->id)>{{ $serviceType->name }}</option>
                                                            @endforeach
                                                        </select>
                                                        <select name="origin_type" onchange="this.closest('form').querySelector('[name=origin_state_id]').classList.toggle('hidden', this.value==='country'); this.closest('form').querySelector('[name=origin_country_id]').classList.toggle('hidden', this.value==='state');" class="rounded-md border border-line px-2 py-1.5 text-xs outline-none focus:border-[var(--brand-primary)]">
                                                            <option value="state" @selected(! $tariff->origin_country_id)>Origin: State</option>
                                                            <option value="country" @selected($tariff->origin_country_id)>Origin: Country</option>
                                                        </select>
                                                        <select name="origin_state_id" class="rounded-md border border-line px-2 py-1.5 text-xs outline-none focus:border-[var(--brand-primary)] {{ $tariff->origin_country_id ? 'hidden' : '' }}">
                                                            @foreach ($billingStates as $state)
                                                                <option value="{{ $state->id }}" @selected($tariff->origin_state_id === $state->id)>{{ $state->name }}</option>
                                                            @endforeach
                                                        </select>
                                                        <select name="origin_country_id" class="rounded-md border border-line px-2 py-1.5 text-xs outline-none focus:border-[var(--brand-primary)] {{ $tariff->origin_country_id ? '' : 'hidden' }}">
                                                            <option value="">—</option>
                                                            @foreach ($billingCountries as $country)
                                                                <option value="{{ $country->id }}" @selected($tariff->origin_country_id === $country->id)>{{ $country->name }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                                                        <select name="destination_type" onchange="this.closest('form').querySelector('[name=destination_state_id]').classList.toggle('hidden', this.value==='country'); this.closest('form').querySelector('[name=destination_country_id]').classList.toggle('hidden', this.value==='state');" class="rounded-md border border-line px-2 py-1.5 text-xs outline-none focus:border-[var(--brand-primary)]">
                                                            <option value="state" @selected(! $tariff->destination_country_id)>Destination: State</option>
                                                            <option value="country" @selected($tariff->destination_country_id)>Destination: Country</option>
                                                        </select>
                                                        <select name="destination_state_id" class="rounded-md border border-line px-2 py-1.5 text-xs outline-none focus:border-[var(--brand-primary)] {{ $tariff->destination_country_id ? 'hidden' : '' }}">
                                                            @foreach ($billingStates as $state)
                                                                <option value="{{ $state->id }}" @selected($tariff->destination_state_id === $state->id)>{{ $state->name }}</option>
                                                            @endforeach
                                                        </select>
                                                        <select name="destination_country_id" class="rounded-md border border-line px-2 py-1.5 text-xs outline-none focus:border-[var(--brand-primary)] {{ $tariff->destination_country_id ? '' : 'hidden' }}">
                                                            <option value="">—</option>
                                                            @foreach ($billingCountries as $country)
                                                                <option value="{{ $country->id }}" @selected($tariff->destination_country_id === $country->id)>{{ $country->name }}</option>
                                                            @endforeach
                                                        </select>
                                                        <input type="number" min="0" name="transit_days" value="{{ $tariff->transit_days }}" placeholder="Transit days" class="rounded-md border border-line px-2 py-1.5 text-xs outline-none focus:border-[var(--brand-primary)]">
                                                    </div>
                                                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-5">
                                                        <input type="number" step="0.01" min="0" name="min_weight" value="{{ $tariff->min_weight }}" required placeholder="Min weight" class="rounded-md border border-line px-2 py-1.5 text-xs outline-none focus:border-[var(--brand-primary)]">
                                                        <input type="number" step="0.01" min="0" name="max_weight" value="{{ $tariff->max_weight }}" required placeholder="Max weight" class="rounded-md border border-line px-2 py-1.5 text-xs outline-none focus:border-[var(--brand-primary)]">
                                                        <input type="number" step="0.01" min="0" name="max_weight_limit" value="{{ $tariff->max_weight_limit }}" required placeholder="Max weight limit" class="rounded-md border border-line px-2 py-1.5 text-xs outline-none focus:border-[var(--brand-primary)]">
                                                        <input type="number" step="0.01" min="0" name="base_charge" value="{{ $tariff->base_charge }}" required placeholder="Base charge" class="rounded-md border border-line px-2 py-1.5 text-xs outline-none focus:border-[var(--brand-primary)]">
                                                        <input type="number" step="0.01" min="0" name="additional_charge" value="{{ $tariff->additional_charge }}" required placeholder="Per kg after" class="rounded-md border border-line px-2 py-1.5 text-xs outline-none focus:border-[var(--brand-primary)]">
                                                    </div>
                                                    <input type="hidden" name="additional_weight" value="{{ $tariff->additional_weight }}">
                                                    <div class="flex justify-end">
                                                        <button type="submit" class="rounded-md bg-[var(--brand-primary)] px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition hover:opacity-90">Save changes</button>
                                                    </div>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="9" class="px-5 py-8 text-center text-sm text-status-exception">No special rates yet — shipments on any route will be <strong>blocked</strong> until one is added below (Special mode never falls back to the company rate).</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if (true)
                        <div class="flex items-center justify-between border-t border-line pt-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-ink-500">Add a special rate</p>
                            <button type="button" onclick="document.getElementById('import-od-{{ $account->id }}').classList.toggle('hidden')" class="text-xs font-medium text-[var(--brand-primary)] hover:underline">Bulk import (CSV) ▾</button>
                        </div>
                        <div id="import-od-{{ $account->id }}" class="hidden mb-3 rounded-lg border border-line bg-surface-50 p-3">
                            <p class="mb-2 text-xs text-ink-500">One row per route. <a href="{{ route('clients.tariff-template', 'od') }}" class="font-medium text-[var(--brand-primary)] hover:underline">Download a template</a> to see the exact columns.</p>
                            <form method="POST" action="{{ route('clients.od-tariffs.import', [$user, $account]) }}" enctype="multipart/form-data" class="flex items-center gap-2">
                                @csrf
                                <input type="file" name="file" accept=".csv,.txt" required class="block flex-1 text-xs text-ink-900 file:mr-2 file:rounded-md file:border-0 file:bg-[var(--brand-primary)]/10 file:px-3 file:py-1.5 file:text-xs file:font-medium file:text-[var(--brand-primary)]">
                                <button type="submit" class="shrink-0 rounded-md border border-[var(--brand-primary)] px-3 py-1.5 text-xs font-semibold text-[var(--brand-primary)] transition hover:bg-[var(--brand-primary)]/5">Import</button>
                            </form>
                        </div>
                        <form method="POST" action="{{ route('clients.od-tariffs.store', [$user, $account]) }}" class="space-y-4">
                            @csrf
                            @if ($errors->odTariff->any())
                                <div class="rounded-md border border-status-exception/30 bg-status-exception/5 p-3 text-xs text-status-exception">
                                    <ul class="list-disc space-y-0.5 pl-4">
                                        @foreach ($errors->odTariff->all() as $message)
                                            <li>{{ $message }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <div>
                                <p class="mb-2 text-xs font-semibold text-ink-700">Product &amp; route</p>
                                <div class="mb-3">
                                    <label class="mb-1 block text-xs font-medium text-ink-900">Route</label>
                                    <div class="flex gap-2">
                                        <label class="flex cursor-pointer items-center gap-1.5 rounded-md border border-line px-3 py-1.5 text-xs text-ink-900 has-[:checked]:border-[var(--brand-primary)] has-[:checked]:bg-[var(--brand-primary)]/5">
                                            <input type="radio" name="_route_type_od" value="domestic" checked onchange="filterServiceTypesByRoute(this, 'od')">
                                            Domestic
                                        </label>
                                        <label class="flex cursor-pointer items-center gap-1.5 rounded-md border border-line px-3 py-1.5 text-xs text-ink-900 has-[:checked]:border-[var(--brand-primary)] has-[:checked]:bg-[var(--brand-primary)]/5">
                                            <input type="radio" name="_route_type_od" value="international" onchange="filterServiceTypesByRoute(this, 'od')">
                                            International
                                        </label>
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                                    <div>
                                        <label class="mb-1 block text-xs font-medium text-ink-900">Service type</label>
                                        <select name="service_type_id" data-route-select="od" required class="w-full rounded-md border border-line px-2 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                                            <option value="">Select…</option>
                                            @foreach ($modelServiceTypes as $serviceType)
                                                <option value="{{ $serviceType->id }}" data-route-type="{{ $serviceType->route_type }}" @style(['display: none' => $serviceType->route_type !== 'domestic'])>{{ $serviceType->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="mb-1 flex items-center gap-1 text-xs font-medium text-ink-900">Origin
                                            <span class="cursor-help text-ink-400" title="Pick State for a domestic origin, or Country for an international one.">ⓘ</span>
                                        </label>
                                        <select name="origin_type" class="mb-1 w-full rounded-md border border-line px-2 py-1 text-xs outline-none focus:border-[var(--brand-primary)]">
                                            <option value="state">State</option>
                                            <option value="country">Country</option>
                                        </select>
                                        <select name="origin_state_id" class="w-full rounded-md border border-line px-2 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                                            <option value="">Select state…</option>
                                            @foreach ($billingStates as $state)
                                                <option value="{{ $state->id }}">{{ $state->name }}</option>
                                            @endforeach
                                        </select>
                                        <select name="origin_country_id" class="mt-1 hidden w-full rounded-md border border-line px-2 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                                            <option value="">Select country…</option>
                                            @foreach ($billingCountries as $country)
                                                <option value="{{ $country->id }}">{{ $country->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="mb-1 flex items-center gap-1 text-xs font-medium text-ink-900">Destination
                                            <span class="cursor-help text-ink-400" title="Pick State for a domestic destination, or Country for an international one.">ⓘ</span>
                                        </label>
                                        <select name="destination_type" class="mb-1 w-full rounded-md border border-line px-2 py-1 text-xs outline-none focus:border-[var(--brand-primary)]">
                                            <option value="state">State</option>
                                            <option value="country">Country</option>
                                        </select>
                                        <select name="destination_state_id" class="w-full rounded-md border border-line px-2 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                                            <option value="">Select state…</option>
                                            @foreach ($billingStates as $state)
                                                <option value="{{ $state->id }}">{{ $state->name }}</option>
                                            @endforeach
                                        </select>
                                        <select name="destination_country_id" class="mt-1 hidden w-full rounded-md border border-line px-2 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                                            <option value="">Select country…</option>
                                            @foreach ($billingCountries as $country)
                                                <option value="{{ $country->id }}">{{ $country->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="mb-1 flex items-center gap-1 text-xs font-medium text-ink-900">Transit days
                                            <span class="cursor-help text-ink-400" title="Expected delivery time for this route (optional).">ⓘ</span>
                                        </label>
                                        <input type="number" min="0" name="transit_days" class="w-full rounded-md border border-line px-2 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                                    </div>
                                </div>
                            </div>

                            <div>
                                <p class="mb-2 text-xs font-semibold text-ink-700">Weight &amp; pricing</p>
                                <div class="grid grid-cols-2 gap-3 sm:grid-cols-5">
                                    <div>
                                        <label class="mb-1 flex items-center gap-1 text-xs font-medium text-ink-900">Min weight (kg)
                                            <span class="cursor-help text-ink-400" title="The lightest shipment this rate covers.">ⓘ</span>
                                        </label>
                                        <input type="number" step="0.01" min="0" name="min_weight" required class="w-full rounded-md border border-line px-2 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                                    </div>
                                    <div>
                                        <label class="mb-1 flex items-center gap-1 text-xs font-medium text-ink-900">Max weight (kg)
                                            <span class="cursor-help text-ink-400" title="The weight the Base charge covers up to — heavier ships bill Per kg after up to Max weight limit.">ⓘ</span>
                                        </label>
                                        <input type="number" step="0.01" min="0" name="max_weight" required class="w-full rounded-md border border-line px-2 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                                    </div>
                                    <div>
                                        <label class="mb-1 flex items-center gap-1 text-xs font-medium text-ink-900">Max weight limit (kg)
                                            <span class="cursor-help text-ink-400" title="The heaviest shipment this rate will ever price on this route.">ⓘ</span>
                                        </label>
                                        <input type="number" step="0.01" min="0" name="max_weight_limit" required class="w-full rounded-md border border-line px-2 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                                    </div>
                                    <div>
                                        <label class="mb-1 flex items-center gap-1 text-xs font-medium text-ink-900">Base charge
                                            <span class="cursor-help text-ink-400" title="Price for this route up to Max weight.">ⓘ</span>
                                        </label>
                                        <input type="number" step="0.01" min="0" name="base_charge" required class="w-full rounded-md border border-line px-2 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                                    </div>
                                    <div>
                                        <label class="mb-1 flex items-center gap-1 text-xs font-medium text-ink-900">Per kg after
                                            <span class="cursor-help text-ink-400" title="Price per kg above Max weight, up to Max weight limit.">ⓘ</span>
                                        </label>
                                        <input type="number" step="0.01" min="0" name="additional_charge" required class="w-full rounded-md border border-line px-2 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="additional_weight" value="1">
                            <div class="flex justify-end pt-2">
                                <button type="submit" class="rounded-md bg-[var(--brand-primary)] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 hover:shadow-md">Add special rate</button>
                            </div>
                        </form>
                        @endif

                    @elseif ($modelKey === 'fleet_billing')
                        <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-ink-500">Special rates (Fleet)</p>
                        <div class="mb-3 overflow-x-auto rounded-xl border border-line bg-surface-0 shadow-sm">
                            <table class="w-full text-left text-sm">
                                <thead>
                                    <tr class="border-b border-line text-xs uppercase tracking-wide text-ink-500">
                                        <th class="px-5 py-3 font-medium">Service type</th>
                                        <th class="px-5 py-3 font-medium">Vehicle</th>
                                        <th class="px-5 py-3 font-medium">Origin</th>
                                        <th class="px-5 py-3 font-medium">Destination</th>
                                        <th class="px-5 py-3 font-medium">Weight band</th>
                                        <th class="px-5 py-3 font-medium">Weight charge</th>
                                        <th class="px-5 py-3 font-medium">Fuel %</th>
                                        <th class="px-5 py-3 font-medium">Status</th>
                                        <th class="px-5 py-3"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($fleetTariffs as $tariff)
                                        <tr class="border-b border-line last:border-0 odd:bg-surface-0 even:bg-surface-50/50 hover:bg-[var(--brand-primary)]/5 transition-colors">
                                            <td class="px-5 py-3 font-medium text-ink-900">{{ $tariff->serviceType->name }}</td>
                                            <td class="px-5 py-3 text-ink-900">{{ $tariff->vehicleType->name }}</td>
                                            <td class="px-5 py-3 text-ink-900">{{ $tariff->originCountry?->name ?? $tariff->originState?->name }}</td>
                                            <td class="px-5 py-3 text-ink-900">{{ $tariff->destinationCountry?->name ?? $tariff->destinationState?->name }}</td>
                                            <td class="px-5 py-3 text-ink-500">
                                                {{ rtrim(rtrim(number_format($tariff->min_weight, 2), '0'), '.') }}–{{ rtrim(rtrim(number_format($tariff->max_weight_limit, 2), '0'), '.') }} kg
                                                @if ((float) $tariff->max_weight !== (float) $tariff->min_weight)
                                                    <br><span class="text-xs">overage from {{ rtrim(rtrim(number_format($tariff->max_weight, 2), '0'), '.') }} kg</span>
                                                @endif
                                            </td>
                                            <td class="px-5 py-3 text-ink-500">
                                                <span class="font-mono text-ink-900">{{ number_format($tariff->base_charge, 2) }}</span> base
                                                + <span class="font-mono">{{ number_format($tariff->additional_charge, 2) }}</span>/{{ rtrim(rtrim(number_format($tariff->additional_weight, 2), '0'), '.') }}kg
                                            </td>
                                            <td class="px-5 py-3 text-ink-500">{{ rtrim(rtrim(number_format($tariff->fuel_surcharge_percentage, 2), '0'), '.') }}%</td>
                                            <td class="px-5 py-3">
                                                <span class="inline-flex items-center rounded-full bg-status-delivered/10 px-2.5 py-0.5 text-xs font-medium text-status-delivered">Active</span>
                                            </td>
                                            <td class="px-5 py-3 text-right">
                                                <button type="button" onclick="document.getElementById('edit-fleet-{{ $tariff->id }}').classList.toggle('hidden')" class="text-sm font-medium text-[var(--brand-primary)] hover:underline">Edit</button>
                                                <form method="POST" action="{{ route('clients.fleet-tariffs.destroy', [$user, $tariff]) }}" class="inline" onsubmit="return confirm('Remove this special rate?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="ml-3 text-sm font-medium text-status-exception hover:underline">Remove</button>
                                                </form>
                                            </td>
                                        </tr>
                                        <tr id="edit-fleet-{{ $tariff->id }}" class="hidden border-b border-line last:border-0 bg-surface-50">
                                            <td colspan="9" class="p-4">
                                                <form method="POST" action="{{ route('clients.fleet-tariffs.update', [$user, $tariff]) }}" class="space-y-2">
                                                    @csrf
                                                    @method('PUT')
                                                    @if ($errors->{'fleetTariff' . $tariff->id}->any())
                                                        <div class="rounded-md border border-status-exception/30 bg-status-exception/5 p-2 text-xs text-status-exception">
                                                            <ul class="list-disc space-y-0.5 pl-4">
                                                                @foreach ($errors->{'fleetTariff' . $tariff->id}->all() as $message)
                                                                    <li>{{ $message }}</li>
                                                                @endforeach
                                                            </ul>
                                                        </div>
                                                    @endif
                                                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                                                        <select name="service_type_id" required class="rounded-md border border-line px-2 py-1.5 text-xs outline-none focus:border-[var(--brand-primary)]">
                                                            @foreach ($modelServiceTypes as $serviceType)
                                                                <option value="{{ $serviceType->id }}" @selected($tariff->service_type_id === $serviceType->id)>{{ $serviceType->name }}</option>
                                                            @endforeach
                                                        </select>
                                                        <select name="vehicle_type_id" required class="rounded-md border border-line px-2 py-1.5 text-xs outline-none focus:border-[var(--brand-primary)]">
                                                            @foreach ($vehicleTypes as $vehicleType)
                                                                <option value="{{ $vehicleType->id }}" @selected($tariff->vehicle_type_id === $vehicleType->id)>{{ $vehicleType->name }}</option>
                                                            @endforeach
                                                        </select>
                                                        <select name="origin_type" onchange="this.closest('form').querySelector('[name=origin_state_id]').classList.toggle('hidden', this.value==='country'); this.closest('form').querySelector('[name=origin_country_id]').classList.toggle('hidden', this.value==='state');" class="rounded-md border border-line px-2 py-1.5 text-xs outline-none focus:border-[var(--brand-primary)]">
                                                            <option value="state" @selected(! $tariff->origin_country_id)>Origin: State</option>
                                                            <option value="country" @selected($tariff->origin_country_id)>Origin: Country</option>
                                                        </select>
                                                        <select name="destination_type" onchange="this.closest('form').querySelector('[name=destination_state_id]').classList.toggle('hidden', this.value==='country'); this.closest('form').querySelector('[name=destination_country_id]').classList.toggle('hidden', this.value==='state');" class="rounded-md border border-line px-2 py-1.5 text-xs outline-none focus:border-[var(--brand-primary)]">
                                                            <option value="state" @selected(! $tariff->destination_country_id)>Destination: State</option>
                                                            <option value="country" @selected($tariff->destination_country_id)>Destination: Country</option>
                                                        </select>
                                                    </div>
                                                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                                                        <select name="origin_state_id" class="rounded-md border border-line px-2 py-1.5 text-xs outline-none focus:border-[var(--brand-primary)] {{ $tariff->origin_country_id ? 'hidden' : '' }}">
                                                            @foreach ($billingStates as $state)
                                                                <option value="{{ $state->id }}" @selected($tariff->origin_state_id === $state->id)>{{ $state->name }}</option>
                                                            @endforeach
                                                        </select>
                                                        <select name="origin_country_id" class="rounded-md border border-line px-2 py-1.5 text-xs outline-none focus:border-[var(--brand-primary)] {{ $tariff->origin_country_id ? '' : 'hidden' }}">
                                                            <option value="">—</option>
                                                            @foreach ($billingCountries as $country)
                                                                <option value="{{ $country->id }}" @selected($tariff->origin_country_id === $country->id)>{{ $country->name }}</option>
                                                            @endforeach
                                                        </select>
                                                        <select name="destination_state_id" class="rounded-md border border-line px-2 py-1.5 text-xs outline-none focus:border-[var(--brand-primary)] {{ $tariff->destination_country_id ? 'hidden' : '' }}">
                                                            @foreach ($billingStates as $state)
                                                                <option value="{{ $state->id }}" @selected($tariff->destination_state_id === $state->id)>{{ $state->name }}</option>
                                                            @endforeach
                                                        </select>
                                                        <select name="destination_country_id" class="rounded-md border border-line px-2 py-1.5 text-xs outline-none focus:border-[var(--brand-primary)] {{ $tariff->destination_country_id ? '' : 'hidden' }}">
                                                            <option value="">—</option>
                                                            @foreach ($billingCountries as $country)
                                                                <option value="{{ $country->id }}" @selected($tariff->destination_country_id === $country->id)>{{ $country->name }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                                                        <input type="number" step="0.01" min="0" name="min_weight" value="{{ $tariff->min_weight }}" required placeholder="Min weight" class="rounded-md border border-line px-2 py-1.5 text-xs outline-none focus:border-[var(--brand-primary)]">
                                                        <input type="number" step="0.01" min="0" name="max_weight" value="{{ $tariff->max_weight }}" required placeholder="Max weight" class="rounded-md border border-line px-2 py-1.5 text-xs outline-none focus:border-[var(--brand-primary)]">
                                                        <input type="number" step="0.01" min="0" name="max_weight_limit" value="{{ $tariff->max_weight_limit }}" required placeholder="Max weight limit" class="rounded-md border border-line px-2 py-1.5 text-xs outline-none focus:border-[var(--brand-primary)]">
                                                        <input type="number" step="0.01" min="0" name="base_charge" value="{{ $tariff->base_charge }}" required placeholder="Base charge" class="rounded-md border border-line px-2 py-1.5 text-xs outline-none focus:border-[var(--brand-primary)]">
                                                    </div>
                                                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                                                        <input type="number" step="0.01" min="0" name="additional_charge" value="{{ $tariff->additional_charge }}" required placeholder="Per kg after" class="rounded-md border border-line px-2 py-1.5 text-xs outline-none focus:border-[var(--brand-primary)]">
                                                        <input type="number" step="0.01" min="0" max="100" name="fuel_surcharge_percentage" value="{{ $tariff->fuel_surcharge_percentage }}" required placeholder="Fuel surcharge %" class="rounded-md border border-line px-2 py-1.5 text-xs outline-none focus:border-[var(--brand-primary)]">
                                                        <select name="empty_return_charge_type" class="rounded-md border border-line px-2 py-1.5 text-xs outline-none focus:border-[var(--brand-primary)]">
                                                            <option value="flat" @selected($tariff->empty_return_charge_type === 'flat')>Flat</option>
                                                            <option value="percentage" @selected($tariff->empty_return_charge_type === 'percentage')>% of freight</option>
                                                        </select>
                                                        <input type="number" step="0.01" min="0" name="empty_return_charge_value" value="{{ $tariff->empty_return_charge_value }}" required placeholder="Empty return value" class="rounded-md border border-line px-2 py-1.5 text-xs outline-none focus:border-[var(--brand-primary)]">
                                                    </div>
                                                    <input type="number" min="0" name="transit_days" value="{{ $tariff->transit_days }}" placeholder="Transit days" class="w-32 rounded-md border border-line px-2 py-1.5 text-xs outline-none focus:border-[var(--brand-primary)]">
                                                    <input type="hidden" name="additional_weight" value="{{ $tariff->additional_weight }}">
                                                    <div class="flex justify-end">
                                                        <button type="submit" class="rounded-md bg-[var(--brand-primary)] px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition hover:opacity-90">Save changes</button>
                                                    </div>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="9" class="px-5 py-8 text-center text-sm text-status-exception">No special rates yet — shipments on any vehicle type/route will be <strong>blocked</strong> until one is added below (Special mode never falls back to the company rate).</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if (true)
                        <div class="flex items-center justify-between border-t border-line pt-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-ink-500">Add a special rate</p>
                            <button type="button" onclick="document.getElementById('import-fleet-{{ $account->id }}').classList.toggle('hidden')" class="text-xs font-medium text-[var(--brand-primary)] hover:underline">Bulk import (CSV) ▾</button>
                        </div>
                        <div id="import-fleet-{{ $account->id }}" class="hidden mb-3 rounded-lg border border-line bg-surface-50 p-3">
                            <p class="mb-2 text-xs text-ink-500">One row per vehicle type/route. <a href="{{ route('clients.tariff-template', 'fleet') }}" class="font-medium text-[var(--brand-primary)] hover:underline">Download a template</a> to see the exact columns.</p>
                            <form method="POST" action="{{ route('clients.fleet-tariffs.import', [$user, $account]) }}" enctype="multipart/form-data" class="flex items-center gap-2">
                                @csrf
                                <input type="file" name="file" accept=".csv,.txt" required class="block flex-1 text-xs text-ink-900 file:mr-2 file:rounded-md file:border-0 file:bg-[var(--brand-primary)]/10 file:px-3 file:py-1.5 file:text-xs file:font-medium file:text-[var(--brand-primary)]">
                                <button type="submit" class="shrink-0 rounded-md border border-[var(--brand-primary)] px-3 py-1.5 text-xs font-semibold text-[var(--brand-primary)] transition hover:bg-[var(--brand-primary)]/5">Import</button>
                            </form>
                        </div>
                        <form method="POST" action="{{ route('clients.fleet-tariffs.store', [$user, $account]) }}" class="space-y-4">
                            @csrf
                            @if ($errors->fleetTariff->any())
                                <div class="rounded-md border border-status-exception/30 bg-status-exception/5 p-3 text-xs text-status-exception">
                                    <ul class="list-disc space-y-0.5 pl-4">
                                        @foreach ($errors->fleetTariff->all() as $message)
                                            <li>{{ $message }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <div>
                                <p class="mb-2 text-xs font-semibold text-ink-700">Product, vehicle &amp; route</p>
                                <div class="mb-3">
                                    <label class="mb-1 block text-xs font-medium text-ink-900">Route</label>
                                    <div class="flex gap-2">
                                        <label class="flex cursor-pointer items-center gap-1.5 rounded-md border border-line px-3 py-1.5 text-xs text-ink-900 has-[:checked]:border-[var(--brand-primary)] has-[:checked]:bg-[var(--brand-primary)]/5">
                                            <input type="radio" name="_route_type_fleet" value="domestic" checked onchange="filterServiceTypesByRoute(this, 'fleet'); toggleFleetRouteFields(this)">
                                            Domestic
                                        </label>
                                        <label class="flex cursor-pointer items-center gap-1.5 rounded-md border border-line px-3 py-1.5 text-xs text-ink-900 has-[:checked]:border-[var(--brand-primary)] has-[:checked]:bg-[var(--brand-primary)]/5">
                                            <input type="radio" name="_route_type_fleet" value="international" onchange="filterServiceTypesByRoute(this, 'fleet'); toggleFleetRouteFields(this)">
                                            International
                                        </label>
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                                    <div>
                                        <label class="mb-1 block text-xs font-medium text-ink-900">Service type</label>
                                        <select name="service_type_id" data-route-select="fleet" required class="w-full rounded-md border border-line px-2 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                                            <option value="">Select…</option>
                                            @foreach ($modelServiceTypes as $serviceType)
                                                <option value="{{ $serviceType->id }}" data-route-type="{{ $serviceType->route_type }}" @style(['display: none' => $serviceType->route_type !== 'domestic'])>{{ $serviceType->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="mb-1 flex items-center gap-1 text-xs font-medium text-ink-900">Vehicle type
                                            <span class="cursor-help text-ink-400" title="Which vehicle this rate applies to — a bike rate and a truck rate for the same route are two separate special rates.">ⓘ</span>
                                        </label>
                                        <select name="vehicle_type_id" required class="w-full rounded-md border border-line px-2 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                                            <option value="">Select…</option>
                                            @foreach ($vehicleTypes as $vehicleType)
                                                <option value="{{ $vehicleType->id }}">{{ $vehicleType->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="mb-1 flex items-center gap-1 text-xs font-medium text-ink-900">Origin
                                            <span class="cursor-help text-ink-400" title="State for a domestic route. Switch Route above to International to pick a country instead.">ⓘ</span>
                                        </label>
                                        <select name="origin_type" data-fleet-route-type-select onchange="this.closest('form').querySelector('[name=origin_state_id]').classList.toggle('hidden', this.value==='country'); this.closest('form').querySelector('[name=origin_country_id]').classList.toggle('hidden', this.value==='state');" class="mb-1 hidden w-full rounded-md border border-line px-2 py-1 text-xs outline-none focus:border-[var(--brand-primary)]">
                                            <option value="state">State</option>
                                            <option value="country">Country</option>
                                        </select>
                                        <select name="origin_state_id" required class="w-full rounded-md border border-line px-2 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                                            <option value="">Select state…</option>
                                            @foreach ($billingStates as $state)
                                                <option value="{{ $state->id }}">{{ $state->name }}</option>
                                            @endforeach
                                        </select>
                                        <select name="origin_country_id" class="mt-1 hidden w-full rounded-md border border-line px-2 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                                            <option value="">Select country…</option>
                                            @foreach ($billingCountries as $country)
                                                <option value="{{ $country->id }}">{{ $country->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="mb-1 flex items-center gap-1 text-xs font-medium text-ink-900">Destination
                                            <span class="cursor-help text-ink-400" title="State for a domestic route. Switch Route above to International to pick a country instead.">ⓘ</span>
                                        </label>
                                        <select name="destination_type" data-fleet-route-type-select onchange="this.closest('form').querySelector('[name=destination_state_id]').classList.toggle('hidden', this.value==='country'); this.closest('form').querySelector('[name=destination_country_id]').classList.toggle('hidden', this.value==='state');" class="mb-1 hidden w-full rounded-md border border-line px-2 py-1 text-xs outline-none focus:border-[var(--brand-primary)]">
                                            <option value="state">State</option>
                                            <option value="country">Country</option>
                                        </select>
                                        <select name="destination_state_id" required class="w-full rounded-md border border-line px-2 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                                            <option value="">Select state…</option>
                                            @foreach ($billingStates as $state)
                                                <option value="{{ $state->id }}">{{ $state->name }}</option>
                                            @endforeach
                                        </select>
                                        <select name="destination_country_id" class="mt-1 hidden w-full rounded-md border border-line px-2 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                                            <option value="">Select country…</option>
                                            @foreach ($billingCountries as $country)
                                                <option value="{{ $country->id }}">{{ $country->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <p class="mb-2 text-xs font-semibold text-ink-700">Weight &amp; base pricing</p>
                                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                                    <div>
                                        <label class="mb-1 flex items-center gap-1 text-xs font-medium text-ink-900">Min weight (kg)
                                            <span class="cursor-help text-ink-400" title="The lightest shipment this rate covers.">ⓘ</span>
                                        </label>
                                        <input type="number" step="0.01" min="0" name="min_weight" required class="w-full rounded-md border border-line px-2 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                                    </div>
                                    <div>
                                        <label class="mb-1 flex items-center gap-1 text-xs font-medium text-ink-900">Max weight (kg)
                                            <span class="cursor-help text-ink-400" title="The weight the Base charge covers up to — heavier ships bill Per kg after up to Max weight limit.">ⓘ</span>
                                        </label>
                                        <input type="number" step="0.01" min="0" name="max_weight" required class="w-full rounded-md border border-line px-2 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                                    </div>
                                    <div>
                                        <label class="mb-1 flex items-center gap-1 text-xs font-medium text-ink-900">Max weight limit (kg)
                                            <span class="cursor-help text-ink-400" title="The heaviest shipment this rate will ever price — should not exceed the vehicle's own real capacity.">ⓘ</span>
                                        </label>
                                        <input type="number" step="0.01" min="0" name="max_weight_limit" required class="w-full rounded-md border border-line px-2 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                                    </div>
                                    <div>
                                        <label class="mb-1 flex items-center gap-1 text-xs font-medium text-ink-900">Base charge
                                            <span class="cursor-help text-ink-400" title="Price for this vehicle/route up to Max weight.">ⓘ</span>
                                        </label>
                                        <input type="number" step="0.01" min="0" name="base_charge" required class="w-full rounded-md border border-line px-2 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                                    </div>
                                </div>
                            </div>

                            <div>
                                <p class="mb-2 text-xs font-semibold text-ink-700">Surcharges</p>
                                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                                    <div>
                                        <label class="mb-1 flex items-center gap-1 text-xs font-medium text-ink-900">Per kg after
                                            <span class="cursor-help text-ink-400" title="Price per kg above Max weight, up to Max weight limit.">ⓘ</span>
                                        </label>
                                        <input type="number" step="0.01" min="0" name="additional_charge" required class="w-full rounded-md border border-line px-2 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                                    </div>
                                    <div>
                                        <label class="mb-1 flex items-center gap-1 text-xs font-medium text-ink-900">Fuel surcharge %
                                            <span class="cursor-help text-ink-400" title="A percentage added on top of the freight amount to cover fuel — 0 if not applicable.">ⓘ</span>
                                        </label>
                                        <input type="number" step="0.01" min="0" max="100" name="fuel_surcharge_percentage" value="0" required class="w-full rounded-md border border-line px-2 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                                    </div>
                                    <div>
                                        <label class="mb-1 flex items-center gap-1 text-xs font-medium text-ink-900">Empty return
                                            <span class="cursor-help text-ink-400" title="How the vehicle's return trip is charged, when applicable — a flat amount, or a percentage of the freight.">ⓘ</span>
                                        </label>
                                        <select name="empty_return_charge_type" class="w-full rounded-md border border-line px-2 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                                            <option value="flat">Flat</option>
                                            <option value="percentage">% of freight</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="mb-1 flex items-center gap-1 text-xs font-medium text-ink-900">Empty return value
                                            <span class="cursor-help text-ink-400" title="The flat amount, or the percentage, matching what's chosen in Empty return above — 0 if not applicable.">ⓘ</span>
                                        </label>
                                        <input type="number" step="0.01" min="0" name="empty_return_charge_value" value="0" required class="w-full rounded-md border border-line px-2 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="additional_weight" value="1">
                            <div class="flex justify-end pt-2">
                                <button type="submit" class="rounded-md bg-[var(--brand-primary)] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 hover:shadow-md">Add special rate</button>
                            </div>
                        </form>
                        @endif
                    @endif
                    @endif
                @else
                    <p class="text-sm text-ink-500">This account isn't set up to use {{ $modelLabel }} — enable it above to configure service access or special rates.</p>
                @endif
            </div>
            @endif
        @endforeach
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
                    <span class="cursor-help text-ink-400" title="Lets this client store goods at a company warehouse ahead of dispatch, rather than every shipment being picked up or dropped off fresh.">ⓘ</span>
                </label>
                <label class="flex cursor-pointer items-center gap-2 text-sm text-ink-900">
                    <input type="checkbox" name="cod_enabled" value="1" @checked($profile?->cod_enabled) class="rounded border-line">
                    Cash on delivery enabled
                    <span class="cursor-help text-ink-400" title="Lets this client's shipments collect payment from the recipient at the point of delivery, instead of always being prepaid.">ⓘ</span>
                </label>
            </div>

            <div class="mb-4 border-t border-line pt-4">
                <label class="flex cursor-pointer items-center gap-2 text-sm text-ink-900">
                    <input type="checkbox" name="insurance_agreement" value="1" @checked($profile?->insurance_agreement) class="rounded border-line">
                    Insurance agreement in place
                    <span class="cursor-help text-ink-400" title="A separate commercial agreement covering loss/damage liability for this client's shipments — not the same as declared-value insurance on an individual shipment.">ⓘ</span>
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
                <p class="mb-2 text-sm font-semibold text-ink-900">SLA</p>
                <p class="mb-2 text-xs text-ink-500">Invoice terms moved to the Accounts tab — each account can now have its own, alongside its other billing/invoicing details.</p>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 flex items-center gap-1 text-xs font-medium text-ink-900">SLA: pickup within (hours)
                            <span class="cursor-help text-ink-400" title="How quickly a pickup request from this client should be actioned, in hours — an internal service target, not a customer-facing guarantee shown on a waybill.">ⓘ</span>
                        </label>
                        <input type="number" min="0" name="sla_pickup_hours" value="{{ $profile?->sla_pickup_hours }}" class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                    </div>
                    <div>
                        <label class="mb-1 flex items-center gap-1 text-xs font-medium text-ink-900">SLA: delivery within (days)
                            <span class="cursor-help text-ink-400" title="Expected delivery turnaround for this client's shipments, in days — an internal service target, separate from any per-shipment transit-day estimate.">ⓘ</span>
                        </label>
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

        </div>
    </div>

    <script>
        // Each origin_type/destination_type select shares a FORM (not
        // necessarily an immediate parent div — the inline "Edit"
        // forms group type/state/country selects across two grid rows
        // rather than one) with its matching state/country selects.
        // Scoped per-select since the page can have several such pairs
        // (the main Add forms, plus one inline edit form per existing
        // rate) with the same field names in different forms. Guards
        // against a missing sibling rather than assuming the structure
        // always matches, since a future form shape change shouldn't
        // be able to throw here.
        (function () {
            document.querySelectorAll('select[name="origin_type"], select[name="destination_type"]').forEach(function (typeSelect) {
                const prefix = typeSelect.name === 'origin_type' ? 'origin' : 'destination';
                const scope = typeSelect.closest('form') || typeSelect.closest('div');
                const stateSelect = scope?.querySelector('select[name="' + prefix + '_state_id"]');
                const countrySelect = scope?.querySelector('select[name="' + prefix + '_country_id"]');
                if (!stateSelect || !countrySelect) return;

                function sync() {
                    const isCountry = typeSelect.value === 'country';
                    stateSelect.classList.toggle('hidden', isCountry);
                    countrySelect.classList.toggle('hidden', !isCountry);
                }

                typeSelect.addEventListener('change', sync);
                sync();
            });
        })();

        function showClientTab(tab) {
            document.querySelectorAll('[id^="tab-"]').forEach(function (el) {
                el.style.display = el.id === 'tab-' + tab ? '' : 'none';
            });
            document.querySelectorAll('[id^="side-nav-"]').forEach(function (btn) {
                const active = btn.id === 'side-nav-' + tab;
                btn.classList.toggle('bg-[var(--brand-primary)]/10', active);
                btn.classList.toggle('text-[var(--brand-primary)]', active);
                btn.classList.toggle('text-ink-500', !active);
            });
        }

        function showBillingModelTab(model) {
            document.querySelectorAll('[id^="billing-model-"]:not([id^="billing-model-nav-"])').forEach(function (el) {
                el.style.display = el.id === 'billing-model-' + model ? '' : 'none';
            });
            document.querySelectorAll('[id^="billing-model-nav-"]').forEach(function (btn) {
                if (btn.disabled) return;
                const active = btn.id === 'billing-model-nav-' + model;
                btn.classList.toggle('bg-[var(--brand-primary)]/10', active);
                btn.classList.toggle('text-[var(--brand-primary)]', active);
                btn.classList.toggle('text-ink-500', !active);
            });
        }

        /**
         * Same Domestic/International separation Rate Checker already
         * uses for its Service Type list — each Add-a-special-rate form
         * is already scoped to one billing model (via the sub-tabs
         * above), so this only needs to filter by route_type, not also
         * by billing model the way Rate Checker's version does. $key
         * ('standard'/'od'/'fleet') scopes this to the one select
         * sharing that key, since the page can have all three forms —
         * each with their own independent Route toggle — at once.
         */
        function filterServiceTypesByRoute(radio, key) {
            const form = radio.closest('form');
            const select = form?.querySelector('select[data-route-select="' + key + '"]');
            if (!select) return;

            select.querySelectorAll('option').forEach(function (option) {
                if (!option.value) return;
                option.style.display = option.dataset.routeType === radio.value ? '' : 'none';
            });

            if (select.selectedOptions[0]?.style.display === 'none') {
                select.value = '';
            }
        }

        /**
         * Fleet vehicles only cross a border on an International route
         * — for Domestic, Origin/Destination are always a State, with
         * no State-vs-Country choice shown at all (Country wouldn't
         * mean anything there). Switching to International reveals
         * the same type toggle Origin-to-Destination already offers,
         * defaulting back to State until the person picks Country.
         * Switching back to Domestic forces both back to State and
         * re-hides the toggle and any country dropdown, so a stale
         * country selection can never be submitted alongside a
         * Domestic route.
         */
        function toggleFleetRouteFields(radio) {
            const form = radio.closest('form');
            if (!form) return;
            const isInternational = radio.value === 'international';

            form.querySelectorAll('[data-fleet-route-type-select]').forEach(function (typeSelect) {
                typeSelect.classList.toggle('hidden', !isInternational);

                if (!isInternational) {
                    typeSelect.value = 'state';
                    typeSelect.dispatchEvent(new Event('change'));
                }
            });
        }

        // Generic — every "Add another zone" button on the page (the
        // main Add form, plus one inline edit form per existing
        // tariff) finds its own sibling zone-rows container and starts
        // its own independent index count, so several of these can
        // coexist on one page without stepping on each other's row
        // names.
        document.querySelectorAll('.add-zone-row-btn').forEach(function (addBtn) {
            const container = addBtn.previousElementSibling;
            let index = container.querySelectorAll('.zone-row').length;

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
        });

        // Reopens whichever tab a save was made from (see
        // ClientController::redirectToTab()) instead of always
        // defaulting to Overview — falls back safely if the requested
        // tab doesn't exist for this account (e.g. an Individual
        // account has no Department/User tabs).
        (function () {
            const requested = @json($activeTab);
            const target = document.getElementById('tab-' + requested) ? requested : 'overview';
            showClientTab(target);
        })();

        // Opens the first billing model this account is actually
        // enabled for — every model starts hidden (display:none) so
        // there's no flash of an unavailable one before JS runs.
        (function () {
            const firstEnabled = @json($firstEnabledBillingModel);
            if (firstEnabled) {
                showBillingModelTab(firstEnabled);
            }
        })();
    </script>

</x-layouts.app>
