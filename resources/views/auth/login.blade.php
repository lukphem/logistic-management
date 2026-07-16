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
    </style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-surface-50 text-ink-900 antialiased">
    <div class="grid h-full place-items-center px-4">
        <div class="w-full max-w-sm">
            <div class="mb-8 flex flex-col items-center gap-3">
                @if (config('branding.logo_url'))
                    <img src="{{ config('branding.logo_url') }}" alt="{{ config('branding.company_name') }}" class="h-12 w-12 rounded-lg object-cover">
                @else
                    <span class="grid h-12 w-12 place-items-center rounded-lg bg-[var(--brand-primary)] font-mono text-lg font-bold text-white">
                        {{ strtoupper(substr(config('branding.company_name'), 0, 2)) }}
                    </span>
                @endif
                <div class="text-center">
                    <p class="text-sm font-medium text-ink-500">{{ config('branding.company_name') }}</p>
                    <h1 class="text-xl font-semibold text-ink-900">Staff sign in</h1>
                </div>
            </div>

            <div class="rounded-xl border border-line bg-surface-0 shadow-sm p-6 shadow-sm">
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
                            class="w-full rounded-md bg-[var(--brand-primary)] py-2 text-sm font-semibold text-white transition hover:opacity-90">
                        Sign in
                    </button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
