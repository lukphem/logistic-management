<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Track a shipment — {{ config('branding.company_name') }}</title>
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
            <div class="mb-8 flex flex-col items-center gap-3 text-center">
                @if (config('branding.logo_url'))
                    <img src="{{ config('branding.logo_url') }}" alt="{{ config('branding.company_name') }}" class="h-14 w-14 rounded-lg object-cover">
                @else
                    <span class="grid h-14 w-14 place-items-center rounded-lg bg-[var(--brand-primary)] font-mono text-xl font-bold text-white">
                        {{ strtoupper(substr(config('branding.company_name'), 0, 2)) }}
                    </span>
                @endif
                <p class="text-lg font-semibold text-ink-900">{{ config('branding.company_name') }}</p>
                <p class="text-sm text-ink-500">Enter one or more tracking, manifest, or trip numbers to see where they are.</p>
            </div>

            @if ($errors->any())
                <div class="mb-4 rounded-xl border border-status-exception/30 bg-status-exception/5 px-4 py-3 text-sm text-status-exception">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('tracking.submit') }}" class="rounded-xl border border-line bg-white shadow-sm p-6">
                @csrf
                <label class="mb-1 block text-sm font-medium text-ink-900">Tracking number(s)</label>
                <textarea name="tracking_numbers" required autofocus rows="3" placeholder="e.g. LM260913WAAORT&#10;One per line for more than one"
                          class="w-full resize-y rounded-md border border-line px-3 py-2.5 text-sm font-mono outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">{{ old('tracking_numbers') }}</textarea>
                <p class="mt-1 text-xs text-ink-500">One per line, or separate with commas.</p>
                <button type="submit" class="mt-4 w-full rounded-md bg-[var(--brand-primary)] px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:opacity-90">
                    Track
                </button>
            </form>
        </div>
    </div>
</body>
</html>
