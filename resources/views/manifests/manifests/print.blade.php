<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Manifest {{ $manifest->manifest_number }}</title>
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
        .doc-title .trip-number { font-family: 'Courier New', monospace; font-size: 16px; font-weight: bold; margin-top: 4px; }
        .doc-title .doc-date { font-size: 11px; color: #666; margin-top: 2px; }

        .section-title { font-size: 11px; text-transform: uppercase; font-weight: bold; color: #555; letter-spacing: 0.5px; margin: 18px 0 6px; border-bottom: 1px solid #ddd; padding-bottom: 4px; }
        .grid-2 { display: flex; gap: 24px; }
        .grid-2 > div { flex: 1; min-width: 0; }
        .field-row { display: flex; justify-content: space-between; gap: 10px; padding: 3px 0; border-bottom: 1px dotted #ddd; }
        .field-row dt { color: #666; flex-shrink: 0; }
        .field-row dd { margin: 0; font-weight: 500; text-align: right; overflow-wrap: break-word; }

        .manifest-heading { display: flex; justify-content: space-between; align-items: baseline; background: #f4f4f4; padding: 8px 10px; border-radius: 4px; margin-top: 18px; }
        .manifest-heading .dest { font-weight: bold; font-size: 13px; }
        .manifest-heading .man-number { font-family: 'Courier New', monospace; font-size: 11px; color: #555; }

        table.shipments { width: 100%; border-collapse: collapse; margin-top: 8px; font-size: 10.5px; }
        table.shipments th { text-align: left; text-transform: uppercase; font-size: 9px; color: #555; border-bottom: 2px solid #999; padding: 5px 4px; }
        table.shipments td { padding: 5px 4px; border-bottom: 1px solid #eee; overflow-wrap: break-word; }
        table.shipments tr:last-child td { border-bottom: 1px solid #999; }

        .signatures { display: flex; gap: 24px; margin-top: 18px; }
        .signature-block { flex: 1; }
        .signature-line { border-top: 1px solid #111; margin-top: 36px; padding-top: 4px; font-size: 10px; color: #555; }

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
                <h1>MANIFEST</h1>
                <div class="trip-number">{{ $manifest->manifest_number }}</div>
                <div class="doc-date">Printed {{ now()->format('d M Y, H:i') }}</div>
            </div>
        </div>

        <div class="section-title">Trip Details</div>
        <div class="grid-2">
            <div>
                <dl>
                    <div class="field-row"><dt>Trip</dt><dd>{{ $manifest->trip->trip_number }}</dd></div>
                    <div class="field-row"><dt>Origin</dt><dd>{{ $manifest->trip->originHub?->name ?? $manifest->trip->originOutlet?->name ?? '—' }}</dd></div>
                    <div class="field-row"><dt>Transport mode</dt><dd>{{ ucfirst($manifest->trip->transport_mode) }}</dd></div>
                    <div class="field-row"><dt>Carrier</dt><dd>{{ $manifest->trip->carrier_type === 'third_party' ? $manifest->trip->carrier_name : 'Company vehicle' }}</dd></div>
                </dl>
            </div>
            <div>
                <dl>
                    <div class="field-row"><dt>Vehicle</dt><dd>{{ $manifest->trip->vehicleType?->name }} {{ $manifest->trip->vehicle_identifier }}</dd></div>
                    <div class="field-row"><dt>Driver</dt><dd>{{ $manifest->trip->driver_name ?? '—' }}</dd></div>
                    <div class="field-row"><dt>Driver phone</dt><dd>{{ $manifest->trip->driver_phone ?? '—' }}</dd></div>
                    <div class="field-row"><dt>Est. arrival</dt><dd>{{ $manifest->estimated_arrival_at?->format('d M Y') ?? '—' }}</dd></div>
                </dl>
            </div>
        </div>

        <div class="manifest-heading">
            <span class="dest">→ {{ $manifest->destinationHub?->name ?? $manifest->destinationOutlet?->name ?? '—' }}</span>
            <span class="man-number">{{ $manifest->manifest_number }} · {{ $manifest->manifestShipments->count() }} shipment(s)</span>
        </div>

        <table class="shipments">
            <thead>
                <tr>
                    <th>Tracking #</th>
                    <th>Description</th>
                    <th>Destination</th>
                    <th>Pieces</th>
                    <th>Service type</th>
                    <th>Weight</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($manifest->manifestShipments as $manifestShipment)
                    @php $s = $manifestShipment->shipment; @endphp
                    @if ($s)
                        <tr>
                            <td>{{ $s->tracking_number }}</td>
                            <td>{{ $s->package_description ?? '—' }}</td>
                            <td>{{ $s->destinationCity?->name }}{{ $s->destinationCity?->state ? ', ' . $s->destinationCity->state->name : '' }}</td>
                            <td>{{ $s->quantity ?? 1 }}</td>
                            <td>{{ $s->serviceType?->name ?? '—' }}</td>
                            <td>{{ $s->weight_kg ? $s->weight_kg . ' kg' : '—' }}</td>
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>

        <div class="signatures">
            <div class="signature-block">
                <div class="signature-line">Dispatched by ({{ $manifest->trip->dispatchedBy?->name ?? '—' }}) — Date: {{ $manifest->trip->dispatched_at?->format('d M Y') }}</div>
            </div>
            <div class="signature-block">
                <div class="signature-line">Driver signature ({{ $manifest->trip->driver_name ?? '—' }}) — Date</div>
            </div>
            <div class="signature-block">
                <div class="signature-line">Received by (name &amp; signature) — Date</div>
            </div>
        </div>

        <div class="footer">{{ $settings->company_name }} — Manifest {{ $manifest->manifest_number }} — Trip {{ $manifest->trip->trip_number }}</div>
    </div>
</body>
</html>
