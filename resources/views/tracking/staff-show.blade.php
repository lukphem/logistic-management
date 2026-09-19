<x-layouts.app :title="$trackingNumber">

    <div class="mb-6 flex items-center gap-3">
        @if ($back)
            <a href="{{ route('staff-tracking.multi', ['numbers' => $back]) }}" class="text-sm text-[var(--brand-primary)] hover:underline">← Back to results</a>
        @else
            <a href="{{ route('staff-tracking.search') }}" class="text-sm text-[var(--brand-primary)] hover:underline">← Track another number</a>
        @endif
    </div>

    @if (! $shipment)
        <div class="rounded-xl border border-line bg-surface-0 shadow-sm p-8 text-center">
            <p class="text-lg font-semibold text-ink-900">No shipment found</p>
            <p class="mt-1 text-sm text-ink-500">Nothing for tracking number <span class="font-mono">{{ $trackingNumber }}</span>.</p>
        </div>
    @else
        <div class="rounded-xl border border-line bg-surface-0 shadow-sm p-6">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="font-mono text-lg font-semibold text-ink-900">{{ $shipment->tracking_number }}</p>
                    <p class="mt-1 text-sm text-ink-500">
                        {{ $shipment->originCity?->name ?? 'Origin' }}
                        <span class="mx-1">→</span>
                        {{ $shipment->destinationCity?->name ?? 'Destination' }}
                        @if ($shipment->serviceType)
                            · {{ $shipment->serviceType->name }}
                        @endif
                    </p>
                </div>
                <span class="inline-flex shrink-0 items-center rounded-full px-3 py-1 text-sm font-medium
                    {{ $shipment->current_status === 'delivered' ? 'bg-status-delivered/10 text-status-delivered' : ($shipment->current_status === 'exception' ? 'bg-status-exception/10 text-status-exception' : 'bg-[var(--brand-primary)]/10 text-[var(--brand-primary)]') }}">
                    {{ $statusLabels[$shipment->current_status] ?? ucfirst(str_replace('_', ' ', $shipment->current_status)) }}
                </span>
            </div>

            <div class="mt-4 grid grid-cols-2 gap-4 border-t border-line pt-4 text-sm sm:grid-cols-3">
                <div>
                    <p class="text-xs text-ink-500">Receiver</p>
                    <p class="font-medium text-ink-900">{{ $shipment->receiver_name }}</p>
                </div>
                @if ($shipment->assignedRider)
                    <div>
                        <p class="text-xs text-ink-500">Assigned to</p>
                        <p class="font-medium text-ink-900">{{ $shipment->assignedRider->name }}</p>
                    </div>
                @endif
                @if ($shipment->promised_delivery_at && $shipment->current_status !== 'delivered')
                    <div>
                        <p class="text-xs text-ink-500">Estimated delivery</p>
                        <p class="font-medium text-ink-900">{{ $shipment->promised_delivery_at->format('d M Y') }}</p>
                    </div>
                @endif
                @if ($shipment->delivered_at)
                    <div>
                        <p class="text-xs text-ink-500">Delivered</p>
                        <p class="font-medium text-ink-900">{{ $shipment->delivered_at->format('d M Y, H:i') }}</p>
                    </div>
                @endif
                @if ($lastScan)
                    <div>
                        <p class="text-xs text-ink-500">Last scan</p>
                        <p class="font-medium text-ink-900">{{ $lastScan['date']?->format('d M Y, H:i') }}</p>
                        @if ($lastScan['location'])
                            <p class="text-xs text-ink-500">{{ $lastScan['location'] }}</p>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        <div class="mt-6 rounded-xl border border-line bg-surface-0 shadow-sm p-6">
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
                                <p class="mt-0.5 text-xs text-[var(--brand-primary)]">
                                    Scanned by {{ $event->handler?->name ?? 'Unknown staff' }}
                                    @if ($event->handedTo)
                                        · Handed to {{ $event->handedTo->name }}
                                    @endif
                                    @if ($event->destinationHub)
                                        · Heading to {{ $event->destinationHub->name }}
                                    @endif
                                </p>
                                @if ($event->receiver_name)
                                    <p class="text-xs text-ink-500">Received by: {{ $event->receiver_name }}</p>
                                @endif
                                @if ($event->photo_path || $event->signature_path)
                                    <p class="mt-0.5 text-xs text-ink-500">
                                        Evidence:
                                        @if ($event->photo_path)
                                            <a href="{{ asset('storage/' . $event->photo_path) }}" target="_blank" class="text-[var(--brand-primary)] hover:underline">photo</a>
                                        @endif
                                        @if ($event->photo_path && $event->signature_path)
                                            ·
                                        @endif
                                        @if ($event->signature_path)
                                            <a href="{{ asset('storage/' . $event->signature_path) }}" target="_blank" class="text-[var(--brand-primary)] hover:underline">signature</a>
                                        @endif
                                    </p>
                                @endif
                            </div>
                        </div>
                    @endforeach

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
            @endif
        </div>
    @endif

</x-layouts.app>
