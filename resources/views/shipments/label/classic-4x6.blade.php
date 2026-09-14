<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Label {{ $shipment->tracking_number }}</title>
    <style>
        @page { size: 4in 6in; margin: 0.12in; }
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; }
        body { font-family: Arial, Helvetica, sans-serif; color: #111; font-size: 11px; }

        .toolbar { display: flex; align-items: center; gap: 12px; padding: 10px; background: #f2f2f2; border-bottom: 2px solid #ccc; }
        .size-toggle a { display: inline-block; padding: 6px 14px; font-size: 12px; border: 1px solid #999; text-decoration: none; color: #333; background: #fff; }
        .size-toggle a.active { background: #111; color: #fff; border-color: #111; }
        .size-toggle a:first-child { border-radius: 4px 0 0 4px; }
        .size-toggle a:last-child { border-radius: 0 4px 4px 0; border-left: none; }
        .print-btn { padding: 8px 16px; font-size: 13px; border: 1px solid #111; background: #111; color: #fff; border-radius: 4px; cursor: pointer; }
        @media print { .toolbar { display: none !important; } }

        .page { width: 3.76in; padding: 0.06in; page-break-after: always; }
        .page:last-child { page-break-after: auto; }

        .fit-content { overflow-wrap: break-word; word-break: break-word; }

        .piece-tag { font-size: 10px; font-weight: bold; text-align: center; padding: 2px 0; background: #111; color: #fff; letter-spacing: 1px; margin-bottom: 6px; }

        .top-row { display: flex; justify-content: space-between; align-items: center; gap: 6px; margin-bottom: 6px; }
        .brand { display: flex; align-items: center; gap: 6px; min-width: 0; }
        .brand img { height: 26px; max-width: 70px; object-fit: contain; flex-shrink: 0; }
        .brand-name { font-weight: bold; font-size: 12px; overflow-wrap: break-word; }
        .service-badge { flex-shrink: 0; background: {{ $settings->color_primary ?? '#1F3864' }}; color: #fff; font-size: 10px; font-weight: bold; text-transform: uppercase; padding: 4px 8px; border-radius: 3px; letter-spacing: 0.5px; }

        .tracking-strip { text-align: center; border-top: 2px solid #111; border-bottom: 2px solid #111; padding: 5px 0; margin-bottom: 6px; }
        .tracking-number { font-family: 'Courier New', monospace; font-size: 16px; font-weight: bold; letter-spacing: 0.5px; overflow-wrap: break-word; }

        .barcode-zone { text-align: center; margin-bottom: 6px; }
        .barcode-zone svg { max-width: 100%; height: auto; }

        .addr-box { border: 1px solid #999; border-radius: 3px; padding: 6px 8px; margin-bottom: 5px; }
        .addr-box.to { border: 2px solid #111; padding: 7px 8px; }
        .addr-label { font-size: 8px; text-transform: uppercase; font-weight: bold; color: #666; letter-spacing: 0.5px; margin-bottom: 2px; }
        .addr-box.to .addr-label { color: #111; }
        .addr-name { font-weight: bold; overflow-wrap: break-word; }
        .addr-box.to .addr-name { font-size: 14px; }
        .addr-box.from .addr-name { font-size: 11px; }
        .addr-line { font-size: 10px; color: #333; margin-top: 1px; overflow-wrap: break-word; }

        table.details { width: 100%; border-collapse: collapse; font-size: 9.5px; margin-top: 4px; }
        table.details td { border: 1px solid #bbb; padding: 3px 6px; overflow-wrap: break-word; }
        table.details td.label { background: #f2f2f2; font-weight: bold; width: 34%; white-space: nowrap; }

        .footer { margin-top: 6px; font-size: 8px; color: #777; text-align: center; overflow-wrap: break-word; }
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

        <div class="top-row">
            <div class="brand">
                @if ($settings->logo_path)
                    <img src="{{ asset('storage/' . $settings->logo_path) }}" alt="{{ $settings->company_name }}">
                @endif
                <span class="brand-name">{{ $settings->company_name }}</span>
                @if ($clientLogoUrl)
                    <img src="{{ asset($clientLogoUrl) }}" alt="Client logo">
                @endif
            </div>
            <div class="service-badge">{{ $shipment->serviceType?->name ?? $shipment->shipping_type }}</div>
        </div>

        <div class="tracking-strip">
            <div class="tracking-number">{{ $piece['code'] }}</div>
        </div>

        @if ($piece['codeSvg'])
            <div class="barcode-zone">{!! $piece['codeSvg'] !!}</div>
        @endif

        <div class="addr-box to">
            <div class="addr-label">Deliver to</div>
            <div class="addr-name">{{ $shipment->receiver_name }}</div>
            <div class="addr-line">
                {{ $shipment->receiver_phone }}
                @if ($shipment->receiver_alternate_phone)
                    / {{ $shipment->receiver_alternate_phone }}
                @endif
            </div>
            <div class="addr-line">{{ $shipment->destination_address }}</div>
        </div>

        <div class="addr-box from">
            <div class="addr-label">From</div>
            <div class="addr-name">{{ $shipment->sender_name }}</div>
            <div class="addr-line">{{ $shipment->sender_phone }}</div>
            <div class="addr-line">{{ $shipment->origin_address }}</div>
        </div>

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
            @if ($shipment->promised_delivery_at)
                <tr>
                    <td class="label">Promised by</td>
                    <td>{{ $shipment->promised_delivery_at->format('d M Y') }}</td>
                </tr>
            @endif
            @if ($shipment->is_cod)
                <tr>
                    <td class="label">Cash on delivery</td>
                    <td>{{ number_format($shipment->cod_amount, 2) }} {{ $settings->currency }}</td>
                </tr>
            @endif
            @if ($shipment->special_instructions)
                <tr>
                    <td class="label">Instructions</td>
                    <td>{{ $shipment->special_instructions }}</td>
                </tr>
            @endif
        </table>

        <div class="footer">{{ $settings->company_name }} — booked {{ $shipment->created_at->format('d M Y') }}</div>
    </div>
    </div>
    @endforeach

    <script>
        // Guarantees content never overflows the fixed 4×6 page,
        // however long a name/address/description turns out to be —
        // rather than letting long content silently get cut off at
        // the physical page boundary when printed. Shrinks in small
        // steps until it fits or hits a floor still legible at
        // arm's length.
        (function () {
            var maxHeightPx = 5.6 * 96; // page height minus margins, in px at 96dpi
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
