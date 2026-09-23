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
                <a href="{{ route('tracking.search') }}" class="ml-auto text-sm text-[var(--brand-primary)] hover:underline">Track another shipment</a>
            @endif
        </div>

        @if (! $shipment)
            <div class="rounded-xl border border-line bg-white shadow-sm p-8 text-center">
                <p class="text-lg font-semibold text-ink-900">No shipment found</p>
                <p class="mt-1 text-sm text-ink-500">We couldn't find anything for tracking number <span class="font-mono">{{ $trackingNumber }}</span>. Double-check the number and try again.</p>
                <a href="{{ route('tracking.search') }}" class="mt-4 inline-block rounded-md bg-[var(--brand-primary)] px-4 py-2 text-sm font-semibold text-white hover:opacity-90">Try again</a>
            </div>
        @elseif ($shipment->isPaymentPending())
            {{-- Deliberately doesn't reveal the shipment's real
                 details or full tracking number here — a shipment
                 booked for online payment that hasn't actually been
                 paid for yet shouldn't be traceable as if it were a
                 real, in-progress shipment; that's exactly the kind
                 of "proof" someone could otherwise walk away with
                 without ever paying. --}}
            <div class="rounded-xl border border-line bg-white shadow-sm p-8 text-center">
                <p class="text-lg font-semibold text-ink-900">Payment pending</p>
                <p class="mt-1 text-sm text-ink-500">This shipment (<span class="font-mono">{{ substr($trackingNumber, 0, 4) }}{{ str_repeat('•', max(strlen($trackingNumber) - 6, 3)) }}{{ substr($trackingNumber, -2) }}</span>) is awaiting online payment and hasn't started moving yet.</p>
                <a href="{{ route('tracking.search') }}" class="mt-4 inline-block rounded-md bg-[var(--brand-primary)] px-4 py-2 text-sm font-semibold text-white hover:opacity-90">Track another shipment</a>
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
                @if ($lastScan)
                    <p class="mt-3 text-sm text-ink-500">
                        Last updated <span class="font-medium text-ink-900">{{ $lastScan['date']?->format('d M Y, H:i') }}</span>
                        @if ($lastScan['location'])
                            — <span class="font-medium text-ink-900">{{ $lastScan['location'] }}</span>
                        @endif
                    </p>
                @endif
            </div>

            <div class="mt-6 rounded-xl border border-line bg-white shadow-sm p-6">
                <p class="mb-4 text-sm font-semibold text-ink-900">Tracking history</p>

                <div class="space-y-5">
                    @foreach ($shipment->scanEvents->reverse() as $event)
                        <div class="flex gap-3">
                            <div class="flex flex-col items-center">
                                <span class="h-2.5 w-2.5 rounded-full {{ $loop->first ? 'bg-[var(--brand-primary)]' : 'bg-line' }}"></span>
                                <span class="mt-1 w-px flex-1 bg-line"></span>
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

                    {{-- Booking itself is never a ScanEvent (it happens at
                         creation, not a scan) — shown here from the
                         shipment's own created_at so the timeline always
                         starts at the true beginning, not just wherever
                         the first physical scan happened to occur. --}}
                    <div class="flex gap-3">
                        <div class="flex flex-col items-center">
                            <span class="h-2.5 w-2.5 rounded-full {{ $shipment->scanEvents->isEmpty() ? 'bg-[var(--brand-primary)]' : 'bg-line' }}"></span>
                        </div>
                        <div class="pb-1">
                            <p class="text-sm font-medium text-ink-900">Booked</p>
                            <p class="text-xs text-ink-500">{{ $shipment->created_at->format('d M Y, H:i') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        @endif

    </div>
</body>
</html>
