<x-layouts.app :title="'Add Manifest to ' . $trip->trip_number">

    <div class="mb-6">
        <p class="text-2xl font-semibold text-ink-900">Add destination to {{ $trip->trip_number }}</p>
        <p class="mt-1 text-sm text-ink-500">Another drop on this same vehicle's route.</p>
    </div>

    @if ($errors->any())
        <div class="mb-4 rounded-xl border border-status-exception/30 bg-status-exception/5 px-4 py-3 text-sm text-status-exception">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('manifests.store', $trip) }}" id="manifest-form" class="space-y-6">
        @csrf

        <div class="rounded-xl border border-line bg-surface-0 shadow-sm p-5">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink-900">Destination hub <x-required /></label>
                    <select name="destination_hub_id" required class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                        <option value="">— Select —</option>
                        @foreach ($destinationHubs as $hub)
                            <option value="{{ $hub->id }}" @selected(old('destination_hub_id') == $hub->id)>{{ $hub->name }} ({{ $hub->code }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink-900">Estimated arrival</label>
                    <input type="datetime-local" name="estimated_arrival_at" value="{{ old('estimated_arrival_at') }}" class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-line bg-surface-0 shadow-sm p-5">
            <p class="mb-1 text-sm font-semibold text-ink-900">Shipments for this manifest</p>
            <p class="mb-4 text-xs text-ink-500">Scan tracking numbers to add them — this trip's own origin is used automatically.</p>

            <div class="mb-4 flex flex-wrap items-end gap-3 rounded-lg border border-dashed border-line p-3">
                <div class="flex-1 min-w-[220px]">
                    <label class="mb-1 block text-xs font-medium text-ink-900">Scan or type a tracking number</label>
                    <input type="text" id="scan-input" placeholder="Focus here, then scan — or type and press Enter"
                           class="w-full rounded-md border border-line px-3 py-2 text-sm font-mono outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20" autocomplete="off">
                </div>
                <span id="scan-feedback" class="text-xs"></span>
            </div>

            <div id="selected-list" class="hidden rounded-lg bg-surface-50 p-3">
                <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-ink-500">Selected (<span id="selected-count">0</span>)</p>
                <ul id="selected-items" class="space-y-1 text-sm"></ul>
            </div>
        </div>

        <div class="flex justify-end gap-3 pb-8">
            <a href="{{ route('manifest-trips.show', $trip) }}" class="rounded-md border border-line px-4 py-2 text-sm font-medium text-ink-700 hover:bg-surface-50">Cancel</a>
            <button type="submit" class="rounded-md bg-[var(--brand-primary)] px-5 py-2 text-sm font-semibold text-white shadow-sm hover:opacity-90">Add manifest</button>
        </div>
    </form>

    <script>
        (function () {
            const scanInput = document.getElementById('scan-input');
            const scanFeedback = document.getElementById('scan-feedback');
            const selectedList = document.getElementById('selected-list');
            const selectedItems = document.getElementById('selected-items');
            const selectedCount = document.getElementById('selected-count');
            const form = document.getElementById('manifest-form');
            const selected = new Map();

            function syncHiddenInputs() {
                form.querySelectorAll('input[name="shipment_ids[]"]').forEach(el => el.remove());
                selected.forEach(function (label, id) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'shipment_ids[]';
                    input.value = id;
                    form.appendChild(input);
                });
                selectedCount.textContent = selected.size;
                selectedList.classList.toggle('hidden', selected.size === 0);
                selectedItems.innerHTML = '';
                selected.forEach(function (label, id) {
                    const li = document.createElement('li');
                    li.textContent = label;
                    selectedItems.appendChild(li);
                });
            }

            scanInput.addEventListener('keydown', function (e) {
                if (e.key !== 'Enter') return;
                e.preventDefault();
                const value = scanInput.value.trim();
                if (! value) return;
                scanFeedback.textContent = 'Looking up…';
                scanFeedback.className = 'text-xs text-ink-500';

                fetch(@json(route('manifests.lookup-tracking-number')), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': @json(csrf_token()) },
                    body: JSON.stringify({ tracking_number: value }),
                })
                    .then(r => r.json().then(data => ({ ok: r.ok, data: data })))
                    .then(function (result) {
                        if (! result.ok || ! result.data.found) {
                            scanFeedback.textContent = result.data.message || 'Not found.';
                            scanFeedback.className = 'text-xs text-status-exception';
                            return;
                        }
                        selected.set(String(result.data.id), result.data.tracking_number);
                        syncHiddenInputs();
                        scanFeedback.textContent = '✓ Added ' + result.data.tracking_number;
                        scanFeedback.className = 'text-xs text-status-delivered';
                    })
                    .catch(function () {
                        scanFeedback.textContent = 'Lookup failed — try again.';
                        scanFeedback.className = 'text-xs text-status-exception';
                    });

                scanInput.value = '';
            });
        })();
    </script>

</x-layouts.app>
