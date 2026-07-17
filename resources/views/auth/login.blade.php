<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in — {{ config('branding.company_name') }}</title>
    <style>
        :root {
            --brand-primary: {{ config('branding.colors.primary') }};
            --brand-secondary: {{ config('branding.colors.secondary') }};
        }
        @keyframes drive {
            0% { transform: translateX(0); }
            100% { transform: translateX(14px); }
        }
        .truck-drive { animation: drive 2.4s ease-in-out infinite alternate; }
    </style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-surface-50 text-ink-900 antialiased">
    <div class="grid h-full grid-cols-1 md:grid-cols-2">

        {{-- Left: logistics illustration panel — hidden on mobile, no room for it there --}}
        <div class="relative hidden overflow-hidden bg-[var(--brand-primary)] bg-gradient-to-br from-white/0 to-black/20 md:flex md:flex-col md:justify-between">
            <div class="relative z-10 p-10">
                <div class="flex items-center gap-2.5">
                    @if (config('branding.logo_url'))
                        <img src="{{ config('branding.logo_url') }}" alt="{{ config('branding.company_name') }}" class="h-9 w-9 rounded-md object-cover ring-1 ring-white/20">
                    @else
                        <span class="grid h-9 w-9 place-items-center rounded-md bg-[var(--brand-secondary)] font-mono text-sm font-bold text-ink-900">
                            {{ strtoupper(substr(config('branding.company_name'), 0, 2)) }}
                        </span>
                    @endif
                    <span class="text-sm font-semibold tracking-wide text-white">{{ config('branding.company_name') }}</span>
                </div>
            </div>

            {{-- The route: two map pins joined by a dashed path, with a truck driving along it --}}
            <div class="relative z-10 flex-1 px-14">
                <div class="relative mt-6 h-40">
                    <x-icon name="map-pin" class="absolute left-0 top-2 h-7 w-7 text-[var(--brand-secondary)]" />
                    <x-icon name="map-pin" class="absolute right-0 bottom-0 h-7 w-7 text-white" />
                    <svg class="absolute inset-0 h-full w-full" viewBox="0 0 400 160" fill="none">
                        <path d="M20 20 Q 200 20 380 140" stroke="white" stroke-opacity="0.35" stroke-width="2.5" stroke-dasharray="6 8" stroke-linecap="round"/>
                    </svg>
                    <x-icon name="truck" class="truck-drive absolute h-9 w-9 text-white" style="left: 160px; top: 62px;" />
                </div>

                {{-- Floating package icons for texture --}}
                <x-icon name="package" class="absolute h-6 w-6 text-white/25" style="left: 60%; top: 8%;" />
                <x-icon name="package" class="absolute h-5 w-5 text-white/20" style="left: 15%; top: 55%;" />
                <x-icon name="package" class="absolute h-7 w-7 text-white/15" style="left: 78%; top: 62%;" />
            </div>

            <div class="relative z-10 p-10">
                <p class="max-w-xs text-lg font-medium leading-snug text-white">
                    Every shipment, tracked from pickup to doorstep.
                </p>
                <p class="mt-2 max-w-xs text-sm text-white/60">
                    Sign in to manage hubs, shipments, and billing across your network.
                </p>
            </div>
        </div>

        {{-- Right: the actual sign-in form --}}
        <div class="grid place-items-center px-4 py-12">
            <div class="w-full max-w-sm">
                <div class="mb-8 flex flex-col items-center gap-3 md:hidden">
                    @if (config('branding.logo_url'))
                        <img src="{{ config('branding.logo_url') }}" alt="{{ config('branding.company_name') }}" class="h-12 w-12 rounded-lg object-cover">
                    @else
                        <span class="grid h-12 w-12 place-items-center rounded-lg bg-[var(--brand-primary)] font-mono text-lg font-bold text-white">
                            {{ strtoupper(substr(config('branding.company_name'), 0, 2)) }}
                        </span>
                    @endif
                    <p class="text-sm font-medium text-ink-500">{{ config('branding.company_name') }}</p>
                </div>

                <div class="mb-6 hidden md:block">
                    <p class="text-sm font-medium text-ink-500">Welcome back</p>
                </div>
                <h1 class="mb-6 text-xl font-semibold text-ink-900">Staff sign in</h1>

                @if ($errors->any())
                    <div class="mb-4 rounded-md bg-status-exception/10 px-3 py-2 text-sm text-status-exception">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label for="email" class="mb-1 block text-sm font-medium text-ink-900">Email <x-required /></label>
                        <input id="email" name="email" type="email" required autofocus value="{{ old('email') }}"
                               class="w-full rounded-md border border-line px-3 py-2 text-sm text-ink-900 outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
                    </div>
                    <div>
                        <label for="password" class="mb-1 block text-sm font-medium text-ink-900">Password <x-required /></label>
                        <input id="password" name="password" type="password" required
                               class="w-full rounded-md border border-line px-3 py-2 text-sm text-ink-900 outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
                    </div>
                    <label class="flex items-center gap-2 text-sm text-ink-500">
                        <input type="checkbox" name="remember" class="rounded border-line">
                        Keep me signed in
                    </label>
                    <button type="submit"
                            class="w-full rounded-md bg-[var(--brand-primary)] py-2.5 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 hover:shadow-md">
                        Sign in
                    </button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
