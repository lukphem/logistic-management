<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Waybill {{ $shipment->tracking_number }}</title>
    <style>
        @page { size: {{ $settings->waybill_thermal_size === '2x1' ? '2in 1in' : '4in 6in' }}; margin: 0.08in; }
        * { box-sizing: border-box; }
        body { font-family: 'Arial Narrow', Arial, Helvetica, sans-serif; color: #000; margin: 0; padding: 4px; font-size: 9px; line-height: 1.25; }
        .qr-row { text-align: center; margin-bottom: 2px; }
        .tracking-number { text-align: center; font-family: 'Courier New', monospace; font-size: 13px; font-weight: bold; }
        .meta { text-align: center; font-size: 8px; color: #333; border-bottom: 1px solid #000; padding-bottom: 3px; margin-bottom: 3px; }
        .receiver { border: 1px solid #000; padding: 3px; margin-bottom: 3px; }
        .receiver-label { font-size: 7px; text-transform: uppercase; font-weight: bold; }
        .receiver-name { font-size: 12px; font-weight: bold; }
        .receiver-detail { font-size: 9px; }
        .sender-line { font-size: 8px; color: #444; margin-bottom: 3px; }
        .pkg-line { font-size: 8px; border-top: 1px dashed #999; padding-top: 2px; }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 8px;">
        <button onclick="window.print()" style="padding: 6px 12px; font-size: 11px;">Print</button>
    </div>

    @if ($qrSvg)
        <div class="qr-row">{!! $qrSvg !!}</div>
    @endif
    <div class="tracking-number">{{ $shipment->tracking_number }}</div>
    <div class="meta">{{ $shipment->serviceType?->name ?? $shipment->shipping_type }} · {{ $shipment->weight_kg ?? '—' }}kg@if ($shipment->quantity > 1) · Qty {{ $shipment->quantity }}@endif</div>

    <div class="receiver">
        <div class="receiver-label">To</div>
        <div class="receiver-name">{{ $shipment->receiver_name }}</div>
        <div class="receiver-detail">{{ $shipment->receiver_phone }}@if ($shipment->receiver_alternate_phone) / {{ $shipment->receiver_alternate_phone }}@endif</div>
        <div class="receiver-detail">{{ $shipment->destination_address }}</div>
    </div>

    <div class="sender-line">From: {{ $shipment->sender_name }} · {{ $shipment->sender_phone }}</div>

    <div class="pkg-line">
        {{ $shipment->package_description }}
        @if ($shipment->is_cod)
            <br>COD: {{ number_format($shipment->cod_amount, 2) }} {{ $settings->currency }}
        @endif
        @if ($shipment->special_instructions)
            <br>{{ $shipment->special_instructions }}
        @endif
    </div>

    <script>window.onload = function () { window.print(); };</script>
</body>
</html>
