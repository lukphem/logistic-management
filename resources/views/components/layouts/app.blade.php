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

        {{-- Sidebar --}}
        <aside class="flex w-64 shrink-0 flex-col bg-[var(--brand-primary)] bg-gradient-to-b from-white/0 to-black/10 text-white">
            <div class="flex h-16 items-center gap-2.5 px-5 border-b border-white/10">
                @if (config('branding.logo_url'))
                    <img src="{{ config('branding.logo_url') }}" alt="{{ config('branding.company_name') }}" class="h-8 w-8 rounded-md object-cover ring-1 ring-white/20">
                @else
                    <span class="grid h-8 w-8 place-items-center rounded-md bg-[var(--brand-secondary)] font-mono text-sm font-bold text-ink-900">
                        {{ strtoupper(substr(config('branding.company_name'), 0, 2)) }}
                    </span>
                @endif
                <span class="truncate text-sm font-semibold tracking-wide">{{ config('branding.company_name') }}</span>
            </div>

            <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4">
                @php
                    $navItems = [
                        ['label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'dashboard', 'permission' => null],
                        ['label' => 'Shipments', 'route' => 'shipments.index', 'icon' => 'box', 'permission' => null],
                    ];

                    // Ordered by setup dependency: company profile/branding has
                    // no prerequisites; hubs must exist before zones can
                    // reference one; scan statuses are independent but belong
                    // here as the last operational-config step.
                    $setupItems = [
                        ['label' => 'Company Settings', 'route' => 'settings.edit', 'icon' => 'sliders', 'permission' => 'settings:update'],
                        ['label' => 'Regions', 'route' => 'regions.index', 'icon' => 'layers', 'permission' => 'locations:read'],
                        ['label' => 'Hubs & Branches', 'route' => 'hubs.index', 'icon' => 'building', 'permission' => 'locations:read'],
                        ['label' => 'Outlets', 'route' => 'outlets.index', 'icon' => 'building', 'permission' => 'locations:read'],
                        ['label' => 'Units', 'route' => 'units.index', 'icon' => 'sliders', 'permission' => 'locations:read'],
                        ['label' => 'Zones', 'route' => 'zones.index', 'icon' => 'layers', 'permission' => 'locations:read'],
                        ['label' => 'Rate Cards', 'route' => 'rate-cards.index', 'icon' => 'sliders', 'permission' => 'rates:read'],
                        ['label' => 'Client Billing', 'route' => 'client-billing.index', 'icon' => 'list-check', 'permission' => 'billing:read'],
                        ['label' => 'Scan Statuses', 'route' => 'scan-statuses.index', 'icon' => 'list-check', 'permission' => 'settings:update'],
                        ['label' => 'Roles & Permissions', 'route' => 'roles.index', 'icon' => 'setups', 'permission' => 'roles:read'],
                        ['label' => 'Staff Users', 'route' => 'users.index', 'icon' => 'building', 'permission' => 'users:read'],
                    ];

                    $visibleSetupItems = collect($setupItems)->filter(
                        fn ($item) => ! $item['permission'] || auth()->user()->can($item['permission'])
                    );

                    $setupsActive = collect($setupItems)->contains(fn ($item) => request()->routeIs($item['route'] . '*'));
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

                @if ($visibleSetupItems->isNotEmpty())
                    <details class="group/setups" @if($setupsActive) open @endif>
                        <summary class="flex cursor-pointer items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-white/70 transition-colors hover:bg-white/5 hover:text-white">
                            <x-icon name="setups" class="h-[18px] w-[18px] shrink-0" />
                            <span class="flex-1">Setups</span>
                            <x-icon name="chevron" class="h-4 w-4 shrink-0 transition-transform group-open/setups:rotate-180" />
                        </summary>

                        <div class="mt-1 space-y-1 border-l border-white/10 pl-4">
                            @foreach ($visibleSetupItems as $item)
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
            <header class="flex h-16 shrink-0 items-center justify-between border-b border-line bg-surface-0/80 px-8 backdrop-blur">
                <div>
                    @if ($setupsActive)
                        <p class="text-xs font-medium text-ink-500">Setups</p>
                    @endif
                    <h1 class="text-lg font-semibold text-ink-900">{{ $title ?? 'Dashboard' }}</h1>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-sm text-ink-500">{{ auth()->user()?->name }}</span>
                    <span class="grid h-9 w-9 place-items-center rounded-full bg-[var(--brand-primary)]/10 text-xs font-semibold text-[var(--brand-primary)] ring-1 ring-[var(--brand-primary)]/20">
                        {{ strtoupper(substr(auth()->user()?->name ?? '?', 0, 1)) }}
                    </span>
                </div>
            </header>

            <main class="flex-1 overflow-y-auto p-8">
                {{ $slot }}
            </main>
        </div>
    </div>
</body>
</html>
