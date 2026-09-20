<x-layouts.app :title="'Delivery Scan'">

    <div class="mb-6">
        <p class="text-2xl font-semibold text-ink-900">Delivery Scan</p>
        <p class="mt-1 text-sm text-ink-500">Scan each waybill to verify it first — then record who received them, once, for the whole batch.</p>
    </div>

    <div class="rounded-xl border border-line bg-surface-0 shadow-sm p-5">
        <p class="mb-3 text-sm font-semibold text-ink-900">Location</p>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            @if ($lockedLocationLabel)
                <div>
                    <div class="rounded-md border border-line bg-surface-50 px-3 py-2 text-sm text-ink-700">{{ $lockedLocationLabel }} <span class="text-xs text-ink-500">(your assigned location)</span></div>
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
        </div>
    </div>

    <div class="mt-4 rounded-xl border border-line bg-surface-0 shadow-sm p-5">
        <p class="mb-1 text-sm font-semibold text-ink-900">Step 1 — Scan waybills</p>
        <p class="mb-4 text-xs text-ink-500">Each one is looked up and shown below so you can check it's the right package before confirming.</p>

        <div class="flex flex-wrap items-end gap-3 rounded-lg border border-dashed border-line p-3">
            <div class="flex-1 min-w-[220px]">
                <label class="mb-1 block text-xs font-medium text-ink-900">Scan or type a tracking number</label>
                <input type="text" id="scan-input" placeholder="Focus here, then scan — or type and press Enter"
                       class="w-full rounded-md border border-line px-3 py-2 text-sm font-mono outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20" autocomplete="off">
            </div>
            <button type="button" id="camera-scan-btn" class="rounded-md border border-line px-3 py-2 text-sm font-medium text-ink-700 hover:bg-surface-50">📷 Scan with camera</button>
            <span id="scan-feedback" class="text-xs"></span>
        </div>

        <div id="camera-scanner" class="mt-3 hidden max-w-sm overflow-hidden rounded-lg border border-line"></div>

        <div id="pending-list" class="mt-4 hidden">
            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-ink-500">Ready for delivery (<span id="pending-count">0</span>)</p>
            <div class="max-h-[28rem] overflow-y-auto overflow-x-auto rounded-lg border border-line">
                <table class="w-full text-sm">
                    <thead class="sticky top-0 bg-surface-50">
                        <tr class="border-b border-line text-left text-xs uppercase tracking-wide text-ink-500">
                            <th class="p-2.5">Tracking #</th>
                            <th class="p-2.5">Registered to</th>
                            <th class="p-2.5">Phone</th>
                            <th class="p-2.5">Address</th>
                            <th class="p-2.5">Pieces</th>
                            <th class="p-2.5">Service type</th>
                            <th class="p-2.5"></th>
                        </tr>
                    </thead>
                    <tbody id="pending-items"></tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="delivery-details-section" class="mt-4 hidden rounded-xl border border-line bg-surface-0 shadow-sm p-5">
        <p class="mb-1 text-sm font-semibold text-ink-900">Step 2 — Who received it</p>
        <p class="mb-4 text-xs text-ink-500">Applies to every waybill scanned above — one signature/photo covers the whole batch.</p>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label class="mb-1 block text-sm font-medium text-ink-900">Received by <x-required /></label>
                <input type="text" id="receiver-name" placeholder="Name of person receiving" required
                       class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-ink-900">Photo evidence <span class="text-xs font-normal text-ink-500">(optional)</span></label>
                <input type="file" id="photo-input" accept="image/*" capture="environment"
                       class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
            </div>
            <div class="sm:col-span-2">
                <label class="mb-1 block text-sm font-medium text-ink-900">Signature <span class="text-xs font-normal text-ink-500">(optional)</span></label>
                <canvas id="signature-pad" width="500" height="150" class="w-full max-w-md rounded-md border border-line bg-white touch-none"></canvas>
                <button type="button" id="clear-signature" class="mt-1 text-xs text-ink-500 hover:underline">Clear signature</button>
            </div>
        </div>

        <div class="mt-5 flex justify-end">
            <button type="button" id="confirm-btn" disabled class="rounded-md bg-[var(--brand-primary)] px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-40">
                Confirm delivery
            </button>
        </div>
    </div>

    <div class="mt-6">
        <p class="mb-3 text-sm font-semibold text-ink-900">Confirmed this session</p>
        <div id="scan-log" class="space-y-2">
            <p class="text-sm text-ink-500">Nothing confirmed yet.</p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <script>
        (function () {
            const lockedHubId = @json($lockedHubId);
            const lockedOutletId = @json($lockedOutletId);
            const hubSelect = document.getElementById('scan-hub');
            const outletSelect = document.getElementById('scan-outlet');
            const scanInput = document.getElementById('scan-input');
            const cameraBtn = document.getElementById('camera-scan-btn');
            const scanFeedback = document.getElementById('scan-feedback');
            const pendingList = document.getElementById('pending-list');
            const pendingItems = document.getElementById('pending-items');
            const pendingCount = document.getElementById('pending-count');
            const detailsSection = document.getElementById('delivery-details-section');
            const receiverInput = document.getElementById('receiver-name');
            const confirmBtn = document.getElementById('confirm-btn');
            const scanLog = document.getElementById('scan-log');
            let logStarted = false;

            // tracking_number -> full shipment detail, so what's
            // rendered for verification and what's actually submitted
            // stay the same list.
            const pending = new Map();

            function currentLocation() {
                return {
                    hub_id: lockedHubId || (hubSelect ? hubSelect.value : null) || null,
                    outlet_id: lockedOutletId || (outletSelect ? outletSelect.value : null) || null,
                };
            }

            function refreshConfirmEnabled() {
                confirmBtn.disabled = pending.size === 0 || ! receiverInput.value.trim();
            }
            receiverInput.addEventListener('input', refreshConfirmEnabled);

            function renderPending() {
                pendingCount.textContent = pending.size;
                pendingList.classList.toggle('hidden', pending.size === 0);
                detailsSection.classList.toggle('hidden', pending.size === 0);
                pendingItems.innerHTML = '';
                pending.forEach(function (shipment, trackingNumber) {
                    const row = document.createElement('tr');
                    row.className = 'border-b border-line last:border-0';
                    row.innerHTML = `
                        <td class="p-2.5 font-mono text-ink-900">${shipment.tracking_number}</td>
                        <td class="p-2.5 text-ink-700">${shipment.receiver_name || '—'}</td>
                        <td class="p-2.5 text-ink-700">${shipment.receiver_phone || '—'}</td>
                        <td class="p-2.5 text-ink-700">${shipment.destination_address || '—'}</td>
                        <td class="p-2.5 text-ink-700">${shipment.quantity || 1}</td>
                        <td class="p-2.5 text-ink-700">${shipment.service_type || '—'}</td>
                    `;
                    const removeCell = document.createElement('td');
                    removeCell.className = 'p-2.5';
                    const removeBtn = document.createElement('button');
                    removeBtn.type = 'button';
                    removeBtn.textContent = 'Remove';
                    removeBtn.className = 'text-xs font-medium text-status-exception hover:underline';
                    removeBtn.onclick = function () {
                        pending.delete(trackingNumber);
                        renderPending();
                        refreshConfirmEnabled();
                    };
                    removeCell.appendChild(removeBtn);
                    row.appendChild(removeCell);
                    pendingItems.appendChild(row);
                });
                refreshConfirmEnabled();
            }

            function lookupAndAdd(number) {
                scanFeedback.textContent = 'Looking up…';
                scanFeedback.className = 'text-xs text-ink-500';

                fetch(@json(route('operational-scans.delivery.lookup')), {
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
                        scanFeedback.textContent = added > 1 ? `✓ Added ${added} shipments — verify the details` : (added === 1 ? '✓ Added — verify the details match' : 'Already in this batch.');
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
                    function (decodedText) { lookupAndAdd(decodedText); },
                    function () { /* per-frame scan failures are normal, ignore */ }
                ).catch(function () {
                    scanFeedback.textContent = 'Could not access camera.';
                    scanFeedback.className = 'text-xs text-status-exception';
                    container.classList.add('hidden');
                });
            });

            // Signature pad — plain canvas drawing, no library needed.
            let signatureDataUrl = null;
            const canvas = document.getElementById('signature-pad');
            const ctx = canvas.getContext('2d');
            ctx.strokeStyle = '#111';
            ctx.lineWidth = 2;
            ctx.lineJoin = 'round';
            ctx.lineCap = 'round';
            let drawing = false;
            let hasDrawn = false;

            function pos(e) {
                const rect = canvas.getBoundingClientRect();
                const scaleX = canvas.width / rect.width;
                const scaleY = canvas.height / rect.height;
                const point = e.touches ? e.touches[0] : e;
                return { x: (point.clientX - rect.left) * scaleX, y: (point.clientY - rect.top) * scaleY };
            }
            function start(e) { drawing = true; hasDrawn = true; const p = pos(e); ctx.beginPath(); ctx.moveTo(p.x, p.y); }
            function move(e) { if (! drawing) return; e.preventDefault(); const p = pos(e); ctx.lineTo(p.x, p.y); ctx.stroke(); }
            function end() { drawing = false; if (hasDrawn) signatureDataUrl = canvas.toDataURL('image/png'); }
            canvas.addEventListener('mousedown', start);
            canvas.addEventListener('mousemove', move);
            window.addEventListener('mouseup', end);
            canvas.addEventListener('touchstart', start);
            canvas.addEventListener('touchmove', move);
            canvas.addEventListener('touchend', end);
            document.getElementById('clear-signature').addEventListener('click', function () {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                hasDrawn = false;
                signatureDataUrl = null;
            });

            function uploadEvidence(kind, dataUrl) {
                return fetch(@json(route('operational-scans.upload-evidence')), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': @json(csrf_token()) },
                    body: JSON.stringify({ kind: kind, data_url: dataUrl }),
                })
                    .then(r => r.json())
                    .then(data => data.path || null)
                    .catch(() => null);
            }

            function readPhotoAsDataUrl() {
                const input = document.getElementById('photo-input');
                if (! input || ! input.files || ! input.files[0]) return Promise.resolve(null);
                return new Promise(function (resolve) {
                    const reader = new FileReader();
                    reader.onload = function () { resolve(reader.result); };
                    reader.onerror = function () { resolve(null); };
                    reader.readAsDataURL(input.files[0]);
                });
            }

            function logResult(success, message) {
                if (! logStarted) { scanLog.innerHTML = ''; logStarted = true; }
                const row = document.createElement('div');
                row.className = 'rounded-lg border p-3 text-sm ' + (success ? 'border-status-delivered/30 bg-status-delivered/5 text-status-delivered' : 'border-status-exception/30 bg-status-exception/5 text-status-exception');
                row.textContent = message;
                scanLog.prepend(row);
            }

            confirmBtn.addEventListener('click', function () {
                const count = pending.size;
                const receiverName = receiverInput.value.trim();
                if (! confirm(`Confirm ${count} waybill${count === 1 ? '' : 's'} delivered to ${receiverName}?`)) {
                    return;
                }

                confirmBtn.disabled = true;
                confirmBtn.textContent = 'Confirming…';

                Promise.all([
                    signatureDataUrl ? uploadEvidence('signature', signatureDataUrl) : Promise.resolve(null),
                    readPhotoAsDataUrl().then(dataUrl => dataUrl ? uploadEvidence('photo', dataUrl) : null),
                ]).then(function (paths) {
                    const location = currentLocation();
                    return fetch(@json(route('operational-scans.delivery.store')), {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': @json(csrf_token()) },
                        body: JSON.stringify({
                            shipment_ids: Array.from(pending.values()).map(s => s.id),
                            receiver_name: receiverName,
                            signature_path: paths[0],
                            photo_path: paths[1],
                            hub_id: location.hub_id,
                            outlet_id: location.outlet_id,
                        }),
                    });
                })
                    .then(r => r.json())
                    .then(function (data) {
                        (data.results || []).forEach(function (result) {
                            logResult(result.success, (result.success ? '✓ ' : '✗ ') + result.tracking_number + (result.success ? ' delivered to ' + receiverName : ' — ' + result.message));
                        });
                        pending.clear();
                        renderPending();
                        receiverInput.value = '';
                        ctx.clearRect(0, 0, canvas.width, canvas.height);
                        hasDrawn = false;
                        signatureDataUrl = null;
                        document.getElementById('photo-input').value = '';
                    })
                    .catch(function () {
                        logResult(false, 'Something went wrong submitting this batch — try again.');
                    })
                    .finally(function () {
                        confirmBtn.disabled = false;
                        confirmBtn.textContent = 'Confirm delivery';
                    });
            });
        })();
    </script>

</x-layouts.app>
