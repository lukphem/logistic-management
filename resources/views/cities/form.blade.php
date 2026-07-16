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

        <div>
            <label class="mb-1 block text-sm font-medium text-ink-900">Short code <x-required /></label>
            <input type="text" name="short_code" value="{{ old('short_code', $city->short_code) }}" placeholder="e.g. IKJ"
                   class="w-full max-w-[10rem] rounded-md border border-line px-3 py-2 text-sm font-mono uppercase outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
            <p class="mt-1 text-xs text-ink-500">
                The full client-facing code is composed automatically: state code + this short code.
                @if ($city->exists && $city->code)
                    Currently <span class="font-mono font-medium text-ink-900">{{ $city->code }}</span>.
                @endif
            </p>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-ink-900">Operational hub <span class="text-xs font-normal text-ink-500">(optional)</span></label>
            <select name="operational_hub_id" class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                <option value="">No specific hub</option>
                @foreach ($hubs as $hub)
                    <option value="{{ $hub->id }}" @selected(old('operational_hub_id', $city->operational_hub_id) == $hub->id)>
                        {{ $hub->name }} ({{ $hub->code }}){{ $hub->city ? ' — ' . $hub->city->name . ', ' . $hub->city->state->name : '' }}
                    </option>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-ink-500">
                Every city can have one, whether or not there's an actual conflict — set it whenever you want shipments
                originating from or destined to this city to always use that hub's code, in place of whatever the
                automatic resolution would otherwise pick. The hub doesn't have to be based in this city's own state —
                pick any hub from anywhere in the network if that's genuinely the one handling this city.
            </p>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-ink-900">Onforwarding classification <span class="text-xs font-normal text-ink-500">(optional)</span></label>
            <select name="onforwarding_classification_id" class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                <option value="">No classification (no extra charge)</option>
                @foreach ($classifications as $classification)
                    <option value="{{ $classification->id }}" @selected(old('onforwarding_classification_id', $city->onforwarding_classification_id) == $classification->id)>
                        {{ $classification->name }} (+{{ number_format($classification->surcharge_amount, 2) }})
                    </option>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-ink-500">
                If set, any shipment originating from or destined to this city is charged this extra amount automatically.
                Manage the available classifications under Setups → Client Billing → Onforwarding Classifications.
            </p>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-ink-900">Postal code <span class="text-xs font-normal text-ink-500">(optional)</span></label>
            <input type="text" name="postal_code" value="{{ old('postal_code', $city->postal_code) }}"
                   class="w-full max-w-[10rem] rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
        </div>

        <div class="flex justify-end gap-3 pt-2">
            <a href="{{ route('cities.index') }}" class="rounded-md px-4 py-2 text-sm font-medium text-ink-500 hover:bg-surface-50">Cancel</a>
            <button type="submit" class="rounded-md bg-[var(--brand-primary)] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 hover:shadow-md">
                {{ $city->exists ? 'Save changes' : 'Add city' }}
            </button>
        </div>
    </form>

</x-layouts.app>
