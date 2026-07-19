<x-layouts.app :title="$additionalService->exists ? 'Edit Service' : 'Add Service'">

    @if (session('status'))
        <div class="mb-5 rounded-xl bg-status-delivered/10 px-4 py-3 text-sm font-medium text-status-delivered">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ $additionalService->exists ? route('additional-services.update', $additionalService) : route('additional-services.store') }}" class="max-w-2xl space-y-4 rounded-xl border border-line bg-surface-0 shadow-sm p-5">
        @csrf
        @if ($additionalService->exists) @method('PUT') @endif

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
            <label class="mb-1 block text-sm font-medium text-ink-900">Service name <x-required /></label>
            <input type="text" name="name" list="service-name-suggestions" value="{{ old('name', $additionalService->name) }}" placeholder="e.g. Packaging"
                   class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
            <datalist id="service-name-suggestions">
                <option value="Packaging">
                <option value="Acknowledgement">
                <option value="Fragile Handling">
                <option value="Gift Wrapping">
                <option value="Signature on Delivery">
            </datalist>
            <p class="mt-1 text-xs text-ink-500">Common names are suggested as you type — feel free to enter your own.</p>
        </div>

        <div>
            <div class="mb-2 flex items-center justify-between">
                <label class="block text-sm font-medium text-ink-900">Options <x-required /></label>
                <button type="button" id="add-option-row" class="text-sm font-medium text-[var(--brand-primary)] hover:underline">+ Add option</button>
            </div>
            <p class="mb-2 text-xs text-ink-500">
                A service with just one type still needs one option — e.g. "Fragile Handling" with a single "Standard" row.
                For something with real variants, add one row per type: "Small Box", "Medium Box", "Large Box" — each its own price.
            </p>

            @php
                $oldOptions = old('options');

                if ($oldOptions !== null) {
                    $rowsToShow = $oldOptions;
                } else {
                    $rowsToShow = $options->mapWithKeys(fn ($option, $i) => [$i => [
                        'id' => $option->id,
                        'name' => $option->name,
                        'charge_type' => $option->charge_type,
                        'reverse_service_type_id' => $option->reverse_service_type_id,
                        'reverse_weight_kg' => $option->reverse_weight_kg,
                        'price' => $option->price,
                    ]])->all();
                }

                if (empty($rowsToShow)) {
                    $rowsToShow = [0 => ['id' => null, 'name' => '', 'charge_type' => 'flat', 'reverse_service_type_id' => '', 'reverse_weight_kg' => '', 'price' => '']];
                }
            @endphp

            <div id="option-rows" class="space-y-3" data-next-index="{{ max(array_keys($rowsToShow)) + 1 }}">
                @foreach ($rowsToShow as $i => $row)
                    @php $isReverse = ($row['charge_type'] ?? 'flat') === 'percentage_of_reverse_shipment'; @endphp
                    <div class="option-row rounded-lg border border-line p-3">
                        <div class="grid grid-cols-2 gap-2 sm:grid-cols-[2fr_1.4fr_1fr_auto] sm:items-end">
                            @if (! empty($row['id']))
                                <input type="hidden" name="options[{{ $i }}][id]" value="{{ $row['id'] }}">
                            @endif
                            <div>
                                <label class="mb-1 block text-xs font-medium text-ink-900">Name</label>
                                <input type="text" name="options[{{ $i }}][name]" value="{{ $row['name'] ?? '' }}" placeholder="e.g. Small Box"
                                       class="w-full rounded-md border border-line px-2 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-ink-900">Charge type</label>
                                <select name="options[{{ $i }}][charge_type]" class="charge-type-select w-full rounded-md border border-line px-2 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                                    @foreach (\App\Models\AdditionalServiceOption::CHARGE_TYPES as $key => $label)
                                        <option value="{{ $key }}" @selected(($row['charge_type'] ?? 'flat') === $key)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="price-label mb-1 block text-xs font-medium text-ink-900">{{ $isReverse ? 'Percentage (%)' : (($row['charge_type'] ?? 'flat') === 'percentage' ? 'Percentage (%)' : 'Price') }}</label>
                                <input type="number" step="0.01" min="0" name="options[{{ $i }}][price]" value="{{ $row['price'] ?? '' }}"
                                       class="w-full rounded-md border border-line px-2 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                            </div>
                            <button type="button" class="remove-option-row rounded-md px-2 py-2 text-sm font-medium text-status-exception hover:bg-status-exception/10">Remove</button>
                        </div>

                        <div class="reverse-fields mt-2 grid grid-cols-2 gap-2" style="{{ $isReverse ? '' : 'display:none' }}">
                            <div>
                                <label class="mb-1 block text-xs font-medium text-ink-900">Reverse shipment's service type</label>
                                <select name="options[{{ $i }}][reverse_service_type_id]" class="w-full rounded-md border border-line px-2 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                                    <option value="">— Select —</option>
                                    @foreach ($serviceTypes as $serviceType)
                                        <option value="{{ $serviceType->id }}" @selected((string) ($row['reverse_service_type_id'] ?? '') === (string) $serviceType->id)>{{ $serviceType->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-ink-900">Reverse shipment's weight (kg)</label>
                                <input type="number" step="0.01" min="0.01" name="options[{{ $i }}][reverse_weight_kg]" value="{{ $row['reverse_weight_kg'] ?? '' }}" placeholder="e.g. 0.5"
                                       class="w-full rounded-md border border-line px-2 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                            </div>
                        </div>
                        <p class="reverse-fields-note mt-1 text-xs text-ink-500" style="{{ $isReverse ? '' : 'display:none' }}">
                            Prices this as its own small shipment on the same route, using this service type and weight, then charges the percentage above of THAT rate — not a cut of the outbound freight.
                        </p>
                    </div>
                @endforeach
            </div>
            <p class="mt-2 text-xs text-ink-500">Leave a row's Price blank (or remove it) to clear that option.</p>
        </div>

        <label class="flex items-center gap-2 text-sm text-ink-900">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $additionalService->exists ? $additionalService->is_active : true)) class="rounded border-line">
            Active (selectable when booking or checking a rate)
        </label>

        <div class="flex justify-end gap-3 pt-2">
            <a href="{{ route('additional-services.index') }}" class="rounded-md px-4 py-2 text-sm font-medium text-ink-500 hover:bg-surface-50">Cancel</a>
            <button type="submit" class="rounded-md bg-[var(--brand-primary)] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 hover:shadow-md">
                {{ $additionalService->exists ? 'Save changes' : 'Add service' }}
            </button>
        </div>
    </form>

    <script>
        (function () {
            const addButton = document.getElementById('add-option-row');
            const container = document.getElementById('option-rows');
            let index = parseInt(container.dataset.nextIndex, 10);

            function wireRemove(row) {
                row.querySelector('.remove-option-row').addEventListener('click', function () {
                    row.remove();
                });
            }

            /**
             * "Price" reads as "Percentage (%)" for either percentage
             * charge type, and the reverse-shipment fields (service
             * type + weight) only show for the reverse type — kept in
             * sync with whichever option is chosen, on every row,
             * including ones added after page load.
             */
            function wireChargeTypeLabel(row) {
                const select = row.querySelector('.charge-type-select');
                const label = row.querySelector('.price-label');
                const reverseFields = row.querySelector('.reverse-fields');
                const reverseNote = row.querySelector('.reverse-fields-note');

                function sync() {
                    const isReverse = select.value === 'percentage_of_reverse_shipment';
                    label.textContent = (select.value === 'percentage' || isReverse) ? 'Percentage (%)' : 'Price';
                    reverseFields.style.display = isReverse ? '' : 'none';
                    reverseNote.style.display = isReverse ? '' : 'none';
                }

                select.addEventListener('change', sync);
            }

            container.querySelectorAll('.option-row').forEach(function (row) {
                wireRemove(row);
                wireChargeTypeLabel(row);
            });

            addButton.addEventListener('click', function () {
                const template = container.querySelector('.option-row');
                const row = template.cloneNode(true);

                const hiddenId = row.querySelector('input[name*="[id]"]');
                if (hiddenId) hiddenId.remove();

                row.querySelectorAll('input').forEach(function (input) {
                    input.name = input.name.replace(/options\[\d+\]/, `options[${index}]`);
                    input.value = '';
                });

                row.querySelectorAll('select').forEach(function (select) {
                    select.name = select.name.replace(/options\[\d+\]/, `options[${index}]`);
                    select.selectedIndex = 0;
                });
                row.querySelector('.price-label').textContent = 'Price';
                row.querySelector('.reverse-fields').style.display = 'none';
                row.querySelector('.reverse-fields-note').style.display = 'none';

                wireRemove(row);
                wireChargeTypeLabel(row);
                container.appendChild(row);
                index++;
            });
        })();
    </script>

</x-layouts.app>
