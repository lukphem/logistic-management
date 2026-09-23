<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Dashboard' }} — {{ config('branding.company_name') }}</title>

    {{-- Per-deployment brand colors — set once here, referenced everywhere
         else via var(--brand-primary) / var(--brand-secondary). This is the
         whole theming mechanism: one shared codebase, one CSS variable
         swap per client install. --}}
    <style>
        :root {
            --brand-primary: {{ config('branding.colors.primary') }};
            --brand-secondary: {{ config('branding.colors.secondary') }};
        }
        details > summary { list-style: none; }
        details > summary::-webkit-details-marker { display: none; }
    </style>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-surface-50 text-ink-900 antialiased">
    <div class="flex h-full">

        {{-- Backdrop — mobile only, closes the drawer on tap --}}
        <div id="sidebar-backdrop" class="fixed inset-0 z-30 hidden bg-black/40 md:hidden"></div>

        {{-- Sidebar — off-canvas drawer on mobile, static column on md+ --}}
        <aside id="sidebar"
               class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full shrink-0 flex-col bg-[var(--brand-primary)] bg-gradient-to-b from-white/0 to-black/10 text-white transition-transform duration-200 md:relative md:inset-auto md:translate-x-0">
            <div class="flex h-16 items-center gap-2.5 border-b border-white/10 px-5">
                @if (config('branding.logo_url'))
                    <img src="{{ config('branding.logo_url') }}" alt="{{ config('branding.company_name') }}" class="h-8 w-8 rounded-md object-cover ring-1 ring-white/20">
                @else
                    <span class="grid h-8 w-8 place-items-center rounded-md bg-[var(--brand-secondary)] font-mono text-sm font-bold text-ink-900">
                        {{ strtoupper(substr(config('branding.company_name'), 0, 2)) }}
                    </span>
                @endif
                <span class="truncate text-sm font-semibold tracking-wide">{{ config('branding.company_name') }}</span>
                <button id="sidebar-close" type="button" class="ml-auto text-white/70 hover:text-white md:hidden">
                    <x-icon name="close" class="h-5 w-5" />
                </button>
            </div>

            <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4">
                @php
                    $navItems = [
                        ['label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'dashboard', 'permission' => null],
                        ['label' => 'Tracking', 'route' => 'staff-tracking.search', 'icon' => 'search', 'permission' => null],
                        ['label' => 'Print Documents', 'route' => 'print-documents.search', 'icon' => 'box', 'permission' => 'manifests:read'],
                        ['label' => 'Rate Checker', 'route' => 'rate-checker.index', 'icon' => 'search', 'permission' => 'billing:read'],
                    ];

                    // Shipping — the one place both ways of creating a
                    // shipment live, plus the unified record of every
                    // shipment either one has ever produced. Matches
                    // how UPS/FedEx/DHL group this: a single "Shipping"
                    // entry point with Create/Batch/History underneath,
                    // rather than the create paths and the history of
                    // what they produced being scattered as separate,
                    // unrelated top-level items.
                    $shippingItems = [
                        ['label' => 'Create Shipment', 'route' => 'shipments.create', 'icon' => 'box', 'permission' => 'shipments:create'],
                        ['label' => 'Bulk Upload', 'route' => 'shipments.bulk.index', 'icon' => 'box', 'permission' => 'shipments:create'],
                        ['label' => 'Shipment History', 'route' => 'shipments.index', 'icon' => 'list-check', 'permission' => null],
                    ];

                    // Payments — Reconciliation (what's outstanding right
                    // now, actionable) and Payment Reports (the full
                    // paid/unpaid history) are different views of the
                    // same underlying money, so they sit together as one
                    // group rather than as two unrelated flat items.
                    $paymentItems = [
                        ['label' => 'Reconciliation', 'route' => 'reconciliation.index', 'icon' => 'wallet', 'permission' => null],
                        ['label' => 'Payment Reports', 'route' => 'payment-reports.index', 'icon' => 'list-check', 'permission' => 'payments:read'],
                    ];

                    // Operational Scans — five single-purpose scan
                    // tools, each its own link with the operation
                    // already locked in rather than one page with a
                    // dropdown to pick it. All five share the same
                    // route name (operational-scans.index) with a
                    // Operations — every individual scan tool plus
                    // Manifest Trips (batch movement) live together
                    // here, since they're all different ways of
                    // moving/tracking shipments day to day. Manifest
                    // Trips uses its own route name with no
                    // parameter ('route'), the six scan tools all
                    // share operational-scans.index with a different
                    // {type} each ('type') — the render loop below
                    // branches on which key is present.
                    $scanItems = [
                        ['label' => 'Manifest Trips', 'route' => 'manifest-trips.index', 'icon' => 'route', 'permission' => 'manifests:read'],
                        ['label' => 'Pickup Scan', 'type' => 'pickup', 'icon' => 'box', 'permission' => 'pickup-scan:update'],
                        ['label' => 'Drop-off Scan', 'type' => 'dropoff', 'icon' => 'box', 'permission' => 'dropoff-scan:update'],
                        ['label' => 'Arrival Scan', 'type' => 'arrival', 'icon' => 'route', 'permission' => 'arrival-scan:update'],
                        ['label' => 'Departure Scan', 'type' => 'departure', 'icon' => 'route', 'permission' => 'departure-scan:update'],
                        ['label' => 'Delivery Scan', 'route' => 'operational-scans.delivery.index', 'icon' => 'box', 'permission' => 'delivery-scan:update'],
                        ['label' => 'Exception Scan', 'type' => 'exception', 'icon' => 'list-check', 'permission' => 'exception-scan:update'],
                    ];

                    // Billing setup — nested inside Setups alongside
                    // Location, same pattern: a collapsible submenu rather
                    // than a flat list of six items.
                    $billingItems = [
                        ['label' => 'Service Types', 'route' => 'service-types.index', 'icon' => 'list-check', 'permission' => 'billing:read'],
                        ['label' => 'Vehicle Types', 'route' => 'vehicle-types.index', 'icon' => 'list-check', 'permission' => 'billing:read'],
                        ['label' => 'Zones', 'route' => 'zones.index', 'icon' => 'layers', 'permission' => 'locations:read'],
                        ['label' => 'Onforwarding', 'route' => 'onforwarding-classifications.index', 'icon' => 'list-check', 'permission' => 'billing:read'],
                        ['label' => 'Zone Mapping', 'route' => 'zone-mappings.index', 'icon' => 'layers', 'permission' => 'rates:read'],
                    ];

                    $billingItemsAfterStandard = [
                        ['label' => 'Additional Services', 'route' => 'additional-services.index', 'icon' => 'list-check', 'permission' => 'billing:read'],
                        ['label' => 'Invoice', 'route' => 'invoices.index', 'icon' => 'list-check', 'permission' => 'billing:read'],
                    ];

                    // Standard Billing is itself a submenu now, not a
                    // single link — every billing MODEL (Zoning and
                    // Weight, Origin to Destination, and whatever comes
                    // next) lives here as a tab on one shared page
                    // (standard-billing.index), switched via ?model=,
                    // rather than getting its own top-level page. Fleet
                    // Billing isn't built yet — kept visible so the
                    // eventual shape is already right, shown disabled
                    // until it actually exists.
                    $standardBillingItems = [
                        ['label' => 'Zoning and Weight', 'route' => 'standard-billing.index', 'model' => 'standard', 'icon' => 'sliders', 'permission' => 'billing:read'],
                        ['label' => 'Origin to Destination', 'route' => 'standard-billing.index', 'model' => 'origin-destination', 'icon' => 'route', 'permission' => 'billing:read'],
                        ['label' => 'Fleet Billing', 'route' => 'standard-billing.index', 'model' => 'fleet', 'icon' => 'sliders', 'permission' => 'billing:read'],
                    ];

                    // Ordered by setup dependency. Location-related screens
                    // (geography down to individual hubs/outlets/zones) are
                    // grouped in their own nested submenu since they're all
                    // one conceptual area and were cluttering the flat list.
                    // Units lives here too — it's an org structure within a
                    // hub, the same conceptual area as the rest of Location.
                    $topSetupItem = ['label' => 'Company Settings', 'route' => 'settings.edit', 'icon' => 'sliders', 'permission' => 'settings:update'];

                    $locationItems = [
                        ['label' => 'Countries', 'route' => 'countries.index', 'icon' => 'building', 'permission' => 'locations:read'],
                        ['label' => 'States/Provinces', 'route' => 'states.index', 'icon' => 'building', 'permission' => 'locations:read'],
                        ['label' => 'Territories', 'route' => 'territories.index', 'icon' => 'layers', 'permission' => 'locations:read'],
                        ['label' => 'Country Regions', 'route' => 'country-regions.index', 'icon' => 'layers', 'permission' => 'locations:read'],
                        ['label' => 'Cities', 'route' => 'cities.index', 'icon' => 'building', 'permission' => 'locations:read'],
                        ['label' => 'Districts/Areas', 'route' => 'districts.index', 'icon' => 'building', 'permission' => 'locations:read'],
                        ['label' => 'Regions', 'route' => 'regions.index', 'icon' => 'layers', 'permission' => 'locations:read'],
                        ['label' => 'Hubs & Branches', 'route' => 'hubs.index', 'icon' => 'building', 'permission' => 'locations:read'],
                        ['label' => 'Outlets', 'route' => 'outlets.index', 'icon' => 'building', 'permission' => 'locations:read'],
                        ['label' => 'Units', 'route' => 'units.index', 'icon' => 'sliders', 'permission' => 'locations:read'],
                        ['label' => 'Routes', 'route' => 'routes.index', 'icon' => 'route', 'permission' => 'locations:read'],
                    ];

                    $restSetupItems = [
                        ['label' => 'Scan Statuses', 'route' => 'scan-statuses.index', 'icon' => 'list-check', 'permission' => 'settings:update'],
                        ['label' => 'Roles & Permissions', 'route' => 'roles.index', 'icon' => 'setups', 'permission' => 'roles:read'],
                        ['label' => 'Staff Users', 'route' => 'users.index', 'icon' => 'building', 'permission' => 'users:read'],
                        ['label' => 'Clients', 'route' => 'clients.index', 'icon' => 'building', 'permission' => 'clients:read'],
                    ];

                    $visibleBillingItems = collect($billingItems)->filter(
                        fn ($item) => ! $item['permission'] || auth()->user()->can($item['permission'])
                    );
                    $visibleBillingItemsAfterStandard = collect($billingItemsAfterStandard)->filter(
                        fn ($item) => ! $item['permission'] || auth()->user()->can($item['permission'])
                    );
                    $visibleStandardBillingItems = collect($standardBillingItems)->filter(
                        fn ($item) => ! $item['permission'] || auth()->user()->can($item['permission'])
                    );
                    $visibleLocationItems = collect($locationItems)->filter(
                        fn ($item) => ! $item['permission'] || auth()->user()->can($item['permission'])
                    );
                    $visibleRestSetupItems = collect($restSetupItems)->filter(
                        fn ($item) => ! $item['permission'] || auth()->user()->can($item['permission'])
                    );
                    $topSetupItemVisible = ! $topSetupItem['permission'] || auth()->user()->can($topSetupItem['permission']);

                    $visiblePaymentItems = collect($paymentItems)->filter(
                        fn ($item) => ! $item['permission'] || auth()->user()->can($item['permission'])
                    );
                    $paymentsActive = collect($paymentItems)->contains(fn ($item) => request()->routeIs($item['route'] . '*'));

                    $visibleShippingItems = collect($shippingItems)->filter(
                        fn ($item) => ! $item['permission'] || auth()->user()->can($item['permission'])
                    );
                    $shippingActive = collect($shippingItems)->contains(fn ($item) => request()->routeIs($item['route'] . '*'))
                        || request()->routeIs('shipments.show') || request()->routeIs('shipments.edit') || request()->routeIs('shipments.update')
                        || request()->routeIs('shipments.bulk.*') || request()->routeIs('quotes.*');

                    $visibleScanItems = collect($scanItems)->filter(
                        fn ($item) => ! $item['permission'] || auth()->user()->can($item['permission'])
                    );
                    $scansActive = request()->routeIs('operational-scans.*') || request()->routeIs('manifest-trips.*') || request()->routeIs('manifests.*');

                    // Every Standard Billing sub-item shares the same
                    // route (standard-billing.index) — they're tabs on
                    // one page, not separate pages — so telling them
                    // apart needs the ?model= query param too, not just
                    // the route. Zoning and Weight is the default tab
                    // (shown when no ?model= is given at all), matching
                    // the page's own JS.
                    $standardBillingItemActive = fn ($item) => request()->routeIs('standard-billing.index')
                        && (request('model', 'standard') === $item['model']);
                    $standardBillingActive = collect($standardBillingItems)->contains($standardBillingItemActive);

                    $billingActive = collect($billingItems)->contains(fn ($item) => request()->routeIs($item['route'] . '*'))
                        || collect($billingItemsAfterStandard)->contains(fn ($item) => request()->routeIs($item['route'] . '*'))
                        || $standardBillingActive;
                    $locationActive = collect($locationItems)->contains(fn ($item) => request()->routeIs($item['route'] . '*'));
                    $setupsActive = $billingActive
                        || $locationActive
                        || request()->routeIs($topSetupItem['route'] . '*')
                        || collect($restSetupItems)->contains(fn ($item) => request()->routeIs($item['route'] . '*'));
                @endphp

                @foreach ($navItems as $item)
                    @continue($item['permission'] && auth()->user()->cannot($item['permission']))
                    @php $active = request()->routeIs($item['route'] . '*'); @endphp
                    <a href="{{ route($item['route']) }}"
                       class="group relative flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors
                              {{ $active ? 'bg-white/10 text-white' : 'text-white/70 hover:bg-white/5 hover:text-white' }}">
                        @if ($active)
                            <span class="absolute left-0 top-1/2 h-4 w-1 -translate-y-1/2 rounded-r-full bg-[var(--brand-secondary)]"></span>
                        @endif
                        <x-icon :name="$item['icon']" class="h-[18px] w-[18px] shrink-0" />
                        {{ $item['label'] }}
                    </a>
                @endforeach

                @if ($visibleShippingItems->isNotEmpty())
                    <details class="group/shipping" @if($shippingActive) open @endif>
                        <summary class="flex cursor-pointer items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-white/70 transition-colors hover:bg-white/5 hover:text-white">
                            <x-icon name="box" class="h-[18px] w-[18px] shrink-0" />
                            <span class="flex-1">Shipping</span>
                            <x-icon name="chevron" class="h-4 w-4 shrink-0 transition-transform group-open/shipping:rotate-180" />
                        </summary>

                        <div class="mt-1 space-y-1 border-l border-white/10 pl-4">
                            @foreach ($visibleShippingItems as $item)
                                @php $active = request()->routeIs($item['route'] . '*'); @endphp
                                <a href="{{ route($item['route']) }}"
                                   class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-medium transition-colors
                                          {{ $active ? 'bg-white/10 text-white' : 'text-white/60 hover:bg-white/5 hover:text-white' }}">
                                    <x-icon :name="$item['icon']" class="h-4 w-4 shrink-0" />
                                    {{ $item['label'] }}
                                </a>
                            @endforeach
                        </div>
                    </details>
                @endif

                @if ($visiblePaymentItems->isNotEmpty())
                    <details class="group/payments" @if($paymentsActive) open @endif>
                        <summary class="flex cursor-pointer items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-white/70 transition-colors hover:bg-white/5 hover:text-white">
                            <x-icon name="wallet" class="h-[18px] w-[18px] shrink-0" />
                            <span class="flex-1">Payments</span>
                            <x-icon name="chevron" class="h-4 w-4 shrink-0 transition-transform group-open/payments:rotate-180" />
                        </summary>

                        <div class="mt-1 space-y-1 border-l border-white/10 pl-4">
                            @foreach ($visiblePaymentItems as $item)
                                @php $active = request()->routeIs($item['route'] . '*'); @endphp
                                <a href="{{ route($item['route']) }}"
                                   class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-medium transition-colors
                                          {{ $active ? 'bg-white/10 text-white' : 'text-white/60 hover:bg-white/5 hover:text-white' }}">
                                    <x-icon :name="$item['icon']" class="h-4 w-4 shrink-0" />
                                    {{ $item['label'] }}
                                </a>
                            @endforeach
                        </div>
                    </details>
                @endif

                @if ($visibleScanItems->isNotEmpty())
                    <details class="group/scans" @if($scansActive) open @endif>
                        <summary class="flex cursor-pointer items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-white/70 transition-colors hover:bg-white/5 hover:text-white">
                            <x-icon name="route" class="h-[18px] w-[18px] shrink-0" />
                            <span class="flex-1">Operations</span>
                            <x-icon name="chevron" class="h-4 w-4 shrink-0 transition-transform group-open/scans:rotate-180" />
                        </summary>

                        <div class="mt-1 space-y-1 border-l border-white/10 pl-4">
                            @foreach ($visibleScanItems as $item)
                                @php
                                    $itemUrl = isset($item['route']) ? route($item['route']) : route('operational-scans.index', $item['type']);
                                    $active = isset($item['route'])
                                        ? request()->routeIs($item['route'] . '*')
                                        : (request()->routeIs('operational-scans.*') && request()->route('type') === $item['type']);
                                @endphp
                                <a href="{{ $itemUrl }}"
                                   class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-medium transition-colors
                                          {{ $active ? 'bg-white/10 text-white' : 'text-white/60 hover:bg-white/5 hover:text-white' }}">
                                    <x-icon :name="$item['icon']" class="h-4 w-4 shrink-0" />
                                    {{ $item['label'] }}
                                </a>
                            @endforeach
                        </div>
                    </details>
                @endif

                @if ($topSetupItemVisible || $visibleBillingItems->isNotEmpty() || $visibleLocationItems->isNotEmpty() || $visibleRestSetupItems->isNotEmpty())
                    <details class="group/setups" @if($setupsActive) open @endif>
                        <summary class="flex cursor-pointer items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-white/70 transition-colors hover:bg-white/5 hover:text-white">
                            <x-icon name="setups" class="h-[18px] w-[18px] shrink-0" />
                            <span class="flex-1">Setups</span>
                            <x-icon name="chevron" class="h-4 w-4 shrink-0 transition-transform group-open/setups:rotate-180" />
                        </summary>

                        <div class="mt-1 space-y-1 border-l border-white/10 pl-4">
                            @if ($topSetupItemVisible)
                                @php $active = request()->routeIs($topSetupItem['route'] . '*'); @endphp
                                <a href="{{ route($topSetupItem['route']) }}"
                                   class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-medium transition-colors
                                          {{ $active ? 'bg-white/10 text-white' : 'text-white/60 hover:bg-white/5 hover:text-white' }}">
                                    <x-icon :name="$topSetupItem['icon']" class="h-4 w-4 shrink-0" />
                                    {{ $topSetupItem['label'] }}
                                </a>
                            @endif

                            @if ($visibleLocationItems->isNotEmpty())
                                <details class="group/location" @if($locationActive) open @endif>
                                    <summary class="flex cursor-pointer items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-medium text-white/60 transition-colors hover:bg-white/5 hover:text-white">
                                        <x-icon name="layers" class="h-4 w-4 shrink-0" />
                                        <span class="flex-1">Location</span>
                                        <x-icon name="chevron" class="h-3.5 w-3.5 shrink-0 transition-transform group-open/location:rotate-180" />
                                    </summary>
                                    <div class="mt-1 space-y-1 border-l border-white/10 pl-4">
                                        @foreach ($visibleLocationItems as $item)
                                            @php $active = request()->routeIs($item['route'] . '*'); @endphp
                                            <a href="{{ route($item['route']) }}"
                                               class="flex items-center gap-2.5 rounded-lg px-3 py-1.5 text-sm font-medium transition-colors
                                                      {{ $active ? 'bg-white/10 text-white' : 'text-white/55 hover:bg-white/5 hover:text-white' }}">
                                                <x-icon :name="$item['icon']" class="h-3.5 w-3.5 shrink-0" />
                                                {{ $item['label'] }}
                                            </a>
                                        @endforeach
                                    </div>
                                </details>
                            @endif

                            @if ($visibleBillingItems->isNotEmpty() || $visibleStandardBillingItems->isNotEmpty() || $visibleBillingItemsAfterStandard->isNotEmpty())
                                <details class="group/billing" @if($billingActive) open @endif>
                                    <summary class="flex cursor-pointer items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-medium text-white/60 transition-colors hover:bg-white/5 hover:text-white">
                                        <x-icon name="list-check" class="h-4 w-4 shrink-0" />
                                        <span class="flex-1">Billing</span>
                                        <x-icon name="chevron" class="h-3.5 w-3.5 shrink-0 transition-transform group-open/billing:rotate-180" />
                                    </summary>
                                    <div class="mt-1 space-y-1 border-l border-white/10 pl-4">
                                        @foreach ($visibleBillingItems as $item)
                                            @php $active = request()->routeIs($item['route'] . '*'); @endphp
                                            <a href="{{ route($item['route']) }}"
                                               class="flex items-center gap-2.5 rounded-lg px-3 py-1.5 text-sm font-medium transition-colors
                                                      {{ $active ? 'bg-white/10 text-white' : 'text-white/55 hover:bg-white/5 hover:text-white' }}">
                                                <x-icon :name="$item['icon']" class="h-3.5 w-3.5 shrink-0" />
                                                {{ $item['label'] }}
                                            </a>
                                        @endforeach

                                        @if ($visibleStandardBillingItems->isNotEmpty())
                                            <details class="group/standard-billing" @if($standardBillingActive) open @endif>
                                                <summary class="flex cursor-pointer items-center gap-2.5 rounded-lg px-3 py-1.5 text-sm font-medium text-white/55 transition-colors hover:bg-white/5 hover:text-white">
                                                    <x-icon name="sliders" class="h-3.5 w-3.5 shrink-0" />
                                                    <span class="flex-1">Standard Billing</span>
                                                    <x-icon name="chevron" class="h-3 w-3 shrink-0 transition-transform group-open/standard-billing:rotate-180" />
                                                </summary>
                                                <div class="mt-1 space-y-1 border-l border-white/10 pl-4">
                                                    @foreach ($visibleStandardBillingItems as $item)
                                                        @php $active = $standardBillingItemActive($item); @endphp
                                                        <a href="{{ route($item['route'], ['model' => $item['model']]) }}"
                                                           class="flex items-center gap-2.5 rounded-lg px-3 py-1.5 text-sm font-medium transition-colors
                                                                  {{ $active ? 'bg-white/10 text-white' : 'text-white/50 hover:bg-white/5 hover:text-white' }}">
                                                            <x-icon :name="$item['icon']" class="h-3.5 w-3.5 shrink-0" />
                                                            {{ $item['label'] }}
                                                        </a>
                                                    @endforeach
                                                </div>
                                            </details>
                                        @endif

                                        @foreach ($visibleBillingItemsAfterStandard as $item)
                                            @php $active = request()->routeIs($item['route'] . '*'); @endphp
                                            <a href="{{ route($item['route']) }}"
                                               class="flex items-center gap-2.5 rounded-lg px-3 py-1.5 text-sm font-medium transition-colors
                                                      {{ $active ? 'bg-white/10 text-white' : 'text-white/55 hover:bg-white/5 hover:text-white' }}">
                                                <x-icon :name="$item['icon']" class="h-3.5 w-3.5 shrink-0" />
                                                {{ $item['label'] }}
                                            </a>
                                        @endforeach
                                    </div>
                                </details>
                            @endif

                            @foreach ($visibleRestSetupItems as $item)
                                @php $active = request()->routeIs($item['route'] . '*'); @endphp
                                <a href="{{ route($item['route']) }}"
                                   class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-medium transition-colors
                                          {{ $active ? 'bg-white/10 text-white' : 'text-white/60 hover:bg-white/5 hover:text-white' }}">
                                    <x-icon :name="$item['icon']" class="h-4 w-4 shrink-0" />
                                    {{ $item['label'] }}
                                </a>
                            @endforeach
                        </div>
                    </details>
                @endif
            </nav>

            <div class="border-t border-white/10 p-3">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-left text-sm font-medium text-white/70 transition-colors hover:bg-white/5 hover:text-white">
                        <x-icon name="logout" class="h-[18px] w-[18px] shrink-0" />
                        Sign out
                    </button>
                </form>
            </div>
        </aside>

        {{-- Main --}}
        <div class="flex min-w-0 flex-1 flex-col">
            <header class="flex h-16 shrink-0 items-center justify-between border-b border-line bg-surface-0/80 px-4 backdrop-blur md:px-8">
                <div class="flex items-center gap-3">
                    <button id="sidebar-open" type="button" class="text-ink-500 hover:text-ink-900 md:hidden">
                        <x-icon name="menu" class="h-5 w-5" />
                    </button>
                    {{-- One universal back control rather than a
                         per-page destination to maintain — browser
                         history already knows where the person came
                         from, so this works correctly everywhere this
                         layout is used without needing to be added
                         to each page individually. --}}
                    <button type="button" onclick="history.back()" title="Back" class="text-ink-500 hover:text-ink-900">
                        <x-icon name="arrow-left" class="h-5 w-5" />
                    </button>
                    <div>
                        @if ($setupsActive)
                            <p class="text-xs font-medium text-ink-500">Setups</p>
                        @endif
                        <h1 class="text-lg font-semibold text-ink-900">{{ $title ?? 'Dashboard' }}</h1>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <span class="hidden text-sm text-ink-500 sm:inline">{{ auth()->user()?->name }}</span>
                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-[var(--brand-primary)]/10 text-xs font-semibold text-[var(--brand-primary)] ring-1 ring-[var(--brand-primary)]/20">
                        {{ strtoupper(substr(auth()->user()?->name ?? '?', 0, 1)) }}
                    </span>
                </div>
            </header>

            <main class="flex-1 overflow-y-auto p-4 md:p-8">
                {{ $slot }}
            </main>
        </div>
    </div>

    <script>
        (function () {
            const sidebar = document.getElementById('sidebar');
            const backdrop = document.getElementById('sidebar-backdrop');
            const openBtn = document.getElementById('sidebar-open');
            const closeBtn = document.getElementById('sidebar-close');

            function openSidebar() {
                sidebar.classList.remove('-translate-x-full');
                backdrop.classList.remove('hidden');
            }

            function closeSidebar() {
                sidebar.classList.add('-translate-x-full');
                backdrop.classList.add('hidden');
            }

            openBtn?.addEventListener('click', openSidebar);
            closeBtn?.addEventListener('click', closeSidebar);
            backdrop?.addEventListener('click', closeSidebar);
        })();
    </script>

    {{-- A styled confirmation modal, shared by every page through
         this one layout, replacing the browser's plain confirm().
         Two ways to use it:
         1. Add data-confirm="message" to any form tag — its normal
            submission is intercepted, the modal shows that message,
            and the form actually submits, bypassing this listener
            entirely, only if the person confirms.
         2. Call window.confirmDialog("message").then(ok => ...) from
            any script for a non-form action (an AJAX call, a
            multi-step JS flow) — resolves true/false. --}}
    <div id="confirm-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-4">
        <div class="w-full max-w-sm rounded-xl bg-surface-0 p-5 shadow-xl">
            <p id="confirm-modal-message" class="text-sm text-ink-900"></p>
            <div class="mt-5 flex justify-end gap-2">
                <button type="button" id="confirm-modal-cancel" class="rounded-md border border-line px-3 py-1.5 text-sm font-medium text-ink-700 hover:bg-surface-50">Cancel</button>
                <button type="button" id="confirm-modal-confirm" class="rounded-md bg-[var(--brand-primary)] px-3 py-1.5 text-sm font-semibold text-white hover:opacity-90">Confirm</button>
            </div>
        </div>
    </div>
    <script>
        (function () {
            const modal = document.getElementById('confirm-modal');
            const messageEl = document.getElementById('confirm-modal-message');
            const cancelBtn = document.getElementById('confirm-modal-cancel');
            const confirmBtn = document.getElementById('confirm-modal-confirm');
            let pendingResolve = null;

            function openModal(message) {
                messageEl.textContent = message;
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                return new Promise(resolve => { pendingResolve = resolve; });
            }

            function closeModal(result) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                pendingResolve?.(result);
                pendingResolve = null;
            }

            cancelBtn.addEventListener('click', () => closeModal(false));
            confirmBtn.addEventListener('click', () => closeModal(true));
            modal.addEventListener('click', e => { if (e.target === modal) closeModal(false); });

            window.confirmDialog = openModal;

            document.addEventListener('submit', function (e) {
                const form = e.target;
                if (!(form instanceof HTMLFormElement) || !form.dataset.confirm) return;
                e.preventDefault();
                openModal(form.dataset.confirm).then(ok => { if (ok) form.submit(); });
            });
        })();
    </script>
</body>
</html>
