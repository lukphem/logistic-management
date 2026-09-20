<x-layouts.app :title="$typeLabel">

    <div class="mb-6">
        <p class="text-2xl font-semibold text-ink-900">{{ $typeLabel }}</p>
        <p class="mt-1 text-sm text-ink-500">Scan each waybill (or a manifest/trip number to load a whole batch) — verify the details, then confirm once for everything.</p>
    </div>

    <div class="rounded-xl border border-line bg-surface-0 shadow-sm p-5">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            @if ($isMultiChoice)
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink-900">Reason <x-required /></label>
                    <select id="scan-status" class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                        <option value="">— Select —</option>
                        @foreach ($availableStatuses as $status)
                            <option value="{{ $status->key }}">{{ $status->label }}</option>
                        @endforeach
                    </select>
                </div>
            @else
                <input type="hidden" id="scan-status" value="{{ $availableStatuses->first()?->key }}">
            @endif

            @if ($lockedLocationLabel)
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink-900">Location</label>
                    <div class="rounded-md border border-line bg-surface-50 px-3 py-2 text-sm text-ink-700">{{ $lockedLocationLabel }} <span class="text-xs text-ink-500">(your assigned location)</span></div>
                    <input type="hidden" id="scan-hub" value="">
                    <input type="hidden" id="scan-outlet" value="">
                </div>
            @else
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink-900">Hub</label>
                    <select id="scan-hub" class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                        <option value="">— None —</option>
                        @foreach ($hubs as $hub)
                            <option value="{{ $hub->id }}">{{ $hub->name }} ({{ $hub->code }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink-900">Or outlet</label>
                    <select id="scan-outlet" class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                        <option value="">— None —</option>
                        @foreach ($outlets as $outlet)
                            <option value="{{ $outlet->id }}">{{ $outlet->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            @if ($needsDestination)
                <div id="destination-field">
                    <label class="mb-1 block text-sm font-medium text-ink-900">Heading to <x-required /></label>
                    <select id="scan-destination" class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                        <option value="">{{ $destinationSameCity ? '— Select an origin first —' : '— Select —' }}</option>
                        @foreach ($destinationHubs as $hub)
                            <option value="{{ $hub->id }}">{{ $hub->name }} ({{ $hub->code }})</option>
                        @endforeach
                    </select>
                    @if ($destinationSameCity)
                        <p class="mt-1 text-xs text-ink-500">Only nearby locations in the same city — for a different city, use a Manifest instead.</p>
                    @endif
                </div>
            @endif
            @if ($needsHandoff)
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink-900">Handed to <span class="text-xs font-normal text-ink-500">(driver/rider — optional)</span></label>
                    <select id="scan-handoff" class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                        <option value="">— Not specified —</option>
                        @foreach ($riders as $rider)
                            <option value="{{ $rider->id }}">{{ $rider->name }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-ink-500">Whoever it's for delivery or a general departure — this is who's physically carrying it out.</p>
                </div>
            @endif
        </div>

        @unless ($availableStatuses->isNotEmpty())
            <p class="mt-4 text-sm text-status-exception">No scan status is currently configured for {{ $typeLabel }} — add or rename one under Scan Statuses first.</p>
        @endunless

        <div class="mt-5 flex flex-wrap items-end gap-3 rounded-lg border border-dashed border-line p-3">
            <div class="flex-1 min-w-[220px]">
                <label class="mb-1 block text-xs font-medium text-ink-900">Scan or type a tracking, manifest, or trip number</label>
                <input type="text" id="scan-input" disabled placeholder="Select the fields above first"
                       class="w-full rounded-md border border-line px-3 py-2 text-sm font-mono outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20 disabled:bg-surface-50 disabled:text-ink-400" autocomplete="off">
            </div>
            <button type="button" id="camera-scan-btn" disabled class="rounded-md border border-line px-3 py-2 text-sm font-medium text-ink-700 hover:bg-surface-50 disabled:opacity-40">
                📷 Scan with camera
            </button>
            <span id="scan-feedback" class="text-xs"></span>
        </div>

        <div id="camera-scanner" class="mt-3 hidden max-w-sm overflow-hidden rounded-lg border border-line"></div>
    </div>

    <div id="pending-section" class="mt-4 hidden rounded-xl border border-line bg-surface-0 shadow-sm p-5">
        <div class="mb-3 flex items-center justify-between">
            <p class="text-sm font-semibold text-ink-900">Ready to confirm (<span id="pending-count">0</span>)</p>
            <button type="button" id="confirm-btn" class="rounded-md bg-[var(--brand-primary)] px-4 py-2 text-sm font-semibold text-white shadow-sm hover:opacity-90">
                Confirm
            </button>
        </div>
        <div id="pending-items" class="space-y-2"></div>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <p class="mb-3 text-sm font-semibold text-status-delivered">Confirmed</p>
            <div id="success-log" class="space-y-2">
                <p class="text-sm text-ink-500">Nothing confirmed yet.</p>
            </div>
        </div>
        <div>
            <p class="mb-3 text-sm font-semibold text-status-exception">Errors — please review</p>
            <div id="error-log" class="space-y-2">
                <p class="text-sm text-ink-500">No errors.</p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <script>
        (function () {
            const isMultiChoice = @json($isMultiChoice);
            const needsDestination = @json($needsDestination);
            const destinationSameCity = @json($destinationSameCity);
            const lockedHubId = @json($lockedHubId);
            const lockedOutletId = @json($lockedOutletId);
            const typeLabel = @json($typeLabel);
            const statusEl = document.getElementById('scan-status');
            const hubSelect = document.getElementById('scan-hub');
            const outletSelect = document.getElementById('scan-outlet');
            const destinationSelect = document.getElementById('scan-destination');
            const destinationField = document.getElementById('destination-field');
            const scanInput = document.getElementById('scan-input');
            const cameraBtn = document.getElementById('camera-scan-btn');
            const scanFeedback = document.getElementById('scan-feedback');
            const pendingSection = document.getElementById('pending-section');
            const pendingItems = document.getElementById('pending-items');
            const pendingCount = document.getElementById('pending-count');
            const confirmBtn = document.getElementById('confirm-btn');
            const successLog = document.getElementById('success-log');
            const errorLog = document.getElementById('error-log');
            let successStarted = false;
            let errorStarted = false;

            // tracking_number -> full verification detail, so what's
            // shown for review and what's actually submitted stay the
            // same list. Removing an item here is exactly the "I
            // spotted a mistake" escape hatch — nothing is recorded
            // until Confirm is pressed.
            const pending = new Map();

            function currentStatus() {
                return statusEl.value;
            }

            function destinationApplies() {
                return needsDestination && currentStatus() !== 'out_for_delivery';
            }

            function readyToScan() {
                if (! currentStatus()) return false;
                if (destinationApplies() && (! destinationSelect || ! destinationSelect.value)) return false;
                return true;
            }

            function refreshEnabled() {
                if (destinationField) { destinationField.classList.toggle('hidden', ! destinationApplies()); }
                const ready = readyToScan();
                scanInput.disabled = ! ready;
                cameraBtn.disabled = ! ready;
                scanInput.placeholder = ready ? 'Focus here, then scan — or type and press Enter' : 'Fill in the fields above first';
                if (ready) scanInput.focus();
            }
            if (isMultiChoice) { statusEl.addEventListener('change', refreshEnabled); }
            if (destinationSelect) { destinationSelect.addEventListener('change', refreshEnabled); }

            function loadNearbyDestinations() {
                if (! destinationSameCity || ! destinationSelect) return;
                const hubId = lockedHubId || hubSelect.value || null;
                const outletId = lockedOutletId || outletSelect.value || null;
                if (! hubId && ! outletId) {
                    destinationSelect.innerHTML = '<option value="">— Select an origin first —</option>';
                    return;
                }
                const params = new URLSearchParams();
                if (outletId) { params.set('outlet_id', outletId); } else { params.set('hub_id', hubId); }

                fetch(@json(route('operational-scans.nearby-destinations')) + '?' + params.toString())
                    .then(r => r.json())
                    .then(function (data) {
                        destinationSelect.innerHTML = '<option value="">— Select —</option>';
                        (data.hubs || []).forEach(function (hub) {
                            const opt = document.createElement('option');
                            opt.value = hub.id;
                            opt.textContent = hub.name + ' (' + hub.code + ')';
                            destinationSelect.appendChild(opt);
                        });
                    })
                    .catch(function () {
                        destinationSelect.innerHTML = '<option value="">Could not load nearby locations</option>';
                    });
            }
            if (destinationSameCity) { loadNearbyDestinations(); }

            if (hubSelect.tagName === 'SELECT') {
                hubSelect.addEventListener('change', function () { if (this.value) outletSelect.value = ''; loadNearbyDestinations(); });
                outletSelect.addEventListener('change', function () { if (this.value) hubSelect.value = ''; loadNearbyDestinations(); });
            }

            function renderPending() {
                pendingCount.textContent = pending.size;
                pendingSection.classList.toggle('hidden', pending.size === 0);
                pendingItems.innerHTML = '';
                pending.forEach(function (shipment, trackingNumber) {
                    const row = document.createElement('div');
                    row.className = 'flex items-start justify-between gap-3 rounded-lg border border-line p-3';
                    const lastScan = shipment.last_scan_date
                        ? new Date(shipment.last_scan_date).toLocaleString() + (shipment.last_scan_location ? ' — ' + shipment.last_scan_location : '')
                        : 'No scans yet';
                    row.innerHTML = `
                        <div class="text-sm">
                            <p class="font-mono font-medium text-ink-900">${shipment.tracking_number}</p>
                            <p class="text-xs text-ink-500">${shipment.receiver_name || '—'} ${shipment.receiver_phone ? '· ' + shipment.receiver_phone : ''}</p>
                            <p class="text-xs text-ink-500">${shipment.origin || '—'} → ${shipment.destination || '—'} · ${shipment.quantity || 1} pc(s) ${shipment.weight_kg ? '· ' + shipment.weight_kg + ' kg' : ''}</p>
                            <p class="text-xs text-ink-500">Current: ${shipment.current_status} · Last scan: ${lastScan}</p>
                        </div>
                    `;
                    const removeBtn = document.createElement('button');
                    removeBtn.type = 'button';
                    removeBtn.textContent = 'Remove';
                    removeBtn.className = 'shrink-0 text-xs font-medium text-status-exception hover:underline';
                    removeBtn.onclick = function () {
                        pending.delete(trackingNumber);
                        renderPending();
                    };
                    row.appendChild(removeBtn);
                    pendingItems.appendChild(row);
                });
            }

            function lookupAndAdd(number) {
                scanFeedback.textContent = 'Looking up…';
                scanFeedback.className = 'text-xs text-ink-500';

                fetch(@json(route('operational-scans.lookup', ['type' => $type])), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': @json(csrf_token()) },
                    body: JSON.stringify({ number: number }),
                })
                    .then(r => r.json().then(data => ({ ok: r.ok, data: data })))
                    .then(function (result) {
                        if (! result.ok || ! result.data.found) {
                            scanFeedback.textContent = result.data.message || 'Not found.';
                            scanFeedback.className = 'text-xs text-status-exception';
                            return;
                        }
                        let added = 0;
                        (result.data.shipments || []).forEach(function (shipment) {
                            if (! pending.has(shipment.tracking_number)) {
                                pending.set(shipment.tracking_number, shipment);
                                added++;
                            }
                        });
                        renderPending();
                        scanFeedback.textContent = added > 1 ? `✓ Added ${added} shipments` : (added === 1 ? '✓ Added — verify the details' : 'Already in the list.');
                        scanFeedback.className = 'text-xs text-status-delivered';
                    })
                    .catch(function () {
                        scanFeedback.textContent = 'Lookup failed — try again.';
                        scanFeedback.className = 'text-xs text-status-exception';
                    });
            }

            scanInput.addEventListener('keydown', function (e) {
                if (e.key !== 'Enter') return;
                e.preventDefault();
                if (! readyToScan()) return;
                const value = scanInput.value.trim();
                scanInput.value = '';
                if (! value) return;
                lookupAndAdd(value);
            });

            let html5QrCode = null;
            cameraBtn.addEventListener('click', function () {
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
                        if (! readyToScan()) return;
                        lookupAndAdd(decodedText);
                    },
                    function () { /* per-frame scan failures are normal, ignore */ }
                ).catch(function () {
                    scanFeedback.textContent = 'Could not access camera.';
                    scanFeedback.className = 'text-xs text-status-exception';
                    container.classList.add('hidden');
                });
            });

            function logSuccess(message) {
                if (! successStarted) { successLog.innerHTML = ''; successStarted = true; }
                const row = document.createElement('div');
                row.className = 'rounded-lg border border-status-delivered/30 bg-status-delivered/5 p-3 text-sm text-status-delivered';
                row.textContent = message;
                successLog.prepend(row);
            }
            function logError(message) {
                if (! errorStarted) { errorLog.innerHTML = ''; errorStarted = true; }
                const row = document.createElement('div');
                row.className = 'rounded-lg border border-status-exception/30 bg-status-exception/5 p-3 text-sm text-status-exception';
                row.textContent = message;
                errorLog.prepend(row);
            }

            confirmBtn.addEventListener('click', function () {
                const count = pending.size;
                if (count === 0) return;
                if (! confirm(`Confirm ${typeLabel.toLowerCase()} for ${count} shipment${count === 1 ? '' : 's'}?`)) {
                    return;
                }

                confirmBtn.disabled = true;
                confirmBtn.textContent = 'Confirming…';

                fetch(@json(route('operational-scans.store', ['type' => $type])), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': @json(csrf_token()) },
                    body: JSON.stringify({
                        shipment_ids: Array.from(pending.values()).map(s => s.id),
                        status: currentStatus(),
                        hub_id: lockedHubId || hubSelect.value || null,
                        outlet_id: lockedOutletId || outletSelect.value || null,
                        destination_hub_id: destinationApplies() ? (destinationSelect ? destinationSelect.value : null) : null,
                        handed_to_user_id: (function () { const el = document.getElementById('scan-handoff'); return el ? (el.value || null) : null; })(),
                    }),
                })
                    .then(r => r.json())
                    .then(function (data) {
                        (data.results || []).forEach(function (result) {
                            if (result.success) {
                                logSuccess('✓ ' + result.tracking_number + ' — ' + typeLabel.toLowerCase() + ' recorded');
                            } else {
                                logError('✗ ' + result.tracking_number + ' — ' + result.message);
                            }
                        });
                        pending.clear();
                        renderPending();
                    })
                    .catch(function () {
                        logError('Something went wrong submitting this batch — try again.');
                    })
                    .finally(function () {
                        confirmBtn.disabled = false;
                        confirmBtn.textContent = 'Confirm';
                    });
            });

            refreshEnabled();
        })();
    </script>

</x-layouts.app>
