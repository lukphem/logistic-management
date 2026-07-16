<x-layouts.app :title="$city->exists ? 'Edit City' : 'Add City'">

    <form method="POST" action="{{ $city->exists ? route('cities.update', $city) : route('cities.store') }}" class="max-w-xl space-y-4 rounded-xl border border-line bg-surface-0 shadow-sm p-5">
        @csrf
        @if ($city->exists) @method('PUT') @endif

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
            <label class="mb-1 block text-sm font-medium text-ink-900">State/Province <x-required /></label>
            <select name="state_id" class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                @foreach ($states->groupBy('country_id') as $countryStates)
                    <optgroup label="{{ $countryStates->first()->country->name }}">
                        @foreach ($countryStates as $state)
                            <option value="{{ $state->id }}" @selected(old('state_id', $city->state_id) == $state->id)>{{ $state->name }}</option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-ink-900">City name <x-required /></label>
            <input type="text" name="name" value="{{ old('name', $city->name) }}" placeholder="e.g. Ikeja"
                   class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
        </div>

        <div class="flex justify-end gap-3 pt-2">
            <a href="{{ route('cities.index') }}" class="rounded-md px-4 py-2 text-sm font-medium text-ink-500 hover:bg-surface-50">Cancel</a>
            <button type="submit" class="rounded-md bg-[var(--brand-primary)] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 hover:shadow-md">
                {{ $city->exists ? 'Save changes' : 'Add city' }}
            </button>
        </div>
    </form>

</x-layouts.app>
