<x-layouts.app :title="'Zones & Regions'">

    @if (session('status'))
        <div class="mb-5 rounded-md bg-status-delivered/10 px-4 py-3 text-sm font-medium text-status-delivered">
            {{ session('status') }}
        </div>
    @endif

    <x-csv-actions :export-route="route('zones.export')" :import-route="route('zones.import')" label="Zones" />

    <div class="mb-5 flex items-center justify-between">
        <p class="text-sm text-ink-500">Zones drive zone-to-zone rate matrices and rider geofencing.</p>
        <a href="{{ route('zones.create') }}" class="rounded-md bg-[var(--brand-primary)] px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:opacity-90 hover:shadow-md">
            + Add zone
        </a>
    </div>

    <div class="overflow-x-auto rounded-xl border border-line bg-surface-0 shadow-sm">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="border-b border-line text-xs uppercase tracking-wide text-ink-500">
                    <th class="px-5 py-3 font-medium">Name</th>
                    <th class="px-5 py-3 font-medium">Code</th>
                    <th class="px-5 py-3 font-medium">Applies to</th>
                    <th class="px-5 py-3 font-medium">Tier</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($zones as $zone)
                    <tr class="border-b border-line last:border-0 odd:bg-surface-0 even:bg-surface-50/50 hover:bg-[var(--brand-primary)]/5 transition-colors">
                        <td class="px-5 py-3 font-medium text-ink-900">{{ $zone->name }}</td>
                        <td class="px-5 py-3 font-mono text-ink-500">{{ $zone->code }}</td>
                        <td class="px-5 py-3">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $zone->applies_domestic && $zone->applies_international ? 'bg-status-delivered/10 text-status-delivered' : ($zone->applies_international ? 'bg-status-transit/10 text-status-transit' : 'bg-ink-500/10 text-ink-500') }}">
                                {{ $zone->applicabilityLabel() }}
                            </span>
                        </td>
                        <td class="px-5 py-3">
                            @if ($zone->tier)
                                <span class="inline-flex items-center rounded-full bg-[var(--brand-primary)]/10 px-2.5 py-0.5 text-xs font-medium text-[var(--brand-primary)]" title="{{ $zone->tierPurpose() }}">
                                    {{ $zone->tierLabel() }}
                                </span>
                            @else
                                <span class="text-ink-500">—</span>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-right">
                            <a href="{{ route('zones.edit', $zone) }}" class="text-sm font-medium text-[var(--brand-primary)] hover:underline">Edit</a>
                            <form method="POST" action="{{ route('zones.destroy', $zone) }}" class="inline" onsubmit="return confirm('Remove this zone?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="ml-3 text-sm font-medium text-status-exception transition-colors hover:text-status-exception/70">Remove</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-5 py-8 text-center text-sm text-ink-500">No zones configured yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-5">{{ $zones->links() }}</div>

</x-layouts.app>
