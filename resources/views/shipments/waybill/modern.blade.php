<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Waybill {{ $shipment->tracking_number }}</title>
    <style>
        @page { size: A4; margin: 0.6in; }
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; }
        body { font-family: Arial, Helvetica, sans-serif; color: #111; font-size: 12px; line-height: 1.4; background: #ddd; }

        .toolbar { display: flex; align-items: center; padding: 14px 20px; background: #f2f2f2; border-bottom: 2px solid #ccc; }
        .print-btn { padding: 8px 16px; font-size: 13px; border: 1px solid #111; background: #111; color: #fff; border-radius: 4px; cursor: pointer; }
        @media print { .toolbar { display: none !important; } }

        .paper { max-width: 8.27in; margin: 24px auto; background: #fff; box-shadow: 0 2px 12px rgba(0,0,0,0.2); overflow-wrap: break-word; }
        @media print {
            body { background: #fff; }
            .paper { max-width: none; margin: 0; box-shadow: none; }
        }

        .band { background: {{ $settings->color_primary ?? '#1F3864' }}; color: #fff; padding: 0.35in 0.6in; display: flex; justify-content: space-between; align-items: center; }
        .band img { height: 40px; max-width: 150px; object-fit: contain; filter: brightness(0) invert(1); margin-bottom: 4px; }
        .band .company-name { font-weight: bold; font-size: 16px; overflow-wrap: break-word; }
        .band .doc-title { text-align: right; }
        .band .doc-title h1 { margin: 0; font-size: 20px; letter-spacing: 2px; color: {{ $settings->color_secondary ?? '#F2A900' }}; }
        .band .tracking-number { font-family: 'Courier New', monospace; font-size: 15px; font-weight: bold; margin-top: 4px; overflow-wrap: break-word; }
        .band .doc-date { font-size: 10px; opacity: 0.8; margin-top: 2px; }

        .body-pad { padding: 0.4in 0.6in; }

        .card { background: #f7f7f9; border-radius: 8px; padding: 14px 16px; margin-bottom: 12px; }
        .card-title { font-size: 10px; text-transform: uppercase; font-weight: bold; color: {{ $settings->color_primary ?? '#1F3864' }}; letter-spacing: 0.5px; margin-bottom: 8px; }
        .cards-row { display: flex; gap: 12px; }
        .cards-row > .card { flex: 1; min-width: 0; }
        .field-row { display: flex; justify-content: space-between; gap: 10px; padding: 2px 0; }
        .field-row dt { color: #777; flex-shrink: 0; }
        .field-row dd { margin: 0; font-weight: 600; text-align: right; overflow-wrap: break-word; }

        table.billing { width: 100%; border-collapse: collapse; }
        table.billing td { padding: 5px 0; overflow-wrap: break-word; }
        table.billing td.amount { text-align: right; font-family: 'Courier New', monospace; }
        table.billing tr.total td { border-top: 2px solid {{ $settings->color_primary ?? '#1F3864' }}; font-weight: bold; font-size: 15px; padding-top: 8px; color: {{ $settings->color_primary ?? '#1F3864' }}; }

        .declaration { font-size: 10px; color: #555; margin-top: 4px; overflow-wrap: break-word; }
        .terms { font-size: 9px; color: #555; margin-top: 4px; white-space: pre-line; overflow-wrap: break-word; }

        .signatures { display: flex; gap: 40px; margin-top: 32px; }
        .signature-block { flex: 1; }
        .signature-line { border-top: 1px solid #111; margin-top: 40px; padding-top: 4px; font-size: 10px; color: #555; }

        .footer { margin-top: 20px; padding-top: 10px; border-top: 1px solid #ddd; font-size: 9px; color: #999; text-align: center; overflow-wrap: break-word; }
    </style>
</head>
<body>
    <div class="toolbar">
        <button class="print-btn" onclick="window.print()">Print</button>
    </div>

    <div class="paper">
    <div class="band">
        <div>
            @if ($settings->logo_path)
                <img src="{{ asset('storage/' . $settings->logo_path) }}" alt="{{ $settings->company_name }}">
            @endif
            <div class="company-name">{{ $settings->company_name }}</div>
        </div>
        <div class="doc-title">
            <h1>WAYBILL</h1>
            <div class="tracking-number">{{ $shipment->tracking_number }}</div>
            <div class="doc-date">Issued {{ $shipment->created_at->format('d M Y') }}</div>
        </div>
    </div>

    <div class="body-pad">
    <div class="cards-row">
        <div class="card">
            <div class="card-title">Sender declaration</div>
            <dl>
                <div class="field-row"><dt>Name</dt><dd>{{ $shipment->sender_name }}</dd></div>
                <div class="field-row"><dt>Phone</dt><dd>{{ $shipment->sender_phone }}</dd></div>
                @if ($shipment->sender_email)
                    <div class="field-row"><dt>Email</dt><dd>{{ $shipment->sender_email }}</dd></div>
                @endif
                <div class="field-row"><dt>Address</dt><dd>{{ $shipment->origin_address }}</dd></div>
            </dl>
        </div>
        <div class="card">
            <div class="card-title">Receiver declaration</div>
            <dl>
                <div class="field-row"><dt>Name</dt><dd>{{ $shipment->receiver_name }}</dd></div>
                <div class="field-row"><dt>Phone</dt><dd>{{ $shipment->receiver_phone }}</dd></div>
                @if ($shipment->receiver_alternate_phone)
                    <div class="field-row"><dt>Alt. phone</dt><dd>{{ $shipment->receiver_alternate_phone }}</dd></div>
                @endif
                @if ($shipment->receiver_email)
                    <div class="field-row"><dt>Email</dt><dd>{{ $shipment->receiver_email }}</dd></div>
                @endif
                <div class="field-row"><dt>Address</dt><dd>{{ $shipment->destination_address }}</dd></div>
            </dl>
        </div>
    </div>

    <div class="cards-row">
        <div class="card">
            <div class="card-title">Shipment particulars</div>
            <dl>
                <div class="field-row"><dt>Client</dt><dd>{{ $shipment->clientUser?->name ?? 'Walk-in customer' }}</dd></div>
                <div class="field-row"><dt>Account</dt><dd>{{ $shipment->clientAccount?->account_name ?? '—' }}</dd></div>
                <div class="field-row"><dt>Service type</dt><dd>{{ $shipment->serviceType?->name ?? $shipment->shipping_type }}</dd></div>
                <div class="field-row"><dt>Weight</dt><dd>{{ $shipment->weight_kg ?? '—' }} kg</dd></div>
                <div class="field-row"><dt>Quantity</dt><dd>{{ $shipment->quantity ?? 1 }}</dd></div>
                <div class="field-row"><dt>Route</dt><dd>{{ $shipment->originCity?->name ?? '—' }} → {{ $shipment->destinationCity?->name ?? '—' }}</dd></div>
            </dl>
            <p style="font-size: 10px; color: #555; margin-top: 8px; overflow-wrap: break-word;"><strong>Goods:</strong> {{ $shipment->package_description }}</p>
            @if ($shipment->special_instructions)
                <p style="font-size: 10px; color: #555; margin-top: 2px; overflow-wrap: break-word;"><strong>Instructions:</strong> {{ $shipment->special_instructions }}</p>
            @endif
        </div>
        <div class="card">
            <div class="card-title">Billing</div>
            <table class="billing">
                <tr><td>Freight</td><td class="amount">{{ number_format($shipment->base_amount, 2) }}</td></tr>
                <tr><td>Surcharges</td><td class="amount">{{ number_format($shipment->surcharge_amount, 2) }}</td></tr>
                @if ($shipment->onforwarding_amount > 0)
                    <tr><td>Onforwarding</td><td class="amount">{{ number_format($shipment->onforwarding_amount, 2) }}</td></tr>
                @endif
                @if ($shipment->pickup_amount > 0)
                    <tr><td>Pickup fee</td><td class="amount">{{ number_format($shipment->pickup_amount, 2) }}</td></tr>
                @endif
                @if ($shipment->discount_amount > 0)
                    <tr><td>Discount</td><td class="amount">−{{ number_format($shipment->discount_amount, 2) }}</td></tr>
                @endif
                <tr><td>Insurance</td><td class="amount">{{ number_format($shipment->insurance_amount, 2) }}</td></tr>
                <tr><td>VAT</td><td class="amount">{{ number_format($shipment->vat_amount, 2) }}</td></tr>
                <tr class="total"><td>Total ({{ $settings->currency }})</td><td class="amount">{{ number_format($shipment->total_amount, 2) }}</td></tr>
            </table>
            @if ($shipment->is_cod)
                <p class="declaration">Cash on delivery: {{ number_format($shipment->cod_amount, 2) }} {{ $settings->currency }} to be collected from the receiver on delivery.</p>
            @endif
        </div>
    </div>

    <p class="declaration">By tendering this shipment, the sender confirms the goods described above do not include any item prohibited by {{ $settings->company_name }}'s carriage policy, and that the declared value and description are accurate to the best of their knowledge.</p>

    @if ($settings->waybill_terms)
        <div class="card-title" style="margin-top: 16px;">Terms &amp; conditions</div>
        <div class="terms">{{ $settings->waybill_terms }}</div>
    @endif

    <div class="signatures">
        <div class="signature-block">
            <div class="signature-line">Sender's signature</div>
        </div>
        <div class="signature-block">
            <div class="signature-line">Received by (name &amp; signature)</div>
        </div>
    </div>

    <div class="footer">{{ $settings->company_name }} — this document is a receipt and contract of carriage for tracking number {{ $shipment->tracking_number }}.</div>
    </div>
    </div>

</body>
</html>
