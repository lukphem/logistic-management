<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Label {{ $shipment->tracking_number }}</title>
    <style>
        @page { size: {{ $printSize === '2x1' ? '2in 1in' : '4in 6in' }}; margin: 0.08in; }
        * { box-sizing: border-box; }
        body { font-family: 'Arial Narrow', Arial, Helvetica, sans-serif; color: #000; margin: 0; padding: 4px; font-size: 9px; line-height: 1.25; }
        .code-row { text-align: center; margin-bottom: 2px; }
        .tracking-number { text-align: center; font-family: 'Courier New', monospace; font-size: 13px; font-weight: bold; }
        .meta { text-align: center; font-size: 8px; color: #333; border-bottom: 1px solid #000; padding-bottom: 3px; margin-bottom: 3px; }
        .receiver { border: 1px solid #000; padding: 3px; margin-bottom: 3px; }
        .receiver-label { font-size: 7px; text-transform: uppercase; font-weight: bold; }
        .receiver-name { font-size: 12px; font-weight: bold; }
        .receiver-detail { font-size: 9px; }
        .sender-line { font-size: 8px; color: #444; margin-bottom: 3px; }
        .pkg-line { font-size: 8px; border-top: 1px dashed #999; padding-top: 2px; }
        .route-line { font-size: 8px; color: #444; }
        .size-toggle a { display: inline-block; padding: 5px 10px; font-size: 10px; border: 1px solid #999; text-decoration: none; color: #333; }
        .size-toggle a.active { background: #111; color: #fff; border-color: #111; }
        .size-toggle a:first-child { border-radius: 4px 0 0 4px; }
        .size-toggle a:last-child { border-radius: 0 4px 4px 0; border-left: none; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 8px; display: flex; align-items: center; gap: 10px;">
        <span class="size-toggle">
            <a href="?size=4x6" class="{{ $printSize === '4x6' ? 'active' : '' }}">4×6"</a><a href="?size=2x1" class="{{ $printSize === '2x1' ? 'active' : '' }}">2×1"</a>
        </span>
        <button onclick="window.print()" style="padding: 6px 12px; font-size: 11px;">Print</button>
    </div>

    @if ($codeSvg)
        <div class="code-row">{!! $codeSvg !!}</div>
    @endif
    <div class="tracking-number">{{ $shipment->tracking_number }}</div>
    <div class="meta">
        {{ $shipment->serviceType?->name ?? $shipment->shipping_type }}
        · {{ $shipment->weight_kg ?? '—' }}kg
        @if ($shipment->quantity > 1)
            · Qty {{ $shipment->quantity }}
        @endif
    </div>

    <div class="receiver">
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

    @if ($printSize !== '2x1')
        <div class="sender-line">From: {{ $shipment->sender_name }} · {{ $shipment->sender_phone }}</div>
        <div class="route-line">Route: {{ $shipment->originHub?->code ?? $shipment->originCity?->name ?? '—' }} → {{ $shipment->destinationHub?->code ?? $shipment->destinationCity?->name ?? '—' }}</div>

        <div class="pkg-line">
            {{ $shipment->package_description }}
            @if ($shipment->is_cod)
                <br>COD: {{ number_format($shipment->cod_amount, 2) }} {{ $settings->currency }}
            @endif
            @if ($shipment->special_instructions)
                <br>{{ $shipment->special_instructions }}
            @endif
        </div>
    @endif

</body>
</html>
