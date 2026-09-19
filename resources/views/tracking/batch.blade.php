<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $trackingNumber }} — {{ config('branding.company_name') }}</title>
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
            @if ($back)
                <a href="{{ route('tracking.multi', ['numbers' => $back]) }}" class="ml-auto text-sm text-[var(--brand-primary)] hover:underline">← Back to results</a>
            @else
                <a href="{{ route('tracking.search') }}" class="ml-auto text-sm text-[var(--brand-primary)] hover:underline">Track another number</a>
            @endif
        </div>

        @if (! $batch)
            <div class="rounded-xl border border-line bg-white shadow-sm p-8 text-center">
                <p class="text-lg font-semibold text-ink-900">Nothing found</p>
                <p class="mt-1 text-sm text-ink-500">We couldn't find anything for <span class="font-mono">{{ $trackingNumber }}</span>. Double-check the number and try again.</p>
                <a href="{{ route('tracking.search') }}" class="mt-4 inline-block rounded-md bg-[var(--brand-primary)] px-4 py-2 text-sm font-semibold text-white hover:opacity-90">Try again</a>
            </div>
        @else
            <div class="rounded-xl border border-line bg-white shadow-sm p-6">
                <p class="font-mono text-lg font-semibold text-ink-900">{{ $batchLabel }}</p>
                <p class="mt-1 text-sm text-ink-500">{{ $shipments->count() }} shipment(s) in this batch</p>
            </div>

            <div class="mt-6 rounded-xl border border-line bg-white shadow-sm overflow-hidden">
                @if ($shipments->isEmpty())
                    <p class="p-6 text-sm text-ink-500">No shipments recorded for this batch.</p>
                @else
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-line text-left text-xs uppercase tracking-wide text-ink-500">
                                <th class="p-3">Tracking #</th>
                                <th class="p-3">Receiver</th>
                                <th class="p-3">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($shipments as $shipment)
                                <tr class="border-b border-line last:border-0">
                                    <td class="p-3">
                                        <a href="{{ route('tracking.show', $shipment->tracking_number) }}" class="font-mono text-[var(--brand-primary)] hover:underline">{{ $shipment->tracking_number }}</a>
                                    </td>
                                    <td class="p-3 text-ink-700">{{ $shipment->receiver_name }}</td>
                                    <td class="p-3 text-ink-700">{{ ucfirst(str_replace('_', ' ', $shipment->current_status)) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        @endif

    </div>
</body>
</html>
