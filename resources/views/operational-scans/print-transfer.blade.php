<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Transfer Confirmation</title>
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
        .doc-title .doc-date { font-size: 11px; color: #666; margin-top: 2px; }

        .route-line { text-align: center; font-size: 14px; font-weight: bold; margin: 10px 0 18px; }

        table.shipments { width: 100%; border-collapse: collapse; margin-top: 8px; font-size: 10.5px; }
        table.shipments th { text-align: left; text-transform: uppercase; font-size: 9px; color: #555; border-bottom: 2px solid #999; padding: 5px 4px; }
        table.shipments td { padding: 5px 4px; border-bottom: 1px solid #eee; overflow-wrap: break-word; }
        table.shipments tr:last-child td { border-bottom: 1px solid #999; }

        .signatures { display: flex; gap: 40px; margin-top: 30px; }
        .signature-block { flex: 1; }
        .signature-line { border-top: 1px solid #111; margin-top: 40px; padding-top: 4px; font-size: 10px; color: #555; }

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
                <h1>TRANSFER CONFIRMATION</h1>
                <div class="doc-date">Printed {{ now()->format('d M Y, H:i') }}</div>
            </div>
        </div>

        @if ($originLabel || $destinationLabel)
            <div class="route-line">{{ $originLabel ?? '—' }} → {{ $destinationLabel ?? '—' }}</div>
        @endif

        <table class="shipments">
            <thead>
                <tr>
                    <th>Tracking #</th>
                    <th>Receiver</th>
                    <th>Phone</th>
                    <th>Pieces</th>
                    <th>Service type</th>
                    <th>Weight</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($shipments as $s)
                    <tr>
                        <td>{{ $s->tracking_number }}</td>
                        <td>{{ $s->receiver_name }}</td>
                        <td>{{ $s->receiver_phone }}</td>
                        <td>{{ $s->quantity ?? 1 }}</td>
                        <td>{{ $s->serviceType?->name ?? '—' }}</td>
                        <td>{{ $s->weight_kg ? $s->weight_kg . ' kg' : '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="signatures">
            <div class="signature-block">
                <div class="signature-line">Handed over by (name &amp; signature) — Date</div>
            </div>
            <div class="signature-block">
                <div class="signature-line">Received by (name &amp; signature) — Date</div>
            </div>
        </div>

        <div class="footer">{{ $settings->company_name }} — Transfer Confirmation — {{ $shipments->count() }} shipment(s)</div>
    </div>
</body>
</html>
