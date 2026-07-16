<x-layouts.app :title="$hub->exists ? 'Edit Hub' : 'Add Hub'">

    <form method="POST" action="{{ $hub->exists ? route('hubs.update', $hub) : route('hubs.store') }}" class="max-w-xl space-y-4 rounded-xl border border-line bg-surface-0 shadow-sm p-5">
        @csrf
        @if ($hub->exists) @method('PUT') @endif

        @if ($errors->any())
            <div class="rounded-md bg-status-exception/10 px-4 py-3 text-sm text-status-exception">
                <ul class="list-inside list-disc">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div>
            <label class="mb-1 block text-sm font-medium text-ink-900">Hub name <x-required /></label>
            <input type="text" name="name" value="{{ old('name', $hub->name) }}"
                   class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-ink-900">Code <x-required /></label>
            <input type="text" name="code" value="{{ old('code', $hub->code) }}" placeholder="e.g. LOS-01"
                   class="w-full rounded-md border border-line px-3 py-2 text-sm font-mono outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-ink-900">Region</label>
            <select name="region_id" class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                <option value="">No region</option>
                @foreach ($regions as $region)
                    <option value="{{ $region->id }}" @selected(old('region_id', $hub->region_id) == $region->id)>{{ $region->name }}</option>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-ink-500">Access-scope grouping — separate from the city below, which is the actual operating location.</p>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-ink-900">City</label>
            <select name="city_id" class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                <option value="">No city set</option>
                @foreach ($cities->groupBy(fn ($city) => $city->state->name . ', ' . $city->state->country->name) as $groupLabel => $groupCities)
                    <optgroup label="{{ $groupLabel }}">
                        @foreach ($groupCities as $city)
                            <option value="{{ $city->id }}" @selected(old('city_id', $hub->city_id) == $city->id)>{{ $city->name }}</option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-ink-500">The actual city this hub operates in. Manage the list under Setups → Location → Countries/States/Cities.</p>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-ink-900">Operating states</label>
            <p class="mb-2 text-xs text-ink-500">Every state this hub picks up from and delivers to — usually broader than just its home city's state.</p>
            @php $selectedStateIds = old('state_ids', $hub->exists ? $hub->states->pluck('id')->all() : []); @endphp
            <div class="max-h-48 space-y-3 overflow-y-auto rounded-md border border-line p-3">
                @foreach ($states->groupBy(fn ($state) => $state->country->name) as $countryName => $countryStates)
                    <div>
                        <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-ink-500">{{ $countryName }}</p>
                        <div class="grid grid-cols-2 gap-1 sm:grid-cols-3">
                            @foreach ($countryStates as $state)
                                <label class="flex items-center gap-1.5 text-sm text-ink-900">
                                    <input type="checkbox" name="state_ids[]" value="{{ $state->id }}"
                                           @checked(in_array($state->id, $selectedStateIds)) class="rounded border-line">
                                    {{ $state->name }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-ink-900">Address <x-required /></label>
            <textarea name="address" rows="2"
                      class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">{{ old('address', $hub->address) }}</textarea>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="mb-1 block text-sm font-medium text-ink-900">Latitude</label>
                <input type="text" name="latitude" value="{{ old('latitude', $hub->latitude) }}"
                       class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-ink-900">Longitude</label>
                <input type="text" name="longitude" value="{{ old('longitude', $hub->longitude) }}"
                       class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
            </div>
        </div>

        <label class="flex items-center gap-2 text-sm text-ink-900">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $hub->is_active ?? true)) class="rounded border-line">
            Active
        </label>

        <div class="flex justify-end gap-3 pt-2">
            <a href="{{ route('hubs.index') }}" class="rounded-md px-4 py-2 text-sm font-medium text-ink-500 hover:bg-surface-50">Cancel</a>
            <button type="submit" class="rounded-md bg-[var(--brand-primary)] px-4 py-2 text-sm font-semibold text-white hover:opacity-90">
                {{ $hub->exists ? 'Save changes' : 'Add hub' }}
            </button>
        </div>
    </form>

</x-layouts.app>
