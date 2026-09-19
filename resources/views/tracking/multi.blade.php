<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tracking results — {{ config('branding.company_name') }}</title>
    <style>
        :root {
            --brand-primary: {{ config('branding.colors.primary') }};
            --brand-secondary: {{ config('branding.colors.secondary') }};
        }
    </style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full bg-surface-50 text-ink-900 antialiased">
    <div class="mx-auto max-w-2xl px-4 py-10">

        <div class="mb-6 flex items-center gap-3">
            @if (config('branding.logo_url'))
                <img src="{{ config('branding.logo_url') }}" alt="{{ config('branding.company_name') }}" class="h-9 w-9 rounded-lg object-cover">
            @else
                <span class="grid h-9 w-9 place-items-center rounded-lg bg-[var(--brand-primary)] font-mono text-sm font-bold text-white">
                    {{ strtoupper(substr(config('branding.company_name'), 0, 2)) }}
                </span>
            @endif
            <span class="text-sm font-semibold text-ink-900">{{ config('branding.company_name') }}</span>
            <a href="{{ route('tracking.search') }}" class="ml-auto text-sm text-[var(--brand-primary)] hover:underline">Track more numbers</a>
        </div>

        <p class="mb-4 text-sm text-ink-500">{{ $results->count() }} number(s) checked</p>

        <div class="rounded-xl border border-line bg-white shadow-sm overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-line text-left text-xs uppercase tracking-wide text-ink-500">
                        <th class="p-3">Number</th>
                        <th class="p-3">Kind</th>
                        <th class="p-3">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($results as $result)
                        <tr class="border-b border-line last:border-0">
                            <td class="p-3">
                                @if ($result['found'])
                                    <a href="{{ route('tracking.show', $result['number']) }}" class="font-mono text-[var(--brand-primary)] hover:underline">{{ $result['number'] }}</a>
                                @else
                                    <span class="font-mono text-ink-500">{{ $result['number'] }}</span>
                                @endif
                            </td>
                            <td class="p-3 text-ink-700">{{ ucfirst($result['kind']) }}</td>
                            <td class="p-3">
                                @if (! $result['found'])
                                    <span class="text-status-exception">Not found</span>
                                @elseif ($result['kind'] === 'manifest')
                                    <span class="text-ink-700">{{ ucfirst($result['status']) }} · {{ $result['count'] }} shipment(s)</span>
                                @elseif ($result['kind'] === 'trip')
                                    <span class="text-ink-700">{{ ucfirst($result['status']) }}</span>
                                @else
                                    <span class="text-ink-700">{{ ucfirst(str_replace('_', ' ', $result['status'])) }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

    </div>
</body>
</html>
