<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Batch {{ $batch->batch_number }}</title>
    <style>
        @page { size: A4; margin: 0.6in; }
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; }
        body { font-family: Arial, Helvetica, sans-serif; color: #111; font-size: 12px; line-height: 1.4; background: #ddd; }

        .toolbar { display: flex; align-items: center; padding: 14px 20px; background: #f2f2f2; border-bottom: 2px solid #ccc; }
        .print-btn { padding: 8px 16px; font-size: 13px; border: 1px solid #111; background: #111; color: #fff; border-radius: 4px; cursor: pointer; }
        @media print { .toolbar { display: none !important; } }

        .paper { max-width: 8.27in; margin: 24px auto; padding: 0.6in; background: #fff; box-shadow: 0 2px 12px rgba(0,0,0,0.2); overflow-wrap: break-word; }
        @media print {
            body { background: #fff; }
            .paper { max-width: none; margin: 0; padding: 0; box-shadow: none; }
        }

        .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 3px solid {{ $settings->color_primary ?? '#1F3864' }}; padding-bottom: 14px; margin-bottom: 16px; }
        .company img { height: 48px; max-width: 160px; object-fit: contain; margin-bottom: 6px; }
        .company-name { font-weight: bold; font-size: 18px; overflow-wrap: break-word; }
        .doc-title { text-align: right; }
        .doc-title h1 { margin: 0; font-size: 22px; color: {{ $settings->color_primary ?? '#1F3864' }}; letter-spacing: 1px; }
        .doc-title .ref-number { font-family: 'Courier New', monospace; font-size: 16px; font-weight: bold; margin-top: 4px; }
        .doc-title .doc-date { font-size: 11px; color: #666; margin-top: 2px; }

        .section-title { font-size: 11px; text-transform: uppercase; font-weight: bold; color: #555; letter-spacing: 0.5px; margin: 18px 0 6px; border-bottom: 1px solid #ddd; padding-bottom: 4px; }
        .grid-2 { display: flex; gap: 24px; }
        .grid-2 > div { flex: 1; min-width: 0; }
        .field-row { display: flex; justify-content: space-between; gap: 10px; padding: 3px 0; border-bottom: 1px dotted #ddd; }
        .field-row dt { color: #666; flex-shrink: 0; }
        .field-row dd { margin: 0; font-weight: 500; text-align: right; overflow-wrap: break-word; }

        table.shipments { width: 100%; border-collapse: collapse; margin-top: 8px; font-size: 10.5px; }
        table.shipments th { text-align: left; text-transform: uppercase; font-size: 9px; color: #555; border-bottom: 2px solid #999; padding: 5px 4px; }
        table.shipments td { padding: 5px 4px; border-bottom: 1px solid #eee; overflow-wrap: break-word; }
        table.shipments tr:last-child td { border-bottom: 1px solid #999; }

        .footer { margin-top: 30px; padding-top: 10px; border-top: 1px solid #ddd; font-size: 9px; color: #999; text-align: center; }
    </style>
</head>
<body>
    <div class="toolbar">
        <button class="print-btn" onclick="window.print()">Print</button>
    </div>

    <div class="paper">
        <div class="header">
            <div class="company">
                @if ($settings->logo_path)
                    <img src="{{ asset('storage/' . $settings->logo_path) }}" alt="{{ $settings->company_name }}">
                @endif
                <div class="company-name">{{ $settings->company_name }}</div>
            </div>
            <div class="doc-title">
                <h1>BULK BATCH</h1>
                <div class="ref-number">{{ $batch->batch_number }}</div>
                <div class="doc-date">Printed {{ now()->format('d M Y, H:i') }}</div>
            </div>
        </div>

        <div class="section-title">Batch Details</div>
        <div class="grid-2">
            <div>
                <dl>
                    <div class="field-row"><dt>Account</dt><dd>{{ $batch->isWalkIn() ? 'Walk-in customer' : $batch->clientAccount?->account_name }}</dd></div>
                    <div class="field-row"><dt>Service type</dt><dd>{{ $batch->serviceType?->name ?? '—' }}</dd></div>
                    <div class="field-row"><dt>Origin</dt><dd>{{ $batch->originHub?->name ?? $batch->originOutlet?->name ?? '—' }}</dd></div>
                </dl>
            </div>
            <div>
                <dl>
                    <div class="field-row"><dt>Sender</dt><dd>{{ $batch->sender_name }}</dd></div>
                    <div class="field-row"><dt>Sender phone</dt><dd>{{ $batch->sender_phone }}</dd></div>
                    <div class="field-row"><dt>Sender address</dt><dd>{{ $batch->sender_address }}</dd></div>
                </dl>
            </div>
        </div>

        <div class="section-title">Shipments ({{ $batch->shipments->count() }})</div>
        <table class="shipments">
            <thead>
                <tr>
                    <th>Tracking #</th>
                    <th>Receiver</th>
                    <th>Destination</th>
                    <th>Pieces</th>
                    <th>Service type</th>
                    <th>Weight</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($batch->shipments as $s)
                    <tr>
                        <td>{{ $s->tracking_number }}</td>
                        <td>{{ $s->receiver_name }}</td>
                        <td>{{ $s->destinationCity?->name }}{{ $s->destinationCity?->state ? ', ' . $s->destinationCity->state->name : '' }}</td>
                        <td>{{ $s->quantity ?? 1 }}</td>
                        <td>{{ $s->serviceType?->name ?? '—' }}</td>
                        <td>{{ $s->weight_kg ? $s->weight_kg . ' kg' : '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6">No shipments created under this batch yet.</td></tr>
                @endforelse
            </tbody>
        </table>

        <div class="footer">{{ $settings->company_name }} — Bulk Batch {{ $batch->batch_number }}</div>
    </div>
</body>
</html>
