<x-layouts.app :title="'New Manifest Trip'">

    <div class="mb-6">
        <p class="text-2xl font-semibold text-ink-900">New Manifest Trip</p>
        <p class="mt-1 text-sm text-ink-500">One vehicle journey, one first destination batch. Add more destinations to this trip afterward if the vehicle makes multiple drops.</p>
    </div>

    @if ($errors->any())
        <div class="mb-4 rounded-xl border border-status-exception/30 bg-status-exception/5 px-4 py-3 text-sm text-status-exception">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('manifest-trips.store') }}" id="trip-form" class="space-y-6">
        @csrf

        <div class="rounded-xl border border-line bg-surface-0 shadow-sm p-5">
            <p class="mb-4 text-sm font-semibold text-ink-900">Origin</p>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink-900">Origin hub</label>
                    <select name="origin_hub_id" id="origin-hub" class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                        <option value="">— Select —</option>
                        @foreach ($hubs as $hub)
                            <option value="{{ $hub->id }}" @selected(old('origin_hub_id') == $hub->id)>{{ $hub->name }} ({{ $hub->code }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink-900">Or origin outlet <span class="text-xs font-normal text-ink-500">(if dispatching straight from an outlet counter)</span></label>
                    <select name="origin_outlet_id" id="origin-outlet" class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                        <option value="">— None —</option>
                        @foreach ($outlets as $outlet)
                            <option value="{{ $outlet->id }}" @selected(old('origin_outlet_id') == $outlet->id)>{{ $outlet->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-line bg-surface-0 shadow-sm p-5">
            <p class="mb-4 text-sm font-semibold text-ink-900">Vehicle &amp; carrier</p>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink-900">Transport mode <x-required /></label>
                    <select name="transport_mode" required class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                        <option value="road" @selected(old('transport_mode', 'road') === 'road')>Road</option>
                        <option value="air" @selected(old('transport_mode') === 'air')>Air</option>
                        <option value="sea" @selected(old('transport_mode') === 'sea')>Sea</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink-900">Carrier <x-required /></label>
                    <div class="flex gap-4 pt-2">
                        <label class="flex items-center gap-2 text-sm text-ink-900">
                            <input type="radio" name="carrier_type" value="company" @checked(old('carrier_type', 'company') === 'company') class="border-line" onchange="document.getElementById('carrier-name-field').classList.add('hidden')">
                            Company
                        </label>
                        <label class="flex items-center gap-2 text-sm text-ink-900">
                            <input type="radio" name="carrier_type" value="third_party" @checked(old('carrier_type') === 'third_party') class="border-line" onchange="document.getElementById('carrier-name-field').classList.remove('hidden')">
                            3PL
                        </label>
                    </div>
                </div>
                <div id="carrier-name-field" class="{{ old('carrier_type') === 'third_party' ? '' : 'hidden' }}">
                    <label class="mb-1 block text-sm font-medium text-ink-900">3PL name <x-required /></label>
                    <input type="text" name="carrier_name" value="{{ old('carrier_name') }}" class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink-900">Vehicle type <span class="text-xs font-normal text-ink-500">(optional)</span></label>
                    <select name="vehicle_type_id" class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                        <option value="">— None —</option>
                        @foreach ($vehicleTypes as $vt)
                            <option value="{{ $vt->id }}" @selected(old('vehicle_type_id') == $vt->id)>{{ $vt->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink-900">Vehicle identifier <span class="text-xs font-normal text-ink-500">(plate number)</span></label>
                    <input type="text" name="vehicle_identifier" value="{{ old('vehicle_identifier') }}" class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink-900">Driver name</label>
                    <input type="text" name="driver_name" value="{{ old('driver_name') }}" class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink-900">Driver phone</label>
                    <input type="text" name="driver_phone" value="{{ old('driver_phone') }}" class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-sm font-medium text-ink-900">Notes <span class="text-xs font-normal text-ink-500">(optional)</span></label>
                    <textarea name="notes" rows="2" class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">{{ old('notes') }}</textarea>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-line bg-surface-0 shadow-sm p-5">
            <p class="mb-4 text-sm font-semibold text-ink-900">First destination</p>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink-900">Destination hub <x-required /></label>
                    <select name="destination_hub_id" id="destination-hub" required class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                        <option value="">— Select —</option>
                        @foreach ($destinationHubs as $hub)
                            <option value="{{ $hub->id }}" @selected(old('destination_hub_id') == $hub->id)>{{ $hub->name }} ({{ $hub->code }})</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-ink-500">Doesn't have to be a shipment's final destination — drop it here for a further manifest onward if it's just a waypoint.</p>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink-900">Estimated arrival</label>
                    <input type="datetime-local" name="estimated_arrival_at" value="{{ old('estimated_arrival_at') }}" class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-line bg-surface-0 shadow-sm p-5">
            <p class="mb-1 text-sm font-semibold text-ink-900">Shipments for this manifest</p>
            <p class="mb-4 text-xs text-ink-500">Pick an origin above to see what's eligible, grouped by destination code — or scan tracking numbers directly below.</p>

            <div class="mb-4 flex flex-wrap items-end gap-3 rounded-lg border border-dashed border-line p-3">
                <div class="flex-1 min-w-[220px]">
                    <label class="mb-1 block text-xs font-medium text-ink-900">Scan or type a tracking number</label>
                    <input type="text" id="scan-input" placeholder="Focus here, then scan — or type and press Enter"
                           class="w-full rounded-md border border-line px-3 py-2 text-sm font-mono outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20" autocomplete="off">
                </div>
                <button type="button" id="camera-scan-btn" class="rounded-md border border-line px-3 py-2 text-sm font-medium text-ink-700 hover:bg-surface-50">
                    📷 Scan with camera
                </button>
                <span id="scan-feedback" class="text-xs"></span>
            </div>

            <div id="camera-scanner" class="mb-4 hidden max-w-sm overflow-hidden rounded-lg border border-line"></div>

            <div id="destination-groups" class="space-y-3">
                <p class="text-sm text-ink-500">Select an origin above to load eligible shipments.</p>
            </div>

            <div id="selected-list" class="mt-4 hidden rounded-lg bg-surface-50 p-3">
                <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-ink-500">Selected (<span id="selected-count">0</span>)</p>
                <ul id="selected-items" class="space-y-1 text-sm"></ul>
            </div>
        </div>

        <div class="flex justify-end gap-3 pb-8">
            <a href="{{ route('manifest-trips.index') }}" class="rounded-md border border-line px-4 py-2 text-sm font-medium text-ink-700 hover:bg-surface-50">Cancel</a>
            <button type="submit" class="rounded-md bg-[var(--brand-primary)] px-5 py-2 text-sm font-semibold text-white shadow-sm hover:opacity-90">Create trip</button>
        </div>
    </form>

    <script src="https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <script>
        (function () {
            const originHubSelect = document.getElementById('origin-hub');
            const originOutletSelect = document.getElementById('origin-outlet');
            const groupsContainer = document.getElementById('destination-groups');
            const scanInput = document.getElementById('scan-input');
            const scanFeedback = document.getElementById('scan-feedback');
            const selectedList = document.getElementById('selected-list');
            const selectedItems = document.getElementById('selected-items');
            const selectedCount = document.getElementById('selected-count');
            const form = document.getElementById('trip-form');

            // Tracking number -> {trackingNumber} for whatever's currently
            // selected, whether picked via the destination-code groups or
            // scanned in directly — one source of truth either way.
            const selected = new Map();

            function syncHiddenInputs() {
                form.querySelectorAll('input[name="shipment_ids[]"]').forEach(el => el.remove());
                selected.forEach((label, id) => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'shipment_ids[]';
                    input.value = id;
                    form.appendChild(input);
                });
                selectedCount.textContent = selected.size;
                selectedList.classList.toggle('hidden', selected.size === 0);
                selectedItems.innerHTML = '';
                selected.forEach((label, id) => {
                    const li = document.createElement('li');
                    li.className = 'flex items-center justify-between gap-2';
                    li.innerHTML = `<span class="font-mono">${label}</span>`;
                    const removeBtn = document.createElement('button');
                    removeBtn.type = 'button';
                    removeBtn.textContent = 'Remove';
                    removeBtn.className = 'text-xs text-status-exception hover:underline';
                    removeBtn.onclick = function () {
                        selected.delete(id);
                        syncHiddenInputs();
                        document.querySelectorAll(`input.group-checkbox[value="${id}"]`).forEach(cb => cb.checked = false);
                    };
                    li.appendChild(removeBtn);
                    selectedItems.appendChild(li);
                });
            }

            function addShipment(id, trackingNumber) {
                selected.set(String(id), trackingNumber);
                syncHiddenInputs();
            }

            function loadEligible() {
                const hubId = originHubSelect.value;
                const outletId = originOutletSelect.value;
                if (! hubId && ! outletId) {
                    groupsContainer.innerHTML = '<p class="text-sm text-ink-500">Select an origin above to load eligible shipments.</p>';
                    return;
                }
                const params = new URLSearchParams();
                if (outletId) { params.set('outlet_id', outletId); } else { params.set('hub_id', hubId); }

                groupsContainer.innerHTML = '<p class="text-sm text-ink-500">Loading…</p>';

                fetch(@json(route('manifests.eligible-shipments')) + '?' + params.toString())
                    .then(r => r.json())
                    .then(function (groups) {
                        if (! groups.length) {
                            groupsContainer.innerHTML = '<p class="text-sm text-ink-500">Nothing eligible at this origin right now.</p>';
                            return;
                        }
                        groupsContainer.innerHTML = '';
                        groups.forEach(function (group) {
                            const box = document.createElement('div');
                            box.className = 'rounded-lg border border-line p-3';
                            const header = document.createElement('div');
                            header.className = 'mb-2 flex items-center justify-between';
                            header.innerHTML = `<span class="text-sm font-medium text-ink-900">${group.destination_hub_code} — ${group.destination_hub_name} <span class="text-xs text-ink-500">(${group.shipments.length})</span></span>`;
                            const selectAllBtn = document.createElement('button');
                            selectAllBtn.type = 'button';
                            selectAllBtn.textContent = 'Add all';
                            selectAllBtn.className = 'text-xs font-medium text-[var(--brand-primary)] hover:underline';
                            selectAllBtn.onclick = function () {
                                group.shipments.forEach(s => addShipment(s.id, s.tracking_number));
                                box.querySelectorAll('input.group-checkbox').forEach(cb => cb.checked = true);
                            };
                            header.appendChild(selectAllBtn);
                            box.appendChild(header);

                            const list = document.createElement('div');
                            list.className = 'space-y-1';
                            group.shipments.forEach(function (s) {
                                const label = document.createElement('label');
                                label.className = 'flex items-center gap-2 text-sm text-ink-700';
                                label.innerHTML = `<input type="checkbox" class="group-checkbox rounded border-line" value="${s.id}"> <span class="font-mono">${s.tracking_number}</span> <span class="text-ink-500">${s.receiver_name}</span>`;
                                label.querySelector('input').addEventListener('change', function (e) {
                                    if (e.target.checked) { addShipment(s.id, s.tracking_number); }
                                    else { selected.delete(String(s.id)); syncHiddenInputs(); }
                                });
                                list.appendChild(label);
                            });
                            box.appendChild(list);
                            groupsContainer.appendChild(box);
                        });
                    })
                    .catch(function () {
                        groupsContainer.innerHTML = '<p class="text-sm text-status-exception">Could not load eligible shipments.</p>';
                    });
            }

            originHubSelect.addEventListener('change', function () { if (this.value) originOutletSelect.value = ''; loadEligible(); });
            originOutletSelect.addEventListener('change', function () { if (this.value) originHubSelect.value = ''; loadEligible(); });

            // Keyboard-wedge handheld scanners type the code then send
            // Enter automatically — this is the primary scanning path for
            // hub/warehouse counters, and needs no special library at all,
            // just a focused text input listening for Enter.
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
                    .then(r => r.json().then(data => ({ ok: r.ok, data })))
                    .then(function ({ ok, data }) {
                        if (! ok || ! data.found) {
                            scanFeedback.textContent = data.message || 'Not found.';
                            scanFeedback.className = 'text-xs text-status-exception';
                            return;
                        }
                        addShipment(data.id, data.tracking_number);
                        scanFeedback.textContent = '✓ Added ' + data.tracking_number;
                        scanFeedback.className = 'text-xs text-status-delivered';
                    })
                    .catch(function () {
                        scanFeedback.textContent = 'Lookup failed — try again.';
                        scanFeedback.className = 'text-xs text-status-exception';
                    });

                scanInput.value = '';
            });

            // Camera-based scanning — the secondary path, for a phone/
            // tablet with no handheld scanner attached. Started only on
            // click, and stopped again once a code is read, so the
            // camera isn't left running in the background.
            let html5QrCode = null;
            document.getElementById('camera-scan-btn').addEventListener('click', function () {
                const container = document.getElementById('camera-scanner');
                if (! container.classList.contains('hidden')) {
                    container.classList.add('hidden');
                    if (html5QrCode) { html5QrCode.stop().catch(() => {}); }
                    return;
                }
                container.classList.remove('hidden');
                container.innerHTML = '<div id="qr-reader" style="width: 100%;"></div>';
                html5QrCode = new Html5Qrcode('qr-reader');
                html5QrCode.start(
                    { facingMode: 'environment' },
                    { fps: 10, qrbox: 220 },
                    function (decodedText) {
                        scanInput.value = decodedText;
                        scanInput.dispatchEvent(new KeyboardEvent('keydown', { key: 'Enter' }));
                        html5QrCode.stop().catch(() => {});
                        container.classList.add('hidden');
                    },
                    function () { /* per-frame scan failures are normal, ignore */ }
                ).catch(function () {
                    scanFeedback.textContent = 'Could not access camera.';
                    scanFeedback.className = 'text-xs text-status-exception';
                    container.classList.add('hidden');
                });
            });
        })();
    </script>

</x-layouts.app>
