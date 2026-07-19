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
                        ['label' => 'Shipments', 'route' => 'shipments.index', 'icon' => 'box', 'permission' => null],
                        ['label' => 'Rate Checker', 'route' => 'rate-checker.index', 'icon' => 'search', 'permission' => 'billing:read'],
                    ];

                    // Billing setup — nested inside Setups alongside
                    // Location, same pattern: a collapsible submenu rather
                    // than a flat list of six items.
                    $billingItems = [
                        ['label' => 'Service Types', 'route' => 'service-types.index', 'icon' => 'list-check', 'permission' => 'billing:read'],
                        ['label' => 'Zones', 'route' => 'zones.index', 'icon' => 'layers', 'permission' => 'locations:read'],
                        ['label' => 'Onforwarding', 'route' => 'onforwarding-classifications.index', 'icon' => 'list-check', 'permission' => 'billing:read'],
                        ['label' => 'Zone Mapping', 'route' => 'zone-mappings.index', 'icon' => 'layers', 'permission' => 'rates:read'],
                        ['label' => 'Standard Billing', 'route' => 'standard-billing.index', 'icon' => 'sliders', 'permission' => 'billing:read'],
                        ['label' => 'Additional Services', 'route' => 'additional-services.index', 'icon' => 'list-check', 'permission' => 'billing:read'],
                        ['label' => 'Invoice', 'route' => 'invoices.index', 'icon' => 'list-check', 'permission' => 'billing:read'],
                        ['label' => 'Client Billing', 'route' => 'client-billing.index', 'icon' => 'list-check', 'permission' => 'billing:read'],
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
                    ];

                    $visibleBillingItems = collect($billingItems)->filter(
                        fn ($item) => ! $item['permission'] || auth()->user()->can($item['permission'])
                    );
                    $visibleLocationItems = collect($locationItems)->filter(
                        fn ($item) => ! $item['permission'] || auth()->user()->can($item['permission'])
                    );
                    $visibleRestSetupItems = collect($restSetupItems)->filter(
                        fn ($item) => ! $item['permission'] || auth()->user()->can($item['permission'])
                    );
                    $topSetupItemVisible = ! $topSetupItem['permission'] || auth()->user()->can($topSetupItem['permission']);

                    $billingActive = collect($billingItems)->contains(fn ($item) => request()->routeIs($item['route'] . '*'));
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

                            @if ($visibleBillingItems->isNotEmpty())
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
</body>
</html>
