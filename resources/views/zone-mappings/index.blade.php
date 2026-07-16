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

    <p class="mb-4 text-sm text-ink-500">
        Assigns every city to a Zone — e.g. "Port Harcourt = Zone 2." A shipment's origin and destination zones
        resolve from these assignments automatically, so origin-destination + weight rate cards (Billing → Rate Cards)
        only need one rate row per <em>zone pair</em>, not one per city pair.
    </p>

    <div class="mb-5">
        <form method="GET" class="flex items-center gap-3">
            <select name="zone_id" onchange="this.form.submit()"
                    class="rounded-md border border-line bg-surface-0 px-3 py-2 text-sm text-ink-900 outline-none focus:border-[var(--brand-primary)]">
                <option value="">All zones</option>
                @foreach ($zones as $zone)
                    <option value="{{ $zone->id }}" @selected(request('zone_id') == $zone->id)>{{ $zone->name }}</option>
                @endforeach
            </select>
        </form>
    </div>

    <div class="overflow-hidden rounded-xl border border-line bg-surface-0 shadow-sm">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="border-b border-line text-xs uppercase tracking-wide text-ink-500">
                    <th class="px-5 py-3 font-medium">City</th>
                    <th class="px-5 py-3 font-medium">State/Country</th>
                    <th class="px-5 py-3 font-medium">Zone</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($mappings as $mapping)
                    <tr class="border-b border-line last:border-0 odd:bg-surface-0 even:bg-surface-50/50 hover:bg-[var(--brand-primary)]/5 transition-colors">
                        <td class="px-5 py-3 font-medium text-ink-900">{{ $mapping->city->name }}</td>
                        <td class="px-5 py-3 text-ink-500">{{ $mapping->city->state->name }}, {{ $mapping->city->state->country->name }}</td>
                        <td class="px-5 py-3 text-ink-900">{{ $mapping->zone->name }}</td>
                        <td class="px-5 py-3 text-right">
                            <form method="POST" action="{{ route('zone-mappings.destroy', $mapping) }}" onsubmit="return confirm('Remove this zone assignment?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-sm font-medium text-status-exception transition-colors hover:text-status-exception/70">Remove</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-5 py-8 text-center text-sm text-ink-500">No cities assigned to a zone yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-5">{{ $mappings->links() }}</div>

    <div class="mt-6 rounded-xl border border-line bg-surface-0 p-5 shadow-sm">
        <h2 class="mb-4 text-sm font-semibold text-ink-900">Assign a city to a zone</h2>
        <form method="POST" action="{{ route('zone-mappings.store') }}" class="flex flex-wrap items-end gap-4">
            @csrf
            <div>
                <label class="mb-1 block text-sm font-medium text-ink-900">City <x-required /></label>
                <select name="city_id" class="w-56 rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                    @foreach ($cities->groupBy(fn ($city) => $city->state->name . ', ' . $city->state->country->name) as $groupLabel => $groupCities)
                        <optgroup label="{{ $groupLabel }}">
                            @foreach ($groupCities as $city)
                                <option value="{{ $city->id }}">{{ $city->name }}</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-ink-500">Re-assigning a city updates its existing mapping rather than creating a duplicate.</p>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-ink-900">Zone <x-required /></label>
                <select name="zone_id" class="w-44 rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                    @foreach ($zones as $zone)
                        <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                    @endforeach
                </select>
                @if ($zones->isEmpty())
                    <p class="mt-1 text-xs text-status-exception">No zones exist yet — create one under Billing → Zones first.</p>
                @endif
            </div>
            <button type="submit" class="rounded-md bg-[var(--brand-primary)] px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:opacity-90 hover:shadow-md">
                Save assignment
            </button>
        </form>
    </div>

</x-layouts.app>
