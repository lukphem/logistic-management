<x-layouts.app :title="$zone->exists ? 'Edit Zone' : 'Add Zone'">

    <form method="POST" action="{{ $zone->exists ? route('zones.update', $zone) : route('zones.store') }}" class="max-w-xl space-y-4 rounded-xl border border-line bg-surface-0 shadow-sm p-5">
        @csrf
        @if ($zone->exists) @method('PUT') @endif

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
            <label class="mb-1 block text-sm font-medium text-ink-900">Zone name</label>
            <input type="text" name="name" value="{{ old('name', $zone->name) }}" placeholder="e.g. Lagos Mainland"
                   class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-ink-900">Code</label>
            <input type="text" name="code" value="{{ old('code', $zone->code) }}" placeholder="e.g. LAG-M"
                   class="w-full rounded-md border border-line px-3 py-2 text-sm font-mono outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-ink-900">Applies to <x-required /></label>
            <div class="flex gap-3">
                <label class="flex flex-1 cursor-pointer items-center gap-2 rounded-lg border border-line p-3 text-sm text-ink-900 has-[:checked]:border-[var(--brand-primary)] has-[:checked]:bg-[var(--brand-primary)]/5">
                    <input type="checkbox" id="applies-domestic" name="applies_domestic" value="1"
                           @checked(old('applies_domestic', $zone->exists ? $zone->applies_domestic : true))
                           onchange="document.getElementById('tier-field').style.display = this.checked ? '' : 'none';" class="rounded border-line">
                    Domestic
                </label>
                <label class="flex flex-1 cursor-pointer items-center gap-2 rounded-lg border border-line p-3 text-sm text-ink-900 has-[:checked]:border-[var(--brand-primary)] has-[:checked]:bg-[var(--brand-primary)]/5">
                    <input type="checkbox" name="applies_international" value="1"
                           @checked(old('applies_international', $zone->applies_international ?? false)) class="rounded border-line">
                    International
                </label>
            </div>
            <p class="mt-1 text-xs text-ink-500">
                A zone can apply to both — e.g. a broad zone reused across domestic and international mapping. At
                least one must be checked. The tier picker below only applies to the domestic side.
            </p>
        </div>

        <div id="tier-field" style="{{ old('applies_domestic', $zone->exists ? $zone->applies_domestic : true) ? '' : 'display:none' }}">
            <label class="mb-1 block text-sm font-medium text-ink-900">Tier <span class="text-xs font-normal text-ink-500">(optional)</span></label>
            <select id="tier" name="tier" class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                <option value="">No tier set</option>
                @foreach (\App\Models\Zone::TIERS as $key => $tier)
                    <option value="{{ $key }}" @selected(old('tier', $zone->tier) === $key)>{{ $tier['label'] }} — {{ $tier['purpose'] }}</option>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-ink-500">
                The standard courier zone-tier model — classifies what kind of domestic coverage this is. The actual
                price between zones is still set under Billing → Zone Mapping, not here.
            </p>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-ink-900">Coverage description <x-required /></label>
            <input id="coverage_description" type="text" name="coverage_description" value="{{ old('coverage_description', $zone->coverage_description) }}"
                   placeholder="e.g. Nearby towns within the same state, or West Africa for an international zone"
                   class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
            <p class="mt-1 text-xs text-ink-500">Filled in automatically from the tier's standard description if left blank — override it anytime.</p>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-ink-900">Hub</label>
            <select name="hub_id" class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                <option value="">— None —</option>
                @foreach ($hubs as $hub)
                    <option value="{{ $hub->id }}" @selected(old('hub_id', $zone->hub_id) == $hub->id)>{{ $hub->name }}</option>
                @endforeach
            </select>
        </div>

        <script>
            (function () {
                const tierDescriptions = @json(collect(\App\Models\Zone::TIERS)->map(fn ($t) => $t['coverage']));
                const tierSelect = document.getElementById('tier');
                const descriptionInput = document.getElementById('coverage_description');

                tierSelect.addEventListener('change', function () {
                    if (!descriptionInput.value.trim() && tierDescriptions[tierSelect.value]) {
                        descriptionInput.value = tierDescriptions[tierSelect.value];
                    }
                });
            })();
        </script>

        <div class="flex justify-end gap-3 pt-2">
            <a href="{{ route('zones.index') }}" class="rounded-md px-4 py-2 text-sm font-medium text-ink-500 hover:bg-surface-50">Cancel</a>
            <button type="submit" class="rounded-md bg-[var(--brand-primary)] px-4 py-2 text-sm font-semibold text-white hover:opacity-90">
                {{ $zone->exists ? 'Save changes' : 'Add zone' }}
            </button>
        </div>
    </form>

</x-layouts.app>
