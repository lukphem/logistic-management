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

    <div class="mb-5 flex gap-2 border-b border-line">
        <button type="button" id="tab-btn-international" onclick="showZoneTab('international')"
                class="border-b-2 border-[var(--brand-primary)] px-1 pb-3 text-sm font-semibold text-ink-900">
            International
        </button>
        <button type="button" id="tab-btn-domestic" onclick="showZoneTab('domestic')"
                class="border-b-2 border-transparent px-1 pb-3 text-sm font-medium text-ink-500 hover:text-ink-900">
            Domestic
        </button>
    </div>

    <div id="tab-domestic" style="display:none">

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

    <details class="mb-5 rounded-xl border border-line bg-surface-0 shadow-sm">
        <summary class="cursor-pointer px-5 py-3 text-sm font-semibold text-ink-900">Apply a rule to every pair</summary>
        <div class="border-t border-line p-5">
            <p class="mb-4 text-xs text-ink-500">
                Sets a zone for every domestic pair at once based on the conditions below — <strong>overwrites every
                existing assignment</strong>, including manual ones. Use it as a reset, then adjust individual rows
                afterward with the picker in the table below if any need to differ from the rule.
            </p>

            <form method="POST" action="{{ route('zone-mappings.apply-domestic-rule') }}" onsubmit="return confirm('This overwrites the zone on every domestic pair, including any already set manually. Continue?')">
                @csrf
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="flex items-center justify-between gap-3 rounded-lg border border-line p-3">
                        <span class="text-sm text-ink-900">Same state</span>
                        <select name="zone_same_state" required class="rounded-md border border-line bg-surface-0 px-2 py-1.5 text-sm text-ink-900 outline-none focus:border-[var(--brand-primary)]">
                            @foreach ($domesticZones as $zone)
                                <option value="{{ $zone->id }}" @selected($zone->code === 'Z1')>{{ $zone->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-center justify-between gap-3 rounded-lg border border-line p-3">
                        <span class="text-sm text-ink-900">Same territory</span>
                        <select name="zone_same_territory" required class="rounded-md border border-line bg-surface-0 px-2 py-1.5 text-sm text-ink-900 outline-none focus:border-[var(--brand-primary)]">
                            @foreach ($domesticZones as $zone)
                                <option value="{{ $zone->id }}" @selected($zone->code === 'Z2')>{{ $zone->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-center justify-between gap-3 rounded-lg border border-line p-3">
                        <span class="text-sm text-ink-900">Different territory, airport condition met</span>
                        <select name="zone_airport_condition_met" required class="rounded-md border border-line bg-surface-0 px-2 py-1.5 text-sm text-ink-900 outline-none focus:border-[var(--brand-primary)]">
                            @foreach ($domesticZones as $zone)
                                <option value="{{ $zone->id }}" @selected($zone->code === 'Z3')>{{ $zone->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-center justify-between gap-3 rounded-lg border border-line p-3">
                        <span class="text-sm text-ink-900">Different territory, airport condition not met</span>
                        <select name="zone_airport_condition_not_met" required class="rounded-md border border-line bg-surface-0 px-2 py-1.5 text-sm text-ink-900 outline-none focus:border-[var(--brand-primary)]">
                            @foreach ($domesticZones as $zone)
                                <option value="{{ $zone->id }}" @selected($zone->code === 'Z4')>{{ $zone->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="mt-4">
                    <span class="mb-2 block text-sm font-medium text-ink-900">Airport condition</span>
                    <div class="flex gap-3">
                        <label class="flex flex-1 cursor-pointer items-center gap-2 rounded-lg border border-line p-3 text-sm text-ink-900 has-[:checked]:border-[var(--brand-primary)] has-[:checked]:bg-[var(--brand-primary)]/5">
                            <input type="radio" name="airport_condition" value="both" checked class="rounded-full border-line">
                            Both states need an airport
                        </label>
                        <label class="flex flex-1 cursor-pointer items-center gap-2 rounded-lg border border-line p-3 text-sm text-ink-900 has-[:checked]:border-[var(--brand-primary)] has-[:checked]:bg-[var(--brand-primary)]/5">
                            <input type="radio" name="airport_condition" value="either" class="rounded-full border-line">
                            Either state having one is enough
                        </label>
                    </div>
                </div>

                <div class="mt-4 flex justify-end">
                    <button type="submit" class="rounded-md bg-[var(--brand-primary)] px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:opacity-90 hover:shadow-md">
                        Apply to all domestic pairs
                    </button>
                </div>
            </form>
        </div>
    </details>

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
                                    @foreach ($domesticZones as $zone)
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

    </div>{{-- /#tab-domestic --}}

    <div id="tab-international">

    <div class="mb-5 flex gap-2">
        <button type="button" id="intl-subtab-btn-nigeria" onclick="showInternationalSubTab('nigeria')"
                class="rounded-full bg-[var(--brand-primary)]/10 px-3 py-1.5 text-xs font-semibold text-[var(--brand-primary)]">
            Nigeria Routes
        </button>
        <button type="button" id="intl-subtab-btn-crosstrade" onclick="showInternationalSubTab('crosstrade')"
                class="rounded-full px-3 py-1.5 text-xs font-medium text-ink-500 hover:bg-surface-50">
            Cross-Trade (Third-Country Shipping)
        </button>
    </div>

    <div id="intl-sub-nigeria">

    {{-- International Mapping --}}
    <div class="mb-5 flex items-center justify-between">
        <h2 class="text-sm font-semibold text-ink-900">International Mapping</h2>
        <form method="POST" action="{{ route('zone-mappings.generate-international') }}">
            @csrf
            <button type="submit" class="rounded-md bg-[var(--brand-primary)] px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:opacity-90 hover:shadow-md">
                Generate international countries
            </button>
        </form>
    </div>

    <x-csv-actions :export-route="route('zone-mappings.export-international')" :import-route="route('zone-mappings.import-international')" label="International Mapping" />

    <details class="mb-5 rounded-xl border border-line bg-surface-0 shadow-sm">
        <summary class="cursor-pointer px-5 py-3 text-sm font-semibold text-ink-900">Apply a rule to every country</summary>
        <div class="border-t border-line p-5">
            <p class="mb-4 text-xs text-ink-500">
                Sets a zone for every country at once, comparing each one against Nigeria specifically (the fixed
                origin side for international shipments) — <strong>overwrites every existing assignment</strong>,
                including manual ones. Regions are managed under
                <a href="{{ route('country-regions.index') }}" class="text-[var(--brand-primary)] hover:underline">Setups → Location → Country Regions</a>
                — yours to name however makes sense, standard geography or otherwise.
            </p>

            <form method="POST" action="{{ route('zone-mappings.apply-international-rule') }}" onsubmit="return confirm('This overwrites the zone on every country, including any already set manually. Continue?')">
                @csrf
                <div class="mb-4">
                    <span class="mb-2 block text-sm font-medium text-ink-900">Grouping method</span>
                    <div class="flex gap-3">
                        <label class="flex flex-1 cursor-pointer items-center gap-2 rounded-lg border border-line p-3 text-sm text-ink-900 has-[:checked]:border-[var(--brand-primary)] has-[:checked]:bg-[var(--brand-primary)]/5">
                            <input type="radio" name="grouping_method" value="continent" id="method-continent" class="rounded-full border-line"
                                   onchange="document.getElementById('continent-only-fields').style.display=''; document.getElementById('continent-region-fields').style.display='none';">
                            Continent only
                        </label>
                        <label class="flex flex-1 cursor-pointer items-center gap-2 rounded-lg border border-line p-3 text-sm text-ink-900 has-[:checked]:border-[var(--brand-primary)] has-[:checked]:bg-[var(--brand-primary)]/5">
                            <input type="radio" name="grouping_method" value="continent_region" id="method-continent-region" checked class="rounded-full border-line"
                                   onchange="document.getElementById('continent-only-fields').style.display='none'; document.getElementById('continent-region-fields').style.display='';">
                            Continent + Region <span class="text-xs text-ink-500">(recommended)</span>
                        </label>
                    </div>
                </div>

                <div id="continent-only-fields" style="display:none" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="flex items-center justify-between gap-3 rounded-lg border border-line p-3">
                        <span class="text-sm text-ink-900">Same continent as Nigeria</span>
                        <select name="zone_same_continent" class="rounded-md border border-line bg-surface-0 px-2 py-1.5 text-sm text-ink-900 outline-none focus:border-[var(--brand-primary)]">
                            @foreach ($internationalZones as $zone)
                                <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-center justify-between gap-3 rounded-lg border border-line p-3">
                        <span class="text-sm text-ink-900">Different continent</span>
                        <select name="zone_different_continent" class="rounded-md border border-line bg-surface-0 px-2 py-1.5 text-sm text-ink-900 outline-none focus:border-[var(--brand-primary)]">
                            @foreach ($internationalZones as $zone)
                                <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div id="continent-region-fields" class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div class="flex items-center justify-between gap-3 rounded-lg border border-line p-3">
                        <span class="text-sm text-ink-900">Same region as Nigeria</span>
                        <select name="zone_same_region" class="rounded-md border border-line bg-surface-0 px-2 py-1.5 text-sm text-ink-900 outline-none focus:border-[var(--brand-primary)]">
                            @foreach ($internationalZones as $zone)
                                <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-center justify-between gap-3 rounded-lg border border-line p-3">
                        <span class="text-sm text-ink-900">Same continent, different region</span>
                        <select name="zone_same_continent_different_region" class="rounded-md border border-line bg-surface-0 px-2 py-1.5 text-sm text-ink-900 outline-none focus:border-[var(--brand-primary)]">
                            @foreach ($internationalZones as $zone)
                                <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-center justify-between gap-3 rounded-lg border border-line p-3">
                        <span class="text-sm text-ink-900">Different continent</span>
                        <select name="zone_different_continent" class="rounded-md border border-line bg-surface-0 px-2 py-1.5 text-sm text-ink-900 outline-none focus:border-[var(--brand-primary)]">
                            @foreach ($internationalZones as $zone)
                                <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="mt-4 flex justify-end">
                    <button type="submit" class="rounded-md bg-[var(--brand-primary)] px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:opacity-90 hover:shadow-md">
                        Apply to all countries
                    </button>
                </div>
            </form>
        </div>
    </details>

    <div class="overflow-x-auto rounded-xl border border-line bg-surface-0 shadow-sm">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="border-b border-line text-xs uppercase tracking-wide text-ink-500">
                    <th class="px-5 py-3 font-medium">Country A</th>
                    <th class="px-5 py-3 font-medium">Country B</th>
                    <th class="px-5 py-3 font-medium">Continent</th>
                    <th class="px-5 py-3 font-medium">Region</th>
                    <th class="px-5 py-3 font-medium">Zone</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($internationalMappings as $mapping)
                    <tr class="border-b border-line last:border-0 odd:bg-surface-0 even:bg-surface-50/50 hover:bg-[var(--brand-primary)]/5 transition-colors">
                        <td class="px-5 py-3 font-medium text-ink-900">{{ $mapping->countryA->name }}</td>
                        <td class="px-5 py-3 font-medium text-ink-900">{{ $mapping->countryB->name }}</td>
                        <td class="px-5 py-3 text-ink-500">{{ $mapping->countryB->continent ?? '—' }}</td>
                        <td class="px-5 py-3 text-ink-500">{{ $mapping->countryB->countryRegion?->name ?? '—' }}</td>
                        <td class="px-5 py-3">
                            <form method="POST" action="{{ route('zone-mappings.update-country-zone', $mapping) }}">
                                @csrf @method('PATCH')
                                <select name="zone_id" onchange="this.form.submit()"
                                        class="rounded-md border border-line bg-surface-0 px-2 py-1.5 text-sm text-ink-900 outline-none focus:border-[var(--brand-primary)]">
                                    <option value="">Unassigned</option>
                                    @foreach ($internationalZones as $zone)
                                        <option value="{{ $zone->id }}" @selected($mapping->zone_id === $zone->id)>{{ $zone->name }}</option>
                                    @endforeach
                                </select>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-5 py-8 text-center text-sm text-ink-500">No countries generated yet — click "Generate international countries" above.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-5">{{ $internationalMappings->links() }}</div>

    </div>{{-- /#intl-sub-nigeria --}}

    <div id="intl-sub-crosstrade" style="display:none">
        <p class="mb-4 text-sm text-ink-500">
            <strong>Cross-Trade (Third-Country Shipping)</strong> — for shipments this business arranges entirely
            between two OTHER countries, neither side is Nigeria (e.g. US to Congo, via a third-party arrangement).
        </p>

        <details class="mb-5 rounded-xl border border-line bg-surface-0 shadow-sm">
            <summary class="cursor-pointer px-5 py-3 text-sm font-semibold text-ink-900">Generate combinations from selected countries</summary>
            <div class="border-t border-line p-5">
                <p class="mb-4 text-xs text-ink-500">
                    Generating every possible pair across all ~178 countries would mean over 15,000 rows for a
                    relationship that's occasional, not routine — pick just the countries actually cross-traded with,
                    and every pair among them gets created. Safe to re-run after adding more countries later; existing
                    pairs are never touched.
                </p>
                <form method="POST" action="{{ route('zone-mappings.third-party.generate') }}">
                    @csrf
                    <div class="mb-4 grid max-h-64 grid-cols-2 gap-x-4 gap-y-1 overflow-y-auto rounded-md border border-line p-3 sm:grid-cols-3 lg:grid-cols-4">
                        @foreach ($countries as $country)
                            <label class="flex items-center gap-2 py-0.5 text-sm text-ink-900">
                                <input type="checkbox" name="country_ids[]" value="{{ $country->id }}" class="rounded border-line">
                                {{ $country->name }}
                            </label>
                        @endforeach
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" class="rounded-md bg-[var(--brand-primary)] px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:opacity-90 hover:shadow-md">
                            Generate combinations
                        </button>
                    </div>
                </form>
            </div>
        </details>

        <details class="mb-5 rounded-xl border border-line bg-surface-0 shadow-sm">
            <summary class="cursor-pointer px-5 py-3 text-sm font-semibold text-ink-900">Apply a rule to every cross-trade route</summary>
            <div class="border-t border-line p-5">
                <p class="mb-4 text-xs text-ink-500">
                    Sets a zone for every existing cross-trade route at once, comparing each pair's own two countries
                    against each other — <strong>overwrites every existing assignment</strong>, including manual
                    ones. Neither side is fixed as Nigeria here, unlike the International rule.
                </p>

                <form method="POST" action="{{ route('zone-mappings.third-party.apply-rule') }}" onsubmit="return confirm('This overwrites the zone on every cross-trade route, including any already set manually. Continue?')">
                    @csrf
                    <div class="mb-4">
                        <span class="mb-2 block text-sm font-medium text-ink-900">Grouping method</span>
                        <div class="flex gap-3">
                            <label class="flex flex-1 cursor-pointer items-center gap-2 rounded-lg border border-line p-3 text-sm text-ink-900 has-[:checked]:border-[var(--brand-primary)] has-[:checked]:bg-[var(--brand-primary)]/5">
                                <input type="radio" name="grouping_method" value="continent" id="ctp-method-continent" class="rounded-full border-line"
                                       onchange="document.getElementById('ctp-continent-only-fields').style.display=''; document.getElementById('ctp-continent-region-fields').style.display='none';">
                                Continent only
                            </label>
                            <label class="flex flex-1 cursor-pointer items-center gap-2 rounded-lg border border-line p-3 text-sm text-ink-900 has-[:checked]:border-[var(--brand-primary)] has-[:checked]:bg-[var(--brand-primary)]/5">
                                <input type="radio" name="grouping_method" value="continent_region" id="ctp-method-continent-region" checked class="rounded-full border-line"
                                       onchange="document.getElementById('ctp-continent-only-fields').style.display='none'; document.getElementById('ctp-continent-region-fields').style.display='';">
                                Continent + Region <span class="text-xs text-ink-500">(recommended)</span>
                            </label>
                        </div>
                    </div>

                    <div id="ctp-continent-only-fields" style="display:none" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="flex items-center justify-between gap-3 rounded-lg border border-line p-3">
                            <span class="text-sm text-ink-900">Same continent</span>
                            <select name="zone_same_continent" class="rounded-md border border-line bg-surface-0 px-2 py-1.5 text-sm text-ink-900 outline-none focus:border-[var(--brand-primary)]">
                                @foreach ($internationalZones as $zone)
                                    <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex items-center justify-between gap-3 rounded-lg border border-line p-3">
                            <span class="text-sm text-ink-900">Different continent</span>
                            <select name="zone_different_continent" class="rounded-md border border-line bg-surface-0 px-2 py-1.5 text-sm text-ink-900 outline-none focus:border-[var(--brand-primary)]">
                                @foreach ($internationalZones as $zone)
                                    <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div id="ctp-continent-region-fields" class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div class="flex items-center justify-between gap-3 rounded-lg border border-line p-3">
                            <span class="text-sm text-ink-900">Same region</span>
                            <select name="zone_same_region" class="rounded-md border border-line bg-surface-0 px-2 py-1.5 text-sm text-ink-900 outline-none focus:border-[var(--brand-primary)]">
                                @foreach ($internationalZones as $zone)
                                    <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex items-center justify-between gap-3 rounded-lg border border-line p-3">
                            <span class="text-sm text-ink-900">Same continent, different region</span>
                            <select name="zone_same_continent_different_region" class="rounded-md border border-line bg-surface-0 px-2 py-1.5 text-sm text-ink-900 outline-none focus:border-[var(--brand-primary)]">
                                @foreach ($internationalZones as $zone)
                                    <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex items-center justify-between gap-3 rounded-lg border border-line p-3">
                            <span class="text-sm text-ink-900">Different continent</span>
                            <select name="zone_different_continent" class="rounded-md border border-line bg-surface-0 px-2 py-1.5 text-sm text-ink-900 outline-none focus:border-[var(--brand-primary)]">
                                @foreach ($internationalZones as $zone)
                                    <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="mt-4 flex justify-end">
                        <button type="submit" class="rounded-md bg-[var(--brand-primary)] px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:opacity-90 hover:shadow-md">
                            Apply to all cross-trade routes
                        </button>
                    </div>
                </form>
            </div>
        </details>

        <div class="mb-8 max-w-2xl rounded-xl border border-line bg-surface-0 p-5 shadow-sm">
            <h2 class="mb-3 text-sm font-semibold text-ink-900">Add a single route</h2>
            <form method="POST" action="{{ route('zone-mappings.third-party.store') }}" class="flex flex-wrap items-end gap-3">
                @csrf
                <div>
                    <label class="mb-1 block text-xs font-medium text-ink-900">Country A</label>
                    <select name="country_a_id" class="w-40 rounded-md border border-line px-2 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                        @foreach ($countries as $country)
                            <option value="{{ $country->id }}">{{ $country->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-ink-900">Country B</label>
                    <select name="country_b_id" class="w-40 rounded-md border border-line px-2 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                        @foreach ($countries as $country)
                            <option value="{{ $country->id }}">{{ $country->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-ink-900">Zone</label>
                    <select name="zone_id" class="w-40 rounded-md border border-line px-2 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                        <option value="">Unassigned</option>
                        @foreach ($internationalZones as $zone)
                            <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="rounded-md bg-[var(--brand-primary)] px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:opacity-90 hover:shadow-md">
                    Save route
                </button>
            </form>
        </div>

        <div class="overflow-x-auto rounded-xl border border-line bg-surface-0 shadow-sm">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-line text-xs uppercase tracking-wide text-ink-500">
                        <th class="px-5 py-3 font-medium">Country A</th>
                        <th class="px-5 py-3 font-medium">Country B</th>
                        <th class="px-5 py-3 font-medium">Zone</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($thirdPartyMappings as $mapping)
                        <tr class="border-b border-line last:border-0 odd:bg-surface-0 even:bg-surface-50/50 hover:bg-[var(--brand-primary)]/5 transition-colors">
                            <td class="px-5 py-3 font-medium text-ink-900">{{ $mapping->countryA->name }}</td>
                            <td class="px-5 py-3 font-medium text-ink-900">{{ $mapping->countryB->name }}</td>
                            <td class="px-5 py-3">
                                <form method="POST" action="{{ route('zone-mappings.third-party.update-zone', $mapping) }}">
                                    @csrf @method('PATCH')
                                    <select name="zone_id" onchange="this.form.submit()"
                                            class="rounded-md border border-line bg-surface-0 px-2 py-1.5 text-sm text-ink-900 outline-none focus:border-[var(--brand-primary)]">
                                        <option value="">Unassigned</option>
                                        @foreach ($internationalZones as $zone)
                                            <option value="{{ $zone->id }}" @selected($mapping->zone_id === $zone->id)>{{ $zone->name }}</option>
                                        @endforeach
                                    </select>
                                </form>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <form method="POST" action="{{ route('zone-mappings.third-party.destroy', $mapping) }}" onsubmit="return confirm('Remove this route?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-sm font-medium text-status-exception transition-colors hover:text-status-exception/70">Remove</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-5 py-8 text-center text-sm text-ink-500">No cross-trade routes added yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>{{-- /#intl-sub-crosstrade --}}

    </div>{{-- /#tab-international --}}


    <script>
        function showZoneTab(tab) {
            ['domestic', 'international'].forEach(function (name) {
                document.getElementById('tab-' + name).style.display = tab === name ? '' : 'none';

                const btn = document.getElementById('tab-btn-' + name);
                btn.classList.toggle('border-[var(--brand-primary)]', tab === name);
                btn.classList.toggle('text-ink-900', tab === name);
                btn.classList.toggle('font-semibold', tab === name);
                btn.classList.toggle('border-transparent', tab !== name);
                btn.classList.toggle('text-ink-500', tab !== name);
            });
        }

        function showInternationalSubTab(sub) {
            ['nigeria', 'crosstrade'].forEach(function (name) {
                document.getElementById('intl-sub-' + name).style.display = sub === name ? '' : 'none';

                const btn = document.getElementById('intl-subtab-btn-' + name);
                btn.classList.toggle('bg-[var(--brand-primary)]/10', sub === name);
                btn.classList.toggle('text-[var(--brand-primary)]', sub === name);
                btn.classList.toggle('font-semibold', sub === name);
                btn.classList.toggle('text-ink-500', sub !== name);
                btn.classList.toggle('font-medium', sub !== name);
            });
        }
    </script>

</x-layouts.app>
