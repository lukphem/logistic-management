<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Label {{ $shipment->tracking_number }}</title>
    <style>
        @page { size: 2in 1in; margin: 0.05in; }
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; }
        body { font-family: Arial, Helvetica, sans-serif; color: #000; font-size: 8px; line-height: 1.15; }

        .toolbar { display: flex; align-items: center; gap: 10px; padding: 8px; background: #f2f2f2; border-bottom: 2px solid #ccc; }
        .size-toggle a { display: inline-block; padding: 5px 10px; font-size: 11px; border: 1px solid #999; text-decoration: none; color: #333; background: #fff; }
        .size-toggle a.active { background: #111; color: #fff; border-color: #111; }
        .size-toggle a:first-child { border-radius: 4px 0 0 4px; }
        .size-toggle a:last-child { border-radius: 0 4px 4px 0; border-left: none; }
        .print-btn { padding: 6px 12px; font-size: 11px; border: 1px solid #111; background: #111; color: #fff; border-radius: 4px; cursor: pointer; }
        @media print { .toolbar { display: none !important; } }

        .page { width: 1.9in; page-break-after: always; }
        .page:last-child { page-break-after: auto; }
        .accent-bar { height: 3px; background: {{ $settings->color_primary ?? '#1F3864' }}; }
        .inner { padding: 0.04in; }

        .fit-content { overflow-wrap: break-word; }

        .piece-tag { font-size: 7px; font-weight: bold; text-align: center; color: {{ $settings->color_secondary ?? '#F2A900' }}; margin-bottom: 1px; }
        .barcode-zone { text-align: center; line-height: 1; }
        .barcode-zone svg { max-width: 100%; height: auto; max-height: 0.3in; }
        .tracking-number { text-align: center; font-family: 'Courier New', monospace; font-size: 10px; font-weight: bold; overflow-wrap: break-word; }
        .receiver-name { font-weight: bold; font-size: 10px; overflow-wrap: break-word; }
        .receiver-line { overflow-wrap: break-word; }
    </style>
</head>
<body>
    <div class="toolbar">
        <span class="size-toggle">
            <a href="?size=4x6">4×6"</a><a href="?size=2x1" class="active">2×1"</a>
        </span>
        <button class="print-btn" onclick="window.print()">Print{{ count($pieces) > 1 ? ' all ' . count($pieces) . ' pieces' : '' }}</button>
    </div>

    @foreach ($pieces as $piece)
    <div class="page">
    <div class="accent-bar"></div>
    <div class="inner">
    <div class="fit-content">
        @if ($piece['total'] > 1)
            <div class="piece-tag">PC {{ $piece['number'] }}/{{ $piece['total'] }}</div>
        @endif
        @if ($piece['codeSvg'])
            <div class="barcode-zone">{!! $piece['codeSvg'] !!}</div>
        @endif
        <div class="tracking-number">{{ $piece['code'] }}</div>
        <div class="receiver-name">{{ $shipment->receiver_name }}</div>
        <div class="receiver-line">{{ $shipment->receiver_phone }}</div>
        <div class="receiver-line">{{ $shipment->destinationCity?->name ?? \Illuminate\Support\Str::limit($shipment->destination_address, 40) }}</div>
    </div>
    </div>
    </div>
    @endforeach

    <script>
        (function () {
            var maxHeightPx = 0.9 * 96;
            document.querySelectorAll('.page').forEach(function (page) {
                var content = page.querySelector('.fit-content');
                if (!content) return;
                var scale = 100;
                var attempts = 0;
                while (content.scrollHeight > maxHeightPx && scale > 45 && attempts < 25) {
                    scale -= 3;
                    content.style.fontSize = scale + '%';
                    attempts++;
                }
            });
        })();
    </script>
</body>
</html>
