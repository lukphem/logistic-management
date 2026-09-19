<x-layouts.app :title="'Tracking results'">

    <div class="mb-6 flex items-center justify-between">
        <p class="text-2xl font-semibold text-ink-900">Tracking results</p>
        <a href="{{ route('staff-tracking.search') }}" class="text-sm text-[var(--brand-primary)] hover:underline">Track more numbers</a>
    </div>

    <p class="mb-4 text-sm text-ink-500">{{ $results->count() }} number(s) checked</p>

    <div class="rounded-xl border border-line bg-surface-0 shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-line text-left text-xs uppercase tracking-wide text-ink-500">
                    <th class="p-3">Number</th>
                    <th class="p-3">Kind</th>
                    <th class="p-3">Receiver</th>
                    <th class="p-3">Status</th>
                    <th class="p-3">Last scan</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($results as $result)
                    <tr class="border-b border-line last:border-0">
                        <td class="p-3">
                            @if ($result['found'])
                                <a href="{{ route('staff-tracking.show', [$result['number'], 'back' => $numbersParam]) }}" class="font-mono text-[var(--brand-primary)] hover:underline">{{ $result['number'] }}</a>
                            @else
                                <span class="font-mono text-ink-500">{{ $result['number'] }}</span>
                            @endif
                        </td>
                        <td class="p-3 text-ink-700">{{ ucfirst($result['kind']) }}</td>
                        <td class="p-3 text-ink-700">{{ $result['receiver_name'] ?? '—' }}</td>
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
                        <td class="p-3 text-ink-700">
                            @if (! empty($result['last_scan_date']))
                                {{ $result['last_scan_date']->format('d M, H:i') }}
                                @if (! empty($result['last_scan_location']))
                                    <span class="block text-xs text-ink-500">{{ $result['last_scan_location'] }}</span>
                                @endif
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

</x-layouts.app>
