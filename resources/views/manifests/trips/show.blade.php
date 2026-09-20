<x-layouts.app :title="'Trip ' . $trip->trip_number">

    @if (session('status'))
        <div class="mb-4 rounded-xl border border-status-delivered/30 bg-status-delivered/5 px-4 py-3 text-sm text-status-delivered">
            {{ session('status') }}
        </div>
    @endif
    @if ($errors->any())
        <div class="mb-4 rounded-xl border border-status-exception/30 bg-status-exception/5 px-4 py-3 text-sm text-status-exception">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="mb-6 flex items-start justify-between">
        <div>
            <p class="font-mono text-2xl font-semibold text-ink-900">{{ $trip->trip_number }}</p>
            <p class="mt-1 text-sm text-ink-500">
                {{ $trip->originHub?->name ?? $trip->originOutlet?->name ?? 'Origin not set' }}
                · {{ ucfirst($trip->transport_mode) }}
                · {{ $trip->carrier_type === 'third_party' ? $trip->carrier_name : 'Company vehicle' }}
                @if ($trip->vehicle_identifier)
                    · {{ $trip->vehicle_identifier }}
                @endif
            </p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('manifest-trips.print', $trip) }}" target="_blank" class="rounded-md border border-line px-3 py-1.5 text-sm font-medium text-ink-700 hover:bg-surface-50">🖨️ Print</a>
            @if ($trip->isDispatched())
                <span class="inline-flex items-center rounded-full bg-status-delivered/10 px-3 py-1 text-sm font-medium text-status-delivered">Dispatched</span>
            @else
                <span class="inline-flex items-center rounded-full bg-ink-100 px-3 py-1 text-sm font-medium text-ink-700">Draft</span>
                <a href="{{ route('manifests.create', $trip) }}" class="rounded-md border border-line px-3 py-1.5 text-sm font-medium text-ink-700 hover:bg-surface-50">+ Add destination</a>
                <form method="POST" action="{{ route('manifest-trips.dispatch', $trip) }}" class="inline" onsubmit="return confirm('Dispatch this trip? All manifests inside it will lock and every shipment will be scanned in transit.');">
                    @csrf
                    <button type="submit" class="rounded-md bg-[var(--brand-primary)] px-3 py-1.5 text-sm font-medium text-white hover:opacity-90">Dispatch trip</button>
                </form>
            @endif
        </div>
    </div>

    @if ($trip->driver_name || $trip->driver_phone)
        <p class="mb-4 text-sm text-ink-500">Driver: {{ $trip->driver_name }} {{ $trip->driver_phone ? '· ' . $trip->driver_phone : '' }}</p>
    @endif
    @if ($trip->notes)
        <p class="mb-4 text-sm text-ink-500">{{ $trip->notes }}</p>
    @endif

    <p class="mb-3 text-sm font-semibold text-ink-900">Manifests on this trip</p>

    <div class="space-y-3">
        @forelse ($trip->manifests as $manifest)
            <div class="rounded-xl border border-line bg-surface-0 shadow-sm p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-mono text-sm font-semibold text-ink-900">{{ $manifest->manifest_number }}</p>
                        <p class="text-xs text-ink-500">
                            → {{ $manifest->destinationHub?->name ?? $manifest->destinationOutlet?->name ?? '—' }}
                            · {{ $manifest->manifestShipments->count() }} shipment(s)
                            @if ($manifest->estimated_arrival_at)
                                · ETA {{ $manifest->estimated_arrival_at->format('d M Y, H:i') }}
                            @endif
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('manifests.print', $manifest) }}" target="_blank" class="rounded-md border border-line px-3 py-1.5 text-xs font-medium text-ink-700 hover:bg-surface-50">🖨️ Print</a>
                        @if ($manifest->status === 'draft')
                            <span class="inline-flex items-center rounded-full bg-ink-100 px-2.5 py-1 text-xs font-medium text-ink-700">Draft</span>
                            <a href="{{ route('manifests.edit', $manifest) }}" class="rounded-md border border-line px-3 py-1.5 text-xs font-medium text-ink-700 hover:bg-surface-50">Edit</a>
                        @elseif ($manifest->status === 'dispatched')
                            <span class="inline-flex items-center rounded-full bg-[var(--brand-primary)]/10 px-2.5 py-1 text-xs font-medium text-[var(--brand-primary)]">In transit</span>
                            <a href="{{ route('manifests.receive', $manifest) }}" class="rounded-md border border-line px-3 py-1.5 text-xs font-medium text-ink-700 hover:bg-surface-50">Receive</a>
                        @else
                            <span class="inline-flex items-center rounded-full {{ $manifest->hasDiscrepancy() ? 'bg-status-exception/10 text-status-exception' : 'bg-status-delivered/10 text-status-delivered' }} px-2.5 py-1 text-xs font-medium">
                                {{ $manifest->hasDiscrepancy() ? 'Received — discrepancy' : 'Received' }}
                            </span>
                        @endif
                    </div>
                </div>
                @if ($manifest->discrepancy_notes)
                    <p class="mt-2 text-xs text-status-exception">{{ $manifest->discrepancy_notes }}</p>
                @endif
            </div>
        @empty
            <p class="text-sm text-ink-500">No manifests on this trip yet.</p>
        @endforelse
    </div>

</x-layouts.app>
