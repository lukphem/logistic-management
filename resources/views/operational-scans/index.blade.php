<x-layouts.app :title="$typeLabel">

    <div class="mb-6">
        <p class="text-2xl font-semibold text-ink-900">{{ $typeLabel }}</p>
        <p class="mt-1 text-sm text-ink-500">Scan shipments one after another — each one takes effect immediately.</p>
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
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink-900">Heading to <x-required /></label>
                    <select id="scan-destination" class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                        <option value="">— Select —</option>
                        @foreach ($destinationHubs as $hub)
                            <option value="{{ $hub->id }}">{{ $hub->name }} ({{ $hub->code }})</option>
                        @endforeach
                    </select>
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

        @if ($needsEvidence)
            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink-900">Received by <span class="text-xs font-normal text-ink-500">(who actually signed for it)</span></label>
                    <input type="text" id="scan-receiver-name" placeholder="Name of person receiving"
                           class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink-900">Photo evidence <span class="text-xs font-normal text-ink-500">(optional)</span></label>
                    <input type="file" id="scan-photo-input" accept="image/*" capture="environment"
                           class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-sm font-medium text-ink-900">Signature <span class="text-xs font-normal text-ink-500">(optional)</span></label>
                    <canvas id="signature-pad" width="500" height="150" class="w-full max-w-md rounded-md border border-line bg-white touch-none"></canvas>
                    <button type="button" id="clear-signature" class="mt-1 text-xs text-ink-500 hover:underline">Clear signature</button>
                </div>
            </div>
        @endif

        @unless ($availableStatuses->isNotEmpty())
            <p class="mt-4 text-sm text-status-exception">No scan status is currently configured for {{ $typeLabel }} — add or rename one under Scan Statuses first.</p>
        @endunless

        <div class="mt-5 flex flex-wrap items-end gap-3 rounded-lg border border-dashed border-line p-3">
            <div class="flex-1 min-w-[220px]">
                <label class="mb-1 block text-xs font-medium text-ink-900">Scan or type a tracking number</label>
                <input type="text" id="scan-input" disabled placeholder="Select a reason above first"
                       class="w-full rounded-md border border-line px-3 py-2 text-sm font-mono outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20 disabled:bg-surface-50 disabled:text-ink-400" autocomplete="off">
            </div>
            <button type="button" id="camera-scan-btn" disabled class="rounded-md border border-line px-3 py-2 text-sm font-medium text-ink-700 hover:bg-surface-50 disabled:opacity-40">
                📷 Scan with camera
            </button>
        </div>

        <div id="camera-scanner" class="mt-3 hidden max-w-sm overflow-hidden rounded-lg border border-line"></div>
    </div>

    <div class="mt-6">
        <p class="mb-3 text-sm font-semibold text-ink-900">This session's scans</p>
        <div id="scan-log" class="space-y-2">
            <p class="text-sm text-ink-500">Nothing scanned yet.</p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <script>
        (function () {
            const isMultiChoice = @json($isMultiChoice);
            const needsDestination = @json($needsDestination);
            const needsEvidence = @json($needsEvidence);
            const statusEl = document.getElementById('scan-status');
            const hubSelect = document.getElementById('scan-hub');
            const outletSelect = document.getElementById('scan-outlet');
            const destinationSelect = document.getElementById('scan-destination');
            const scanInput = document.getElementById('scan-input');
            const cameraBtn = document.getElementById('camera-scan-btn');
            const scanLog = document.getElementById('scan-log');
            let logStarted = false;

            function currentStatus() {
                return statusEl.value;
            }

            function readyToScan() {
                if (! currentStatus()) return false;
                if (needsDestination && (! destinationSelect || ! destinationSelect.value)) return false;
                if (needsEvidence) {
                    const receiverEl = document.getElementById('scan-receiver-name');
                    if (! receiverEl || ! receiverEl.value.trim()) return false;
                }
                return true;
            }

            function refreshEnabled() {
                const ready = readyToScan();
                scanInput.disabled = ! ready;
                cameraBtn.disabled = ! ready;
                scanInput.placeholder = ready ? 'Focus here, then scan — or type and press Enter' : 'Fill in the fields above first';
                if (ready) scanInput.focus();
            }
            if (isMultiChoice) { statusEl.addEventListener('change', refreshEnabled); }
            if (destinationSelect) { destinationSelect.addEventListener('change', refreshEnabled); }
            if (needsEvidence) {
                const receiverEl = document.getElementById('scan-receiver-name');
                if (receiverEl) { receiverEl.addEventListener('input', refreshEnabled); }
            }

            if (hubSelect.tagName === 'SELECT') {
                hubSelect.addEventListener('change', function () { if (this.value) outletSelect.value = ''; });
                outletSelect.addEventListener('change', function () { if (this.value) hubSelect.value = ''; });
            }

            // Signature pad — plain canvas drawing, no library needed.
            let signatureDataUrl = null;
            if (needsEvidence) {
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
            }

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
                const input = document.getElementById('scan-photo-input');
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

            function submitScan(trackingNumber) {
                Promise.all([
                    needsEvidence && signatureDataUrl ? uploadEvidence('signature', signatureDataUrl) : Promise.resolve(null),
                    needsEvidence ? readPhotoAsDataUrl().then(dataUrl => dataUrl ? uploadEvidence('photo', dataUrl) : null) : Promise.resolve(null),
                ]).then(function (paths) {
                    const body = {
                        tracking_number: trackingNumber,
                        status: currentStatus(),
                        hub_id: hubSelect.value || null,
                        outlet_id: outletSelect.value || null,
                    };
                    if (needsDestination) { body.destination_hub_id = destinationSelect.value || null; }
                    const handoffSelect = document.getElementById('scan-handoff');
                    if (handoffSelect) { body.handed_to_user_id = handoffSelect.value || null; }
                    if (needsEvidence) {
                        body.receiver_name = document.getElementById('scan-receiver-name').value || null;
                        body.signature_path = paths[0];
                        body.photo_path = paths[1];
                    }

                    return fetch(window.location.pathname, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': @json(csrf_token()) },
                        body: JSON.stringify(body),
                    });
                })
                    .then(r => r.json().then(data => ({ ok: r.ok, data: data })))
                    .then(function (result) {
                        if (! result.ok) {
                            logResult(false, trackingNumber + ' — ' + (result.data.message || 'Failed.'));
                            return;
                        }
                        logResult(true, '✓ ' + result.data.tracking_number + ' — ' + result.data.receiver_name);
                    })
                    .catch(function () {
                        logResult(false, trackingNumber + ' — network error, try again.');
                    });
            }

            scanInput.addEventListener('keydown', function (e) {
                if (e.key !== 'Enter') return;
                e.preventDefault();
                if (! readyToScan()) return;
                const value = scanInput.value.trim();
                scanInput.value = '';
                if (! value) return;
                submitScan(value);
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
                        submitScan(decodedText);
                    },
                    function () { /* per-frame scan failures are normal, ignore */ }
                ).catch(function () {
                    logResult(false, 'Could not access camera.');
                    container.classList.add('hidden');
                });
            });

            refreshEnabled();
        })();
    </script>

</x-layouts.app>
