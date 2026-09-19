<x-layouts.app :title="$typeLabel">

    <div class="mb-6">
        <p class="text-2xl font-semibold text-ink-900">{{ $typeLabel }}</p>
        <p class="mt-1 text-sm text-ink-500">Pick a location if needed, then scan shipments one after another — each one takes effect immediately.</p>
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
        </div>

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
            const statusEl = document.getElementById('scan-status');
            const hubSelect = document.getElementById('scan-hub');
            const outletSelect = document.getElementById('scan-outlet');
            const scanInput = document.getElementById('scan-input');
            const cameraBtn = document.getElementById('camera-scan-btn');
            const scanLog = document.getElementById('scan-log');
            let logStarted = false;

            function currentStatus() {
                return statusEl.value;
            }

            function refreshEnabled() {
                const ready = !! currentStatus();
                scanInput.disabled = ! ready;
                cameraBtn.disabled = ! ready;
                scanInput.placeholder = ready ? 'Focus here, then scan — or type and press Enter' : (isMultiChoice ? 'Select a reason above first' : 'Nothing configured for this scan yet');
                if (ready) scanInput.focus();
            }
            if (isMultiChoice) {
                statusEl.addEventListener('change', refreshEnabled);
            }

            hubSelect.addEventListener('change', function () { if (this.value) outletSelect.value = ''; });
            outletSelect.addEventListener('change', function () { if (this.value) hubSelect.value = ''; });

            function logResult(success, message) {
                if (! logStarted) { scanLog.innerHTML = ''; logStarted = true; }
                const row = document.createElement('div');
                row.className = 'rounded-lg border p-3 text-sm ' + (success ? 'border-status-delivered/30 bg-status-delivered/5 text-status-delivered' : 'border-status-exception/30 bg-status-exception/5 text-status-exception');
                row.textContent = message;
                scanLog.prepend(row);
            }

            function submitScan(trackingNumber) {
                fetch(window.location.pathname, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': @json(csrf_token()) },
                    body: JSON.stringify({
                        tracking_number: trackingNumber,
                        status: currentStatus(),
                        hub_id: hubSelect.value || null,
                        outlet_id: outletSelect.value || null,
                    }),
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
