<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'My Account' }} — {{ config('branding.company_name') }}</title>
    <style>
        :root {
            --brand-primary: {{ config('branding.colors.primary') }};
            --brand-secondary: {{ config('branding.colors.secondary') }};
        }
    </style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-surface-50 text-ink-900 antialiased">
    <header class="border-b border-line bg-surface-0">
        <div class="mx-auto flex max-w-5xl items-center justify-between px-4 py-4">
            <div class="flex items-center gap-3">
                @if (config('branding.logo_url'))
                    <img src="{{ config('branding.logo_url') }}" alt="{{ config('branding.company_name') }}" class="h-8 w-8 rounded-lg object-cover">
                @else
                    <span class="grid h-8 w-8 place-items-center rounded-lg bg-[var(--brand-primary)] font-mono text-sm font-bold text-white">
                        {{ strtoupper(substr(config('branding.company_name'), 0, 2)) }}
                    </span>
                @endif
                <span class="text-sm font-semibold text-ink-900">{{ config('branding.company_name') }}</span>
            </div>
            <nav class="flex items-center gap-4 text-sm">
                <a href="{{ route('portal.dashboard') }}" class="font-medium text-ink-700 hover:text-ink-900">Dashboard</a>
                <form method="POST" action="{{ route('portal.logout') }}">
                    @csrf
                    <button type="submit" class="font-medium text-ink-500 hover:text-ink-900">Sign out</button>
                </form>
            </nav>
        </div>
    </header>

    <main class="mx-auto max-w-5xl px-4 py-8">
        {{ $slot }}
    </main>
</body>
</html>
