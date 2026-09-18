<x-layouts.app :title="'Manifest Trips'">

    <div class="mb-6 flex items-center justify-between">
        <div>
            <p class="text-2xl font-semibold text-ink-900">Manifest Trips</p>
            <p class="mt-1 text-sm text-ink-500">Vehicle journeys — each carrying one or more destination manifests.</p>
        </div>
        @can('manifests:create')
            <a href="{{ route('manifest-trips.create') }}" class="rounded-md bg-[var(--brand-primary)] px-4 py-2 text-sm font-semibold text-white hover:opacity-90">New trip</a>
        @endcan
    </div>

    <div class="rounded-xl border border-line bg-surface-0 shadow-sm overflow-hidden">
        @if ($trips->isEmpty())
            <p class="p-6 text-sm text-ink-500">No manifest trips yet.</p>
        @else
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-line text-left text-xs uppercase tracking-wide text-ink-500">
                        <th class="p-3">Trip</th>
                        <th class="p-3">Origin</th>
                        <th class="p-3">Destinations</th>
                        <th class="p-3">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($trips as $trip)
                        <tr class="border-b border-line last:border-0">
                            <td class="p-3">
                                <a href="{{ route('manifest-trips.show', $trip) }}" class="font-mono text-[var(--brand-primary)] hover:underline">{{ $trip->trip_number }}</a>
                            </td>
                            <td class="p-3 text-ink-700">{{ $trip->originHub?->name ?? $trip->originOutlet?->name ?? '—' }}</td>
                            <td class="p-3 text-ink-700">
                                @foreach ($trip->manifests as $manifest)
                                    <span class="inline-block">{{ $manifest->destinationHub?->code ?? '—' }}</span>
                                    @if (! $loop->last)
                                        ,
                                    @endif
                                @endforeach
                            </td>
                            <td class="p-3">
                                @if ($trip->isDispatched())
                                    <span class="text-status-delivered">Dispatched</span>
                                @else
                                    <span class="text-ink-500">Draft</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div class="mt-4">
        {{ $trips->links() }}
    </div>

</x-layouts.app>
