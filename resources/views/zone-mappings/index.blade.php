<x-layouts.app :title="'Zone Mapping'">

    @if (session('status'))
        <div class="mb-5 rounded-xl bg-status-delivered/10 px-4 py-3 text-sm font-medium text-status-delivered">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-5 rounded-xl bg-status-exception/10 px-4 py-3 text-sm text-status-exception">
            {{ $errors->first() }}
        </div>
    @endif

    {{-- Domestic Mapping --}}
    <div class="mb-5 flex items-center justify-between">
        <h2 class="text-sm font-semibold text-ink-900">Domestic Mapping</h2>
        <form method="POST" action="{{ route('zone-mappings.generate-domestic') }}">
            @csrf
            <button type="submit" class="rounded-md bg-[var(--brand-primary)] px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:opacity-90 hover:shadow-md">
                Generate Nigeria combinations
            </button>
        </form>
    </div>

    <x-csv-actions :export-route="route('zone-mappings.export-domestic')" :import-route="route('zone-mappings.import-domestic')" label="Domestic Mapping" />

    <div class="overflow-x-auto rounded-xl border border-line bg-surface-0 shadow-sm">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="border-b border-line text-xs uppercase tracking-wide text-ink-500">
                    <th class="px-5 py-3 font-medium">State A</th>
                    <th class="px-5 py-3 font-medium">State B</th>
                    <th class="px-5 py-3 font-medium">Same territory</th>
                    <th class="px-5 py-3 font-medium">Airport</th>
                    <th class="px-5 py-3 font-medium">Zone</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($domesticMappings as $mapping)
                    @php
                        $sameState = $mapping->stateA->id === $mapping->stateB->id;
                        $sameTerritory = ! $sameState && $mapping->stateA->territory_id && $mapping->stateA->territory_id === $mapping->stateB->territory_id;
                        $aHasAirport = $mapping->stateA->has_airport;
                        $bHasAirport = $mapping->stateB->has_airport;
                    @endphp
                    <tr class="border-b border-line last:border-0 odd:bg-surface-0 even:bg-surface-50/50 hover:bg-[var(--brand-primary)]/5 transition-colors">
                        <td class="px-5 py-3 font-medium text-ink-900">{{ $mapping->stateA->name }}</td>
                        <td class="px-5 py-3 font-medium text-ink-900">{{ $mapping->stateB->name }}</td>
                        <td class="px-5 py-3 text-ink-500">
                            @if ($sameState)
                                <span class="text-ink-500">Same state</span>
                            @elseif ($sameTerritory)
                                <span class="inline-flex items-center rounded-full bg-status-delivered/10 px-2.5 py-0.5 text-xs font-medium text-status-delivered">Yes</span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-ink-500/10 px-2.5 py-0.5 text-xs font-medium text-ink-500">No</span>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-ink-500">
                            @if ($sameState)
                                {{ $aHasAirport ? 'Yes' : 'No' }}
                            @elseif ($aHasAirport && $bHasAirport)
                                Both
                            @elseif ($aHasAirport)
                                {{ $mapping->stateA->name }} only
                            @elseif ($bHasAirport)
                                {{ $mapping->stateB->name }} only
                            @else
                                Neither
                            @endif
                        </td>
                        <td class="px-5 py-3">
                            <form method="POST" action="{{ route('zone-mappings.update-zone', $mapping) }}">
                                @csrf @method('PATCH')
                                <select name="zone_id" onchange="this.form.submit()"
                                        class="rounded-md border border-line bg-surface-0 px-2 py-1.5 text-sm text-ink-900 outline-none focus:border-[var(--brand-primary)]">
                                    <option value="">Unassigned</option>
                                    @foreach ($zones as $zone)
                                        <option value="{{ $zone->id }}" @selected($mapping->zone_id === $zone->id)>{{ $zone->name }}</option>
                                    @endforeach
                                </select>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-5 py-8 text-center text-sm text-ink-500">No combinations generated yet — click "Generate Nigeria combinations" above.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-5">{{ $domesticMappings->links() }}</div>

    {{-- International Mapping --}}
    <div class="mb-5 mt-10 flex items-center justify-between">
        <h2 class="text-sm font-semibold text-ink-900">International Mapping</h2>
        <form method="POST" action="{{ route('zone-mappings.generate-international') }}">
            @csrf
            <button type="submit" class="rounded-md bg-[var(--brand-primary)] px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:opacity-90 hover:shadow-md">
                Generate international countries
            </button>
        </form>
    </div>

    <x-csv-actions :export-route="route('zone-mappings.export-international')" :import-route="route('zone-mappings.import-international')" label="International Mapping" />

    <div class="overflow-x-auto rounded-xl border border-line bg-surface-0 shadow-sm">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="border-b border-line text-xs uppercase tracking-wide text-ink-500">
                    <th class="px-5 py-3 font-medium">Country</th>
                    <th class="px-5 py-3 font-medium">Zone</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($internationalMappings as $mapping)
                    <tr class="border-b border-line last:border-0 odd:bg-surface-0 even:bg-surface-50/50 hover:bg-[var(--brand-primary)]/5 transition-colors">
                        <td class="px-5 py-3 font-medium text-ink-900">{{ $mapping->country->name }}</td>
                        <td class="px-5 py-3">
                            <form method="POST" action="{{ route('zone-mappings.update-country-zone', $mapping) }}">
                                @csrf @method('PATCH')
                                <select name="zone_id" onchange="this.form.submit()"
                                        class="rounded-md border border-line bg-surface-0 px-2 py-1.5 text-sm text-ink-900 outline-none focus:border-[var(--brand-primary)]">
                                    <option value="">Unassigned</option>
                                    @foreach ($zones as $zone)
                                        <option value="{{ $zone->id }}" @selected($mapping->zone_id === $zone->id)>{{ $zone->name }}</option>
                                    @endforeach
                                </select>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="2" class="px-5 py-8 text-center text-sm text-ink-500">No countries generated yet — click "Generate international countries" above.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-5">{{ $internationalMappings->links() }}</div>

</x-layouts.app>
