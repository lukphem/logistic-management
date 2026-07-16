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
    </style>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-surface-50 text-ink-900 antialiased">
    <div class="flex h-full">

        {{-- Sidebar --}}
        <aside class="flex w-60 shrink-0 flex-col bg-[var(--brand-primary)] text-white">
            <div class="flex h-16 items-center gap-2 px-5 border-b border-white/10">
                <span class="grid h-8 w-8 place-items-center rounded-md bg-[var(--brand-secondary)] font-mono text-sm font-bold text-ink-900">
                    {{ strtoupper(substr(config('branding.company_name'), 0, 2)) }}
                </span>
                <span class="truncate text-sm font-semibold tracking-wide">{{ config('branding.company_name') }}</span>
            </div>

            <nav class="flex-1 space-y-1 px-3 py-4">
                @php
                    $navItems = [
                        ['label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'grid'],
                        ['label' => 'Shipments', 'route' => 'shipments.index', 'icon' => 'box'],
                    ];
                @endphp

                @foreach ($navItems as $item)
                    @php $active = request()->routeIs($item['route'] . '*'); @endphp
                    <a href="{{ route($item['route']) }}"
                       class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition
                              {{ $active ? 'bg-white/10 text-white' : 'text-white/70 hover:bg-white/5 hover:text-white' }}">
                        <span class="relative flex h-1.5 w-1.5 shrink-0 rounded-full {{ $active ? 'bg-[var(--brand-secondary)]' : 'bg-transparent' }}"></span>
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </nav>

            <div class="border-t border-white/10 p-3">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full rounded-md px-3 py-2 text-left text-sm font-medium text-white/70 transition hover:bg-white/5 hover:text-white">
                        Sign out
                    </button>
                </form>
            </div>
        </aside>

        {{-- Main --}}
        <div class="flex min-w-0 flex-1 flex-col">
            <header class="flex h-16 shrink-0 items-center justify-between border-b border-line bg-surface-0 px-8">
                <h1 class="text-lg font-semibold text-ink-900">{{ $title ?? 'Dashboard' }}</h1>
                <div class="flex items-center gap-3">
                    <span class="text-sm text-ink-500">{{ auth()->user()?->name }}</span>
                    <span class="grid h-8 w-8 place-items-center rounded-full bg-surface-50 text-xs font-semibold text-ink-500 ring-1 ring-line">
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
