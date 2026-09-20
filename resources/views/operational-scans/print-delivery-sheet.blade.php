<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Delivery Sheet</title>
    <style>
        @page { size: A4 landscape; margin: 0.5in; }
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; }
        body { font-family: Arial, Helvetica, sans-serif; color: #111; font-size: 11px; line-height: 1.4; background: #ddd; }

        .toolbar { display: flex; align-items: center; padding: 14px 20px; background: #f2f2f2; border-bottom: 2px solid #ccc; }
        .print-btn { padding: 8px 16px; font-size: 13px; border: 1px solid #111; background: #111; color: #fff; border-radius: 4px; cursor: pointer; }
        @media print { .toolbar { display: none !important; } }

        /* Landscape — a delivery sheet needs the extra width for a
           real signature column alongside every other field. */
        .paper { max-width: 11.69in; margin: 24px auto; padding: 0.5in; background: #fff; box-shadow: 0 2px 12px rgba(0,0,0,0.2); overflow-wrap: break-word; }
        @media print {
            body { background: #fff; }
            .paper { max-width: none; margin: 0; padding: 0; box-shadow: none; }
        }

        .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 3px solid {{ $settings->color_primary ?? '#1F3864' }}; padding-bottom: 14px; margin-bottom: 16px; }
        .company img { height: 44px; max-width: 150px; object-fit: contain; margin-bottom: 6px; }
        .company-name { font-weight: bold; font-size: 16px; overflow-wrap: break-word; }
        .doc-title { text-align: right; }
        .doc-title h1 { margin: 0; font-size: 20px; color: {{ $settings->color_primary ?? '#1F3864' }}; letter-spacing: 1px; }
        .doc-title .ref-number { font-family: 'Courier New', monospace; font-size: 15px; font-weight: bold; margin-top: 4px; }
        .doc-title .doc-date { font-size: 11px; color: #666; margin-top: 2px; }

        .meta-line { display: flex; gap: 30px; margin-bottom: 14px; font-size: 11px; }
        .meta-line span { color: #666; }
        .meta-line strong { color: #111; }

        table.sheet { width: 100%; border-collapse: collapse; font-size: 10px; }
        table.sheet th { text-align: left; text-transform: uppercase; font-size: 8.5px; color: #555; border-bottom: 2px solid #999; padding: 5px 4px; }
        table.sheet td { padding: 6px 4px; border-bottom: 1px solid #ccc; overflow-wrap: break-word; vertical-align: top; }
        table.sheet tr:last-child td { border-bottom: 1px solid #999; }
        /* Enough row height for an actual pen signature, not just a
           line of text. */
        table.sheet td.sig-cell { height: 46px; border-left: 1px solid #ddd; }
        table.sheet td.name-cell { border-left: 1px solid #ddd; }

        .footer { margin-top: 20px; padding-top: 10px; border-top: 1px solid #ddd; font-size: 9px; color: #999; text-align: center; }
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
                <h1>DELIVERY SHEET</h1>
                @if ($reference)
                    <div class="ref-number">{{ $reference }}</div>
                @endif
                <div class="doc-date">Printed {{ now()->format('d M Y, H:i') }}</div>
            </div>
        </div>

        <div class="meta-line">
            @if ($originLabel)
                <div><span>Out from:</span> <strong>{{ $originLabel }}</strong></div>
            @endif
            @if ($riderName)
                <div><span>Rider/Driver:</span> <strong>{{ $riderName }}</strong></div>
            @endif
            <div><span>Shipments:</span> <strong>{{ $shipments->count() }}</strong></div>
        </div>

        <table class="sheet">
            <thead>
                <tr>
                    <th>Tracking #</th>
                    <th>Receiver (on file)</th>
                    <th>Phone</th>
                    <th>Address</th>
                    <th>Destination</th>
                    <th>Pieces</th>
                    <th>Service type</th>
                    <th class="name-cell">Received by (printed name)</th>
                    <th class="sig-cell">Signature</th>
                    <th>Date / time</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($shipments as $s)
                    <tr>
                        <td>{{ $s->tracking_number }}</td>
                        <td>{{ $s->receiver_name }}</td>
                        <td>{{ $s->receiver_phone }}</td>
                        <td>{{ $s->destination_address }}</td>
                        <td>{{ $s->destinationCity?->name }}{{ $s->destinationCity?->state ? ', ' . $s->destinationCity->state->name : '' }}</td>
                        <td>{{ $s->quantity ?? 1 }}</td>
                        <td>{{ $s->serviceType?->name ?? '—' }}</td>
                        <td class="name-cell"></td>
                        <td class="sig-cell"></td>
                        <td></td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="footer">{{ $settings->company_name }} — Delivery Sheet — {{ $shipments->count() }} shipment(s)</div>
    </div>
</body>
</html>
