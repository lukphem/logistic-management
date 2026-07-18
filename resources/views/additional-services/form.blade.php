<x-layouts.app :title="$additionalService->exists ? 'Edit Service' : 'Add Service'">

    @if (session('status'))
        <div class="mb-5 rounded-xl bg-status-delivered/10 px-4 py-3 text-sm font-medium text-status-delivered">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ $additionalService->exists ? route('additional-services.update', $additionalService) : route('additional-services.store') }}" class="max-w-xl space-y-4 rounded-xl border border-line bg-surface-0 shadow-sm p-5">
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
            <input type="text" name="name" value="{{ old('name', $additionalService->name) }}" placeholder="e.g. Packaging"
                   class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
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
                        'price' => $option->price,
                    ]])->all();
                }

                if (empty($rowsToShow)) {
                    $rowsToShow = [0 => ['id' => null, 'name' => '', 'price' => '']];
                }
            @endphp

            <div id="option-rows" class="space-y-2" data-next-index="{{ max(array_keys($rowsToShow)) + 1 }}">
                @foreach ($rowsToShow as $i => $row)
                    <div class="option-row grid grid-cols-1 gap-2 sm:grid-cols-[2fr_1fr_auto] sm:items-end">
                        @if (! empty($row['id']))
                            <input type="hidden" name="options[{{ $i }}][id]" value="{{ $row['id'] }}">
                        @endif
                        <div>
                            <label class="mb-1 block text-xs font-medium text-ink-900">Name</label>
                            <input type="text" name="options[{{ $i }}][name]" value="{{ $row['name'] ?? '' }}" placeholder="e.g. Small Box"
                                   class="w-full rounded-md border border-line px-2 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-ink-900">Price</label>
                            <input type="number" step="0.01" min="0" name="options[{{ $i }}][price]" value="{{ $row['price'] ?? '' }}"
                                   class="w-full rounded-md border border-line px-2 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                        </div>
                        <button type="button" class="remove-option-row rounded-md px-2 py-2 text-sm font-medium text-status-exception hover:bg-status-exception/10">Remove</button>
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

            container.querySelectorAll('.option-row').forEach(wireRemove);

            addButton.addEventListener('click', function () {
                const template = container.querySelector('.option-row');
                const row = template.cloneNode(true);

                const hiddenId = row.querySelector('input[name*="[id]"]');
                if (hiddenId) hiddenId.remove();

                row.querySelectorAll('input').forEach(function (input) {
                    input.name = input.name.replace(/options\[\d+\]/, `options[${index}]`);
                    input.value = '';
                });

                wireRemove(row);
                container.appendChild(row);
                index++;
            });
        })();
    </script>

</x-layouts.app>
