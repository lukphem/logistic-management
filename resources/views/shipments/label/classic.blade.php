<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Label {{ $shipment->tracking_number }}</title>
    <style>
        @page { size: {{ $settings->waybill_thermal_size === '2x1' ? '2in 1in' : '4in 6in' }}; margin: 0.15in; }
        * { box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; color: #111; margin: 0; padding: 12px; font-size: 11px; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #111; padding-bottom: 8px; margin-bottom: 8px; }
        .logos { display: flex; align-items: center; gap: 8px; }
        .logos img { height: 30px; max-width: 90px; object-fit: contain; }
        .company-name { font-weight: bold; font-size: 13px; }
        .tracking { text-align: right; }
        .tracking-number { font-family: 'Courier New', monospace; font-size: 18px; font-weight: bold; letter-spacing: 1px; }
        .service-type { font-size: 10px; color: #555; text-transform: uppercase; }
        .code { text-align: center; margin: 8px 0; }
        .parties { display: flex; gap: 10px; margin-bottom: 8px; }
        .party { flex: 1; border: 1px solid #999; padding: 6px; }
        .party-label { font-size: 9px; text-transform: uppercase; color: #555; font-weight: bold; margin-bottom: 3px; }
        .party-name { font-weight: bold; font-size: 12px; }
        .party-detail { font-size: 10px; margin-top: 2px; }
        table.details { width: 100%; border-collapse: collapse; font-size: 10px; }
        table.details td { border: 1px solid #999; padding: 4px 6px; }
        table.details td.label { background: #f2f2f2; font-weight: bold; width: 40%; }
        .footer { margin-top: 10px; font-size: 9px; color: #666; text-align: center; }
        .mini { padding: 4px; }
        .mini .tracking-number { font-size: 15px; text-align: center; }
        .mini .receiver-name { font-weight: bold; font-size: 12px; }
        .mini .receiver-detail { font-size: 9px; }
        .mini .code { margin: 3px 0; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body class="{{ $settings->waybill_thermal_size === '2x1' ? 'mini' : '' }}">
    <div class="no-print" style="margin-bottom: 12px;">
        <button onclick="window.print()" style="padding: 8px 16px; font-size: 13px;">Print</button>
    </div>

    @if ($settings->waybill_thermal_size === '2x1')
        {{-- Minimal content only — no room for sender/company branding at this size --}}
        @if ($codeSvg)
            <div class="code">{!! $codeSvg !!}</div>
        @endif
        <div class="tracking-number">{{ $shipment->tracking_number }}</div>
        <div class="party-label">To</div>
        <div class="receiver-name">{{ $shipment->receiver_name }}</div>
        <div class="receiver-detail">{{ $shipment->receiver_phone }}</div>
        <div class="receiver-detail">{{ $shipment->destinationCity?->name ?? $shipment->destination_address }}</div>
    @else
        <div class="header">
            <div class="logos">
                @if ($settings->logo_path)
                    <img src="{{ asset('storage/' . $settings->logo_path) }}" alt="{{ $settings->company_name }}">
                @endif
                <span class="company-name">{{ $settings->company_name }}</span>
                @if ($clientLogoUrl)
                    <img src="{{ asset($clientLogoUrl) }}" alt="Client logo">
                @endif
            </div>
            <div class="tracking">
                <div class="tracking-number">{{ $shipment->tracking_number }}</div>
                <div class="service-type">{{ $shipment->serviceType?->name ?? $shipment->shipping_type }}</div>
            </div>
        </div>

        @if ($codeSvg)
            <div class="code">{!! $codeSvg !!}</div>
        @endif

        <div class="parties">
            <div class="party">
                <div class="party-label">From</div>
                <div class="party-name">{{ $shipment->sender_name }}</div>
                <div class="party-detail">{{ $shipment->sender_phone }}</div>
                <div class="party-detail">{{ $shipment->origin_address }}</div>
            </div>
            <div class="party">
                <div class="party-label">To</div>
                <div class="party-name">{{ $shipment->receiver_name }}</div>
                <div class="party-detail">{{ $shipment->receiver_phone }}</div>
                @if ($shipment->receiver_alternate_phone)
                    <div class="party-detail">Alt: {{ $shipment->receiver_alternate_phone }}</div>
                @endif
                <div class="party-detail">{{ $shipment->destination_address }}</div>
            </div>
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
    @endif

    <script>window.onload = function () { window.print(); };</script>
</body>
</html>
