<x-layouts.app :title="'Dashboard'">

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @php
            $cards = [
                ['label' => 'Total Shipments', 'value' => $stats['total_shipments'], 'accent' => 'brand'],
                ['label' => 'In Transit', 'value' => $stats['in_transit'], 'accent' => 'transit'],
                ['label' => 'Delivered Today', 'value' => $stats['delivered_today'], 'accent' => 'delivered'],
                ['label' => 'Exceptions', 'value' => $stats['exceptions'], 'accent' => 'exception'],
            ];
        @endphp

        @php
            $accentStyles = [
                'brand' => ['border' => 'border-l-[var(--brand-primary)]', 'text' => 'text-[var(--brand-primary)]'],
                'transit' => ['border' => 'border-l-status-transit', 'text' => 'text-status-transit'],
                'delivered' => ['border' => 'border-l-status-delivered', 'text' => 'text-status-delivered'],
                'exception' => ['border' => 'border-l-status-exception', 'text' => 'text-status-exception'],
            ];
        @endphp

        @foreach ($cards as $card)
            @php $accent = $accentStyles[$card['accent']]; @endphp
            <div class="rounded-xl border border-line {{ $accent['border'] }} border-l-4 bg-surface-0 p-5 shadow-sm transition-shadow hover:shadow-md">
                <p class="text-sm font-medium text-ink-500">{{ $card['label'] }}</p>
                <p class="mt-2 font-mono text-3xl font-semibold {{ $accent['text'] }}">{{ number_format($card['value']) }}</p>
            </div>
        @endforeach
    </div>

    <div class="mt-8 rounded-xl border border-line bg-surface-0 shadow-sm">
        <div class="flex items-center justify-between border-b border-line px-5 py-4">
            <h2 class="text-sm font-semibold text-ink-900">Recent shipments</h2>
            <a href="{{ route('shipments.index') }}" class="text-sm font-medium text-[var(--brand-primary)] hover:underline">View all</a>
        </div>

        <table class="w-full text-left text-sm">
            <thead>
                <tr class="border-b border-line text-xs uppercase tracking-wide text-ink-500">
                    <th class="px-5 py-3 font-medium">Tracking No.</th>
                    <th class="px-5 py-3 font-medium">Service</th>
                    <th class="px-5 py-3 font-medium">Status</th>
                    <th class="px-5 py-3 font-medium">Rider</th>
                    <th class="px-5 py-3 font-medium">Booked</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($recentShipments as $shipment)
                    <tr class="border-b border-line last:border-0 odd:bg-surface-0 even:bg-surface-50/50 hover:bg-[var(--brand-primary)]/5 transition-colors">
                        <td class="px-5 py-3">
                            <a href="{{ route('shipments.show', $shipment) }}" class="font-mono text-sm text-[var(--brand-primary)] hover:underline">
                                {{ $shipment->tracking_number }}
                            </a>
                        </td>
                        <td class="px-5 py-3 text-ink-900">{{ $shipment->serviceType->name ?? '—' }}</td>
                        <td class="px-5 py-3"><x-status-pill :status="$shipment->current_status" /></td>
                        <td class="px-5 py-3 text-ink-500">{{ $shipment->assignedRider?->name ?? '—' }}</td>
                        <td class="px-5 py-3 text-ink-500">{{ $shipment->created_at->format('d M, H:i') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-5 py-8 text-center text-sm text-ink-500">No shipments booked yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</x-layouts.app>
