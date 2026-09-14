<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Waybill {{ $shipment->tracking_number }}</title>
    <style>
        @page { size: {{ $settings->waybill_thermal_size === '2x1' ? '2in 1in' : '4in 6in' }}; margin: 0.15in; }
        * { box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; color: #111; margin: 0; padding: 0; font-size: 11px; }
        .accent-bar { height: 6px; background: {{ $settings->color_primary ?? '#1F3864' }}; }
        .content { padding: 12px; }
        .company-name { text-align: center; font-size: 11px; font-weight: bold; color: {{ $settings->color_primary ?? '#1F3864' }}; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 6px; }
        .qr-block { text-align: center; margin: 6px 0; }
        .tracking-number { text-align: center; font-family: 'Courier New', monospace; font-size: 20px; font-weight: bold; letter-spacing: 1px; margin-top: 4px; }
        .service-type { text-align: center; font-size: 10px; color: #666; text-transform: uppercase; margin-bottom: 10px; }
        .divider { border: none; border-top: 1px dashed #ccc; margin: 10px 0; }
        .party { margin-bottom: 10px; }
        .party-label { font-size: 9px; text-transform: uppercase; font-weight: bold; color: {{ $settings->color_secondary ?? '#F2A900' }}; margin-bottom: 2px; }
        .party-name { font-weight: bold; font-size: 13px; }
        .party-detail { font-size: 10px; color: #444; margin-top: 1px; }
        .package-box { background: #f7f7f7; border-radius: 6px; padding: 8px; font-size: 10px; margin-top: 8px; }
        .package-box div { margin-bottom: 2px; }
        .footer { margin-top: 10px; font-size: 9px; color: #999; text-align: center; }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="padding: 12px;">
        <button onclick="window.print()" style="padding: 8px 16px; font-size: 13px;">Print</button>
    </div>

    <div class="accent-bar"></div>
    <div class="content">
        <div class="company-name">{{ $settings->company_name }}</div>

        @if ($qrSvg)
            <div class="qr-block">{!! $qrSvg !!}</div>
        @endif
        <div class="tracking-number">{{ $shipment->tracking_number }}</div>
        <div class="service-type">{{ $shipment->serviceType?->name ?? $shipment->shipping_type }}</div>

        <hr class="divider">

        <div class="party">
            <div class="party-label">From</div>
            <div class="party-name">{{ $shipment->sender_name }}</div>
            <div class="party-detail">{{ $shipment->sender_phone }}</div>
            <div class="party-detail">{{ $shipment->origin_address }}</div>
        </div>
        <div class="party">
            <div class="party-label">To</div>
            <div class="party-name">{{ $shipment->receiver_name }}</div>
            <div class="party-detail">{{ $shipment->receiver_phone }}@if ($shipment->receiver_alternate_phone) · {{ $shipment->receiver_alternate_phone }}@endif</div>
            <div class="party-detail">{{ $shipment->destination_address }}</div>
        </div>

        <div class="package-box">
            <div><strong>{{ $shipment->package_description }}</strong></div>
            <div>{{ $shipment->weight_kg ?? '—' }} kg @if ($shipment->quantity > 1) · Qty: {{ $shipment->quantity }} @endif @if ($shipment->carton_size) · {{ ucfirst($shipment->carton_size) }} @endif</div>
            @if ($shipment->is_cod)
                <div>COD: {{ number_format($shipment->cod_amount, 2) }} {{ $settings->currency }}</div>
            @endif
            @if ($shipment->special_instructions)
                <div>{{ $shipment->special_instructions }}</div>
            @endif
        </div>

        <div class="footer">Booked {{ $shipment->created_at->format('d M Y') }}</div>
    </div>

    <script>window.onload = function () { window.print(); };</script>
</body>
</html>
