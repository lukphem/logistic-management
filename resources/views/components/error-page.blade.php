@props(['code', 'title', 'message'])

<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} — {{ config('branding.company_name') }}</title>
    <style>
        :root {
            --brand-primary: {{ config('branding.colors.primary') }};
            --brand-secondary: {{ config('branding.colors.secondary') }};
        }
    </style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-surface-50 text-ink-900 antialiased">
    <div class="grid h-full place-items-center px-4">
        <div class="w-full max-w-sm text-center">

            <span class="mx-auto mb-5 grid h-14 w-14 place-items-center rounded-lg border-2 border-dashed border-[var(--brand-primary)] font-mono text-xl font-bold text-[var(--brand-primary)]">
                {{ $code }}
            </span>

            <h1 class="text-xl font-semibold text-ink-900">{{ $title }}</h1>
            <p class="mt-2 text-sm text-ink-500">{{ $message }}</p>

            <div class="mt-6 flex justify-center gap-3">
                @auth
                    <a href="{{ route('dashboard') }}"
                       class="rounded-md bg-[var(--brand-primary)] px-4 py-2 text-sm font-semibold text-white transition hover:opacity-90">
                        Back to dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}"
                       class="rounded-md bg-[var(--brand-primary)] px-4 py-2 text-sm font-semibold text-white transition hover:opacity-90">
                        Sign in
                    </a>
                @endauth
            </div>
        </div>
    </div>
</body>
</html>
