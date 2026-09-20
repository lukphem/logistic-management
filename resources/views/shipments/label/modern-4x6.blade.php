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

        .page { width: 3.76in; page-break-after: always; }
        .page:last-child { page-break-after: auto; }
        .accent-bar { height: 5px; background: {{ $settings->color_primary ?? '#1F3864' }}; }
        .inner { padding: 0.08in; }

        .fit-content { overflow-wrap: break-word; }

        .piece-tag { font-size: 10px; font-weight: bold; text-align: center; color: {{ $settings->color_secondary ?? '#F2A900' }}; margin-bottom: 4px; letter-spacing: 1px; }

        .brand-row { display: flex; justify-content: center; align-items: center; gap: 8px; margin-bottom: 4px; }
        .brand-row img { height: 24px; max-width: 70px; object-fit: contain; }
        .company-name { text-align: center; font-size: 10px; font-weight: bold; color: {{ $settings->color_primary ?? '#1F3864' }}; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 6px; }

        .code-block { text-align: center; margin-bottom: 4px; }
        .code-block svg { max-width: 90%; height: auto; }
        .tracking-number { text-align: center; font-family: 'Courier New', monospace; font-size: 17px; font-weight: bold; overflow-wrap: break-word; }
        .service-type { text-align: center; font-size: 10px; color: #666; text-transform: uppercase; margin-bottom: 8px; letter-spacing: 0.5px; }

        .divider { border: none; border-top: 1px dashed #ccc; margin: 8px 0; }

        .party { margin-bottom: 8px; }
        .party-label { font-size: 8px; text-transform: uppercase; font-weight: bold; color: {{ $settings->color_secondary ?? '#F2A900' }}; margin-bottom: 2px; letter-spacing: 0.5px; }
        .party-name { font-weight: bold; font-size: 13px; overflow-wrap: break-word; }
        .party-detail { font-size: 10px; color: #444; margin-top: 1px; overflow-wrap: break-word; }

        .package-box { background: #f7f7f7; border-radius: 6px; padding: 8px; font-size: 10px; }
        .package-box div { margin-bottom: 2px; overflow-wrap: break-word; }

        .footer { margin-top: 8px; font-size: 8px; color: #999; text-align: center; overflow-wrap: break-word; }
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
    <div class="accent-bar"></div>
    <div class="inner">
    <div class="fit-content">
        @if ($piece['total'] > 1)
            <div class="piece-tag">PIECE {{ $piece['number'] }} OF {{ $piece['total'] }}</div>
        @endif

        <div class="brand-row">
            @if ($settings->logo_path)
                <img src="{{ asset('storage/' . $settings->logo_path) }}" alt="{{ $settings->company_name }}">
            @endif
            @if ($clientLogoUrl)
                <img src="{{ asset($clientLogoUrl) }}" alt="Client logo">
            @endif
        </div>
        <div class="company-name">{{ $settings->company_name }}</div>

        @if ($piece['codeSvg'])
            <div class="code-block">{!! $piece['codeSvg'] !!}</div>
        @endif
        <div class="tracking-number">{{ $piece['code'] }}</div>
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
            <div class="party-detail">
                {{ $shipment->receiver_phone }}
                @if ($shipment->receiver_alternate_phone)
                    · {{ $shipment->receiver_alternate_phone }}
                @endif
            </div>
            <div class="party-detail">{{ \Illuminate\Support\Str::limit($shipment->destination_address, 120) }}</div>
        </div>

        <div class="package-box">
            <div><strong>{{ \Illuminate\Support\Str::limit($shipment->package_description, 70) }}</strong></div>
            <div>
                {{ $shipment->weight_kg ?? '—' }} kg · Qty {{ $shipment->quantity ?? 1 }}
                @if ($shipment->carton_size)
                    · {{ ucfirst($shipment->carton_size) }}
                @endif
            </div>
            <div>Route: {{ $shipment->originHub?->code ?? $shipment->originCity?->name ?? '—' }} → {{ $shipment->destinationHub?->code ?? $shipment->destinationCity?->name ?? '—' }}</div>
            @if ($shipment->promised_delivery_at)
                <div>Promised by {{ $shipment->promised_delivery_at->format('d M Y') }}</div>
            @endif
            @if ($shipment->is_cod)
                <div>COD: {{ number_format($shipment->cod_amount, 2) }} {{ $settings->currency }}</div>
            @endif
            @if ($shipment->special_instructions)
                <div>{{ $shipment->special_instructions }}</div>
            @endif
        </div>

        <div class="footer">Booked {{ $shipment->created_at->format('d M Y') }}</div>
    </div>
    </div>
    </div>
    @endforeach

    <script>
        (function () {
            var maxHeightPx = 5.6 * 96;
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
