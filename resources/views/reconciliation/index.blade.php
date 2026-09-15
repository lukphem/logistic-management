<x-layouts.app title="Reconciliation">

    <div class="mb-6">
        <p class="text-2xl font-semibold text-ink-900">Reconciliation</p>
        <p class="mt-1 text-sm text-ink-500">Cash physically collected — at an outlet counter or by a rider on delivery — sits here until it's settled to the company via Paystack. Select what you're settling now and pay the total in one transaction.</p>
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

    <form method="POST" action="{{ route('reconciliation.store') }}" id="reconciliation-form">
        @csrf

        <div class="rounded-xl border border-line bg-surface-0 shadow-sm">
            @if ($shipments->isEmpty())
                <p class="p-6 text-sm text-ink-500">Nothing outstanding right now — every cash-collected shipment you have access to has already been settled.</p>
            @else
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-line text-left text-xs uppercase tracking-wide text-ink-500">
                            <th class="p-3"><input type="checkbox" id="select-all" class="rounded border-line"></th>
                            <th class="p-3">Tracking #</th>
                            <th class="p-3">Source</th>
                            <th class="p-3">Collected</th>
                            <th class="p-3 text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($shipments as $shipment)
                            <tr class="border-b border-line last:border-0">
                                <td class="p-3">
                                    <input type="checkbox" name="shipment_ids[]" value="{{ $shipment->id }}" class="reconcile-checkbox rounded border-line" data-amount="{{ $shipment->total_amount }}">
                                </td>
                                <td class="p-3">
                                    <a href="{{ route('shipments.show', $shipment) }}" class="font-mono text-[var(--brand-primary)] hover:underline">{{ $shipment->tracking_number }}</a>
                                </td>
                                <td class="p-3 text-ink-700">
                                    @if ($shipment->is_cod && $shipment->assignedRider)
                                        COD · {{ $shipment->assignedRider->name }}
                                    @elseif ($shipment->currentOutlet)
                                        {{ $shipment->currentOutlet->name }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="p-3 text-ink-500">{{ $shipment->cash_collected_at->format('d M Y, H:i') }}</td>
                                <td class="p-3 text-right font-mono text-ink-900">{{ number_format($shipment->total_amount, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        @if ($shipments->isNotEmpty())
            <div class="mt-4 flex items-center justify-between rounded-xl border border-line bg-surface-0 shadow-sm p-4">
                <div class="text-sm text-ink-700">
                    <span id="selected-count">0</span> selected — total
                    <span id="selected-total" class="font-mono font-semibold text-ink-900">0.00</span>
                </div>
                <button type="submit" id="settle-button" disabled class="rounded-md bg-[var(--brand-primary)] px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-40">
                    Settle selected via Paystack
                </button>
            </div>
        @endif
    </form>

    @if ($pastSettlements->isNotEmpty())
        <div class="mt-8">
            <p class="mb-3 text-sm font-semibold text-ink-900">Your recent settlements</p>
            <div class="rounded-xl border border-line bg-surface-0 shadow-sm overflow-hidden">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-line text-left text-xs uppercase tracking-wide text-ink-500">
                            <th class="p-3">Date</th>
                            <th class="p-3">Status</th>
                            <th class="p-3 text-right">Amount</th>
                            <th class="p-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($pastSettlements as $settlement)
                            <tr class="border-b border-line last:border-0">
                                <td class="p-3 text-ink-500">{{ $settlement->created_at->format('d M Y, H:i') }}</td>
                                <td class="p-3">
                                    @if ($settlement->status === 'paid')
                                        <span class="text-status-delivered">Paid</span>
                                    @elseif ($settlement->status === 'failed')
                                        <span class="text-status-exception">Failed</span>
                                    @else
                                        <span class="text-ink-500">Pending</span>
                                    @endif
                                </td>
                                <td class="p-3 text-right font-mono text-ink-900">{{ number_format($settlement->total_amount, 2) }}</td>
                                <td class="p-3 text-right">
                                    @if ($settlement->status !== 'paid')
                                        <a href="{{ route('payments.pay-settlement', $settlement) }}" class="text-xs font-medium text-[var(--brand-primary)] hover:underline">Retry payment</a>
                                        @if ($settlement->payment_reference)
                                            <form method="POST" action="{{ route('payments.check-status') }}" class="inline">
                                                @csrf
                                                <input type="hidden" name="reference" value="{{ $settlement->payment_reference }}">
                                                <button type="submit" class="ml-2 text-xs font-medium text-ink-500 hover:underline">Check status</button>
                                            </form>
                                        @endif
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <script>
        (function () {
            const selectAll = document.getElementById('select-all');
            const checkboxes = document.querySelectorAll('.reconcile-checkbox');
            const countEl = document.getElementById('selected-count');
            const totalEl = document.getElementById('selected-total');
            const settleButton = document.getElementById('settle-button');

            function refresh() {
                let count = 0;
                let total = 0;
                checkboxes.forEach(function (cb) {
                    if (cb.checked) {
                        count++;
                        total += parseFloat(cb.dataset.amount || '0');
                    }
                });
                if (countEl) countEl.textContent = count;
                if (totalEl) totalEl.textContent = total.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                if (settleButton) settleButton.disabled = count === 0;
            }

            if (selectAll) {
                selectAll.addEventListener('change', function () {
                    checkboxes.forEach(function (cb) { cb.checked = selectAll.checked; });
                    refresh();
                });
            }
            checkboxes.forEach(function (cb) { cb.addEventListener('change', refresh); });
            refresh();
        })();
    </script>

</x-layouts.app>
