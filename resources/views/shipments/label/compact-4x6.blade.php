<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Label {{ $shipment->tracking_number }}</title>
    <style>
        @page { size: 4in 6in; margin: 0.1in; }
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; }
        body { font-family: 'Arial Narrow', Arial, Helvetica, sans-serif; color: #000; font-size: 10px; line-height: 1.25; }

        .toolbar { display: flex; align-items: center; gap: 12px; padding: 10px; background: #f2f2f2; border-bottom: 2px solid #ccc; }
        .size-toggle a { display: inline-block; padding: 6px 14px; font-size: 12px; border: 1px solid #999; text-decoration: none; color: #333; background: #fff; }
        .size-toggle a.active { background: #111; color: #fff; border-color: #111; }
        .size-toggle a:first-child { border-radius: 4px 0 0 4px; }
        .size-toggle a:last-child { border-radius: 0 4px 4px 0; border-left: none; }
        .print-btn { padding: 8px 16px; font-size: 13px; border: 1px solid #111; background: #111; color: #fff; border-radius: 4px; cursor: pointer; }
        @media print { .toolbar { display: none !important; } }

        .page { width: 3.8in; padding: 0.06in; page-break-after: always; }
        .page:last-child { page-break-after: auto; }

        .fit-content { overflow-wrap: break-word; }

        .piece-tag { font-size: 9px; font-weight: bold; text-align: center; border: 1px solid #000; padding: 2px; margin-bottom: 4px; }
        .top-line { display: flex; justify-content: space-between; font-size: 8px; text-transform: uppercase; border-bottom: 1px solid #000; padding-bottom: 3px; margin-bottom: 3px; }

        .barcode-zone { text-align: center; margin: 3px 0; }
        .barcode-zone svg { max-width: 100%; height: auto; }
        .tracking-number { text-align: center; font-family: 'Courier New', monospace; font-size: 14px; font-weight: bold; overflow-wrap: break-word; margin-bottom: 4px; }

        .receiver-block { border: 1px solid #000; padding: 5px; margin-bottom: 4px; }
        .receiver-label { font-size: 8px; text-transform: uppercase; font-weight: bold; }
        .receiver-name { font-size: 13px; font-weight: bold; overflow-wrap: break-word; }
        .receiver-detail { font-size: 10px; overflow-wrap: break-word; }

        .sender-line { font-size: 9px; color: #333; margin-bottom: 3px; overflow-wrap: break-word; }

        table.details { width: 100%; border-collapse: collapse; font-size: 9px; }
        table.details td { border: 1px solid #999; padding: 2px 4px; overflow-wrap: break-word; }
        table.details td.label { font-weight: bold; width: 36%; }

        .footer { margin-top: 4px; font-size: 8px; color: #666; text-align: center; overflow-wrap: break-word; }
    </style>
</head>
<body>
    <div class="toolbar">
        <span class="size-toggle">
            <a href="?size=4x6" class="active">4×6"</a><a href="?size=2x1">2×1"</a>
        </span>
        <button class="print-btn" onclick="window.print()">Print{{ count($pieces) > 1 ? ' all ' . count($pieces) . ' pieces' : '' }}</button>
    </div>

    @foreach ($pieces as $piece)
    <div class="page">
    <div class="fit-content">
        @if ($piece['total'] > 1)
            <div class="piece-tag">PIECE {{ $piece['number'] }} OF {{ $piece['total'] }}</div>
        @endif

        <div class="top-line">
            <span>{{ $settings->company_name }}</span>
            <span>{{ $shipment->serviceType?->name ?? $shipment->shipping_type }}</span>
        </div>

        @if ($piece['codeSvg'])
            <div class="barcode-zone">{!! $piece['codeSvg'] !!}</div>
        @endif
        <div class="tracking-number">{{ $piece['code'] }}</div>

        <div class="receiver-block">
            <div class="receiver-label">To</div>
            <div class="receiver-name">{{ $shipment->receiver_name }}</div>
            <div class="receiver-detail">
                {{ $shipment->receiver_phone }}
                @if ($shipment->receiver_alternate_phone)
                    / {{ $shipment->receiver_alternate_phone }}
                @endif
            </div>
            <div class="receiver-detail">{{ $shipment->destination_address }}</div>
        </div>

        <div class="sender-line">From: {{ $shipment->sender_name }} · {{ $shipment->sender_phone }} · {{ $shipment->origin_address }}</div>

        <table class="details">
            <tr>
                <td class="label">Description</td>
                <td>{{ $shipment->package_description }}</td>
            </tr>
            <tr>
                <td class="label">Weight / Qty</td>
                <td>{{ $shipment->weight_kg ?? '—' }} kg — Qty {{ $shipment->quantity ?? 1 }}</td>
            </tr>
            <tr>
                <td class="label">Route</td>
                <td>{{ $shipment->originHub?->code ?? $shipment->originCity?->name ?? '—' }} → {{ $shipment->destinationHub?->code ?? $shipment->destinationCity?->name ?? '—' }}</td>
            </tr>
            @if ($shipment->is_cod)
                <tr>
                    <td class="label">COD</td>
                    <td>{{ number_format($shipment->cod_amount, 2) }} {{ $settings->currency }}</td>
                </tr>
            @endif
            @if ($shipment->special_instructions)
                <tr>
                    <td class="label">Notes</td>
                    <td>{{ $shipment->special_instructions }}</td>
                </tr>
            @endif
        </table>

        <div class="footer">Booked {{ $shipment->created_at->format('d M Y') }}</div>
    </div>
    </div>
    @endforeach

    <script>
        (function () {
            var maxHeightPx = 5.7 * 96;
            document.querySelectorAll('.page').forEach(function (page) {
                var content = page.querySelector('.fit-content');
                if (!content) return;
                var scale = 100;
                var attempts = 0;
                while (content.scrollHeight > maxHeightPx && scale > 55 && attempts < 25) {
                    scale -= 2;
                    content.style.fontSize = scale + '%';
                    attempts++;
                }
            });
        })();
    </script>
</body>
</html>
