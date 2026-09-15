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
            <a href="{{ route('tracking.search') }}" class="ml-auto text-sm text-[var(--brand-primary)] hover:underline">Track another shipment</a>
        </div>

        @if (! $shipment)
            <div class="rounded-xl border border-line bg-white shadow-sm p-8 text-center">
                <p class="text-lg font-semibold text-ink-900">No shipment found</p>
                <p class="mt-1 text-sm text-ink-500">We couldn't find anything for tracking number <span class="font-mono">{{ $trackingNumber }}</span>. Double-check the number and try again.</p>
                <a href="{{ route('tracking.search') }}" class="mt-4 inline-block rounded-md bg-[var(--brand-primary)] px-4 py-2 text-sm font-semibold text-white hover:opacity-90">Try again</a>
            </div>
        @else
            <div class="rounded-xl border border-line bg-white shadow-sm p-6">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="font-mono text-lg font-semibold text-ink-900">{{ $shipment->tracking_number }}</p>
                        <p class="mt-1 text-sm text-ink-500">
                            {{ $shipment->originCity?->name ?? 'Origin' }}
                            <span class="mx-1">→</span>
                            {{ $shipment->destinationCity?->name ?? 'Destination' }}
                        </p>
                    </div>
                    <span class="inline-flex shrink-0 items-center rounded-full px-3 py-1 text-sm font-medium
                        {{ $shipment->current_status === 'delivered' ? 'bg-status-delivered/10 text-status-delivered' : ($shipment->current_status === 'exception' ? 'bg-status-exception/10 text-status-exception' : 'bg-[var(--brand-primary)]/10 text-[var(--brand-primary)]') }}">
                        {{ $statusLabels[$shipment->current_status] ?? ucfirst(str_replace('_', ' ', $shipment->current_status)) }}
                    </span>
                </div>

                @if ($shipment->promised_delivery_at && $shipment->current_status !== 'delivered')
                    <p class="mt-3 text-sm text-ink-500">Estimated delivery: <span class="font-medium text-ink-900">{{ $shipment->promised_delivery_at->format('d M Y') }}</span></p>
                @endif
                @if ($shipment->delivered_at)
                    <p class="mt-3 text-sm text-ink-500">Delivered <span class="font-medium text-ink-900">{{ $shipment->delivered_at->format('d M Y, H:i') }}</span></p>
                @endif
            </div>

            <div class="mt-6 rounded-xl border border-line bg-white shadow-sm p-6">
                <p class="mb-4 text-sm font-semibold text-ink-900">Tracking history</p>

                @if ($shipment->scanEvents->isEmpty())
                    <p class="text-sm text-ink-500">No scan history yet — this shipment has just been booked.</p>
                @else
                    <div class="space-y-5">
                        @foreach ($shipment->scanEvents->reverse() as $event)
                            <div class="flex gap-3">
                                <div class="flex flex-col items-center">
                                    <span class="h-2.5 w-2.5 rounded-full {{ $loop->first ? 'bg-[var(--brand-primary)]' : 'bg-line' }}"></span>
                                    @if (! $loop->last)
                                        <span class="mt-1 w-px flex-1 bg-line"></span>
                                    @endif
                                </div>
                                <div class="pb-1">
                                    <p class="text-sm font-medium text-ink-900">{{ $statusLabels[$event->status] ?? ucfirst(str_replace('_', ' ', $event->status)) }}</p>
                                    <p class="text-xs text-ink-500">
                                        {{ $event->outlet?->name ?? $event->hub?->name ?? '' }}
                                        {{ $event->scanned_at?->format('d M Y, H:i') }}
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif

    </div>
</body>
</html>
