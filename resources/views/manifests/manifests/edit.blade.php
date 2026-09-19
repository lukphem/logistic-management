<x-layouts.app :title="'Edit ' . $manifest->manifest_number">

    <div class="mb-6">
        <p class="text-2xl font-semibold text-ink-900">Edit {{ $manifest->manifest_number }}</p>
        <p class="mt-1 text-sm text-ink-500">
            Trip {{ $manifest->trip->trip_number }} → {{ $manifest->destinationHub?->name ?? '—' }}.
            Add or remove shipments freely until this manifest is dispatched.
        </p>
    </div>

    @if (session('status'))
        <div class="mb-4 rounded-xl border border-status-delivered/30 bg-status-delivered/5 px-4 py-3 text-sm text-status-delivered">
            {{ session('status') }}
        </div>
    @endif
    @if ($errors->any())
        <div class="mb-4 rounded-xl border border-status-exception/30 bg-status-exception/5 px-4 py-3 text-sm text-status-exception">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="rounded-xl border border-line bg-surface-0 shadow-sm p-5">
        <p class="mb-1 text-sm font-semibold text-ink-900">Shipments on this manifest ({{ $manifest->manifestShipments->count() }})</p>

        @if ($manifest->manifestShipments->isEmpty())
            <p class="mt-2 text-sm text-ink-500">Nothing added yet — scan tracking numbers below.</p>
        @else
            <div class="mt-3 space-y-2">
                @foreach ($manifest->manifestShipments as $manifestShipment)
                    <div class="flex items-center justify-between rounded-lg border border-line p-3">
                        <div>
                            <p class="font-mono text-sm font-medium text-ink-900">{{ $manifestShipment->shipment->tracking_number }}</p>
                            <p class="text-xs text-ink-500">{{ $manifestShipment->shipment->receiver_name }}</p>
                        </div>
                        <form method="POST" action="{{ route('manifests.remove-shipment', [$manifest, $manifestShipment->shipment_id]) }}" onsubmit="return confirm('Remove {{ $manifestShipment->shipment->tracking_number }} from this manifest?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-xs font-medium text-status-exception hover:underline">Remove</button>
                        </form>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <div class="mt-4 rounded-xl border border-line bg-surface-0 shadow-sm p-5">
        <p class="mb-1 text-sm font-semibold text-ink-900">Add more shipments</p>
        <p class="mb-4 text-xs text-ink-500">Scan tracking numbers to add them to this manifest.</p>

        <form method="POST" action="{{ route('manifests.add-shipments', $manifest) }}" id="add-shipments-form">
            @csrf

            <div class="mb-4 flex flex-wrap items-end gap-3 rounded-lg border border-dashed border-line p-3">
                <div class="flex-1 min-w-[220px]">
                    <label class="mb-1 block text-xs font-medium text-ink-900">Scan or type a tracking number</label>
                    <input type="text" id="scan-input" placeholder="Focus here, then scan — or type and press Enter"
                           class="w-full rounded-md border border-line px-3 py-2 text-sm font-mono outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20" autocomplete="off">
                </div>
                <span id="scan-feedback" class="text-xs"></span>
            </div>

            <div id="selected-list" class="hidden rounded-lg bg-surface-50 p-3">
                <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-ink-500">Ready to add (<span id="selected-count">0</span>)</p>
                <ul id="selected-items" class="space-y-1 text-sm"></ul>
            </div>

            <div class="mt-4 flex justify-end gap-3">
                <a href="{{ route('manifest-trips.show', $manifest->trip) }}" class="rounded-md border border-line px-4 py-2 text-sm font-medium text-ink-700 hover:bg-surface-50">Done</a>
                <button type="submit" id="add-btn" disabled class="rounded-md bg-[var(--brand-primary)] px-5 py-2 text-sm font-semibold text-white shadow-sm hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-40">Add to manifest</button>
            </div>
        </form>
    </div>

    <script>
        (function () {
            const scanInput = document.getElementById('scan-input');
            const scanFeedback = document.getElementById('scan-feedback');
            const selectedList = document.getElementById('selected-list');
            const selectedItems = document.getElementById('selected-items');
            const selectedCount = document.getElementById('selected-count');
            const addBtn = document.getElementById('add-btn');
            const form = document.getElementById('add-shipments-form');
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
                addBtn.disabled = selected.size === 0;
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
                        scanFeedback.textContent = '✓ Ready to add ' + result.data.tracking_number;
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
