<x-layouts.app :title="$district->exists ? 'Edit District/Area' : 'Add District/Area'">

    <form method="POST" action="{{ $district->exists ? route('districts.update', $district) : route('districts.store') }}" class="max-w-xl space-y-4 rounded-xl border border-line bg-surface-0 shadow-sm p-5">
        @csrf
        @if ($district->exists) @method('PUT') @endif

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
            <label class="mb-1 block text-sm font-medium text-ink-900">City <x-required /></label>
            <select name="city_id" class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                @foreach ($cities->groupBy(fn ($city) => $city->state->name . ', ' . $city->state->country->name) as $groupLabel => $groupCities)
                    <optgroup label="{{ $groupLabel }}">
                        @foreach ($groupCities as $city)
                            <option value="{{ $city->id }}" @selected(old('city_id', $district->city_id) == $city->id)>{{ $city->name }}</option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-ink-900">District/Area name <x-required /></label>
            <input type="text" name="name" value="{{ old('name', $district->name) }}" placeholder="e.g. GRA, Opebi"
                   class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-ink-900">Short code <x-required /></label>
            <input type="text" name="short_code" value="{{ old('short_code', $district->short_code) }}" placeholder="e.g. GRA"
                   class="w-full max-w-[10rem] rounded-md border border-line px-3 py-2 text-sm font-mono uppercase outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
            <p class="mt-1 text-xs text-ink-500">
                The full client-facing code is composed automatically: city code + this short code.
                @if ($district->exists && $district->code)
                    Currently <span class="font-mono font-medium text-ink-900">{{ $district->code }}</span>.
                @endif
            </p>
        </div>

        <div class="flex justify-end gap-3 pt-2">
            <a href="{{ route('districts.index') }}" class="rounded-md px-4 py-2 text-sm font-medium text-ink-500 hover:bg-surface-50">Cancel</a>
            <button type="submit" class="rounded-md bg-[var(--brand-primary)] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 hover:shadow-md">
                {{ $district->exists ? 'Save changes' : 'Add district/area' }}
            </button>
        </div>
    </form>

</x-layouts.app>
