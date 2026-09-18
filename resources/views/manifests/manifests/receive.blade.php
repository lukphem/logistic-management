<x-layouts.app :title="'Receive ' . $manifest->manifest_number">

    <div class="mb-6">
        <p class="text-2xl font-semibold text-ink-900">Receive {{ $manifest->manifest_number }}</p>
        <p class="mt-1 text-sm text-ink-500">
            From trip {{ $manifest->trip->trip_number }} · {{ $manifest->manifestShipments->count() }} shipment(s) expected.
            Everything defaults to "Received" — flag anything damaged or not physically present.
        </p>
    </div>

    @if ($errors->any())
        <div class="mb-4 rounded-xl border border-status-exception/30 bg-status-exception/5 px-4 py-3 text-sm text-status-exception">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="mb-4 flex flex-wrap items-end gap-3 rounded-lg border border-dashed border-line bg-surface-0 p-3">
        <div class="flex-1 min-w-[220px]">
            <label class="mb-1 block text-xs font-medium text-ink-900">Scan to confirm physically present</label>
            <input type="text" id="scan-input" placeholder="Scan a tracking number to check it off"
                   class="w-full rounded-md border border-line px-3 py-2 text-sm font-mono outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20" autocomplete="off">
        </div>
        <span id="scan-feedback" class="text-xs"></span>
    </div>

    <form method="POST" action="{{ route('manifests.store-receive', $manifest) }}" id="receive-form" class="space-y-3">
        @csrf

        @foreach ($manifest->manifestShipments as $manifestShipment)
            @php $shipment = $manifestShipment->shipment; @endphp
            <div class="manifest-row rounded-xl border border-line bg-surface-0 shadow-sm p-4" data-tracking="{{ $shipment->tracking_number }}">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="font-mono text-sm font-semibold text-ink-900">
                            {{ $shipment->tracking_number }}
                            <span class="confirmed-badge ml-2 hidden text-xs font-medium text-status-delivered">✓ Confirmed</span>
                        </p>
                        <p class="text-xs text-ink-500">{{ $shipment->receiver_name }} — {{ $shipment->destinationCity?->name ?? $shipment->destination_address }}</p>
                    </div>
                    <div class="flex items-center gap-4">
                        <label class="flex items-center gap-1.5 text-sm text-ink-900">
                            <input type="radio" name="conditions[{{ $shipment->id }}]" value="received" checked class="condition-radio border-line">
                            Received
                        </label>
                        <label class="flex items-center gap-1.5 text-sm text-ink-900">
                            <input type="radio" name="conditions[{{ $shipment->id }}]" value="damaged" class="condition-radio border-line">
                            Damaged
                        </label>
                        <label class="flex items-center gap-1.5 text-sm text-ink-900">
                            <input type="radio" name="conditions[{{ $shipment->id }}]" value="missing" class="condition-radio border-line">
                            Missing
                        </label>
                    </div>
                </div>
                <div class="note-field mt-2 hidden">
                    <input type="text" name="notes[{{ $shipment->id }}]" placeholder="Reason / note (optional)"
                           class="w-full rounded-md border border-line px-3 py-2 text-xs outline-none focus:border-[var(--brand-primary)]">
                </div>
            </div>
        @endforeach

        <div class="flex justify-end gap-3 pb-8 pt-2">
            <a href="{{ route('manifest-trips.show', $manifest->trip) }}" class="rounded-md border border-line px-4 py-2 text-sm font-medium text-ink-700 hover:bg-surface-50">Cancel</a>
            <button type="submit" class="rounded-md bg-[var(--brand-primary)] px-5 py-2 text-sm font-semibold text-white shadow-sm hover:opacity-90">Confirm receipt</button>
        </div>
    </form>

    <script>
        (function () {
            document.querySelectorAll('.manifest-row').forEach(function (row) {
                const radios = row.querySelectorAll('.condition-radio');
                const noteField = row.querySelector('.note-field');
                radios.forEach(function (radio) {
                    radio.addEventListener('change', function () {
                        noteField.classList.toggle('hidden', radio.value === 'received' && radio.checked);
                    });
                });
            });

            const scanInput = document.getElementById('scan-input');
            const scanFeedback = document.getElementById('scan-feedback');

            scanInput.addEventListener('keydown', function (e) {
                if (e.key !== 'Enter') return;
                e.preventDefault();
                const value = scanInput.value.trim();
                scanInput.value = '';
                if (! value) return;

                const row = document.querySelector('.manifest-row[data-tracking="' + value + '"]');
                if (! row) {
                    scanFeedback.textContent = value + ' is not on this manifest.';
                    scanFeedback.className = 'text-xs text-status-exception';
                    return;
                }

                row.querySelector('input.condition-radio[value="received"]').checked = true;
                row.querySelector('.note-field').classList.add('hidden');
                row.querySelector('.confirmed-badge').classList.remove('hidden');
                row.scrollIntoView({ behavior: 'smooth', block: 'center' });

                scanFeedback.textContent = '✓ Confirmed ' + value;
                scanFeedback.className = 'text-xs text-status-delivered';
            });
        })();
    </script>

</x-layouts.app>
