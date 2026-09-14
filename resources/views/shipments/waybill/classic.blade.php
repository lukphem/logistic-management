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

        /* On screen this simulates an A4 sheet sitting on a desk — the
           @page margin above is what actually controls print output,
           so this screen-only padding/shadow is removed at print time
           to avoid doubling up the whitespace. */
        .paper { max-width: 8.27in; margin: 24px auto; padding: 0.6in; background: #fff; box-shadow: 0 2px 12px rgba(0,0,0,0.2); overflow-wrap: break-word; word-break: break-word; }
        @media print {
            body { background: #fff; }
            .paper { max-width: none; margin: 0; padding: 0; box-shadow: none; }
        }

        .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 3px solid {{ $settings->color_primary ?? '#1F3864' }}; padding-bottom: 14px; margin-bottom: 16px; }
        .company img { height: 48px; max-width: 160px; object-fit: contain; margin-bottom: 6px; }
        .company-name { font-weight: bold; font-size: 18px; overflow-wrap: break-word; }
        .doc-title { text-align: right; }
        .doc-title h1 { margin: 0; font-size: 22px; color: {{ $settings->color_primary ?? '#1F3864' }}; letter-spacing: 1px; }
        .doc-title .tracking-number { font-family: 'Courier New', monospace; font-size: 16px; font-weight: bold; margin-top: 4px; overflow-wrap: break-word; }
        .doc-title .doc-date { font-size: 11px; color: #666; margin-top: 2px; }
        .section-title { font-size: 11px; text-transform: uppercase; font-weight: bold; color: #555; letter-spacing: 0.5px; margin: 18px 0 6px; border-bottom: 1px solid #ddd; padding-bottom: 4px; }
        .grid-2 { display: flex; gap: 24px; }
        .grid-2 > div { flex: 1; min-width: 0; }
        .field-row { display: flex; justify-content: space-between; gap: 10px; padding: 3px 0; border-bottom: 1px dotted #ddd; }
        .field-row dt { color: #666; flex-shrink: 0; }
        .field-row dd { margin: 0; font-weight: 500; text-align: right; overflow-wrap: break-word; }
        table.billing { width: 100%; border-collapse: collapse; margin-top: 6px; }
        table.billing td { padding: 5px 0; overflow-wrap: break-word; }
        table.billing td.amount { text-align: right; font-family: 'Courier New', monospace; }
        table.billing tr.total td { border-top: 2px solid #111; font-weight: bold; font-size: 14px; padding-top: 8px; }
        .declaration { font-size: 10px; color: #444; background: #f7f7f7; border-radius: 6px; padding: 10px 12px; margin-top: 16px; overflow-wrap: break-word; }
        .terms { font-size: 9px; color: #555; margin-top: 16px; white-space: pre-line; overflow-wrap: break-word; }
        .signatures { display: flex; gap: 40px; margin-top: 40px; }
        .signature-block { flex: 1; }
        .signature-line { border-top: 1px solid #111; margin-top: 40px; padding-top: 4px; font-size: 10px; color: #555; }
        .footer { margin-top: 24px; padding-top: 10px; border-top: 1px solid #ddd; font-size: 9px; color: #999; text-align: center; overflow-wrap: break-word; }
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
            <h1>WAYBILL</h1>
            <div class="tracking-number">{{ $shipment->tracking_number }}</div>
            <div class="doc-date">Issued {{ $shipment->created_at->format('d M Y') }}</div>
        </div>
    </div>

    <div class="grid-2">
        <div>
            <div class="section-title">Sender declaration</div>
            <dl>
                <div class="field-row"><dt>Name</dt><dd>{{ $shipment->sender_name }}</dd></div>
                <div class="field-row"><dt>Phone</dt><dd>{{ $shipment->sender_phone }}</dd></div>
                @if ($shipment->sender_email)
                    <div class="field-row"><dt>Email</dt><dd>{{ $shipment->sender_email }}</dd></div>
                @endif
                <div class="field-row"><dt>Address</dt><dd>{{ $shipment->origin_address }}</dd></div>
            </dl>
        </div>
        <div>
            <div class="section-title">Receiver declaration</div>
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

    <div class="section-title">Shipment particulars</div>
    <div class="grid-2">
        <div>
            <dl>
                <div class="field-row"><dt>Client</dt><dd>{{ $shipment->clientUser?->name ?? 'Walk-in customer' }}</dd></div>
                <div class="field-row"><dt>Account</dt><dd>{{ $shipment->clientAccount?->account_name ?? '—' }}</dd></div>
                <div class="field-row"><dt>Service type</dt><dd>{{ $shipment->serviceType?->name ?? $shipment->shipping_type }}</dd></div>
                <div class="field-row"><dt>Description of goods</dt><dd>{{ $shipment->package_description }}</dd></div>
            </dl>
        </div>
        <div>
            <dl>
                <div class="field-row"><dt>Weight</dt><dd>{{ $shipment->weight_kg ?? '—' }} kg</dd></div>
                <div class="field-row"><dt>Quantity</dt><dd>{{ $shipment->quantity ?? 1 }}</dd></div>
                <div class="field-row"><dt>Packaging</dt><dd>{{ $shipment->carton_size ? ucfirst($shipment->carton_size) : '—' }}</dd></div>
                <div class="field-row"><dt>Route</dt><dd>{{ $shipment->originCity?->name ?? '—' }} → {{ $shipment->destinationCity?->name ?? '—' }}</dd></div>
            </dl>
        </div>
    </div>
    @if ($shipment->special_instructions)
        <p style="font-size: 10px; color: #555; margin-top: 6px; overflow-wrap: break-word;"><strong>Special instructions:</strong> {{ $shipment->special_instructions }}</p>
    @endif

    <div class="section-title">Billing</div>
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
        <p style="font-size: 10px; color: #555; margin-top: 4px; overflow-wrap: break-word;">Cash on delivery: {{ number_format($shipment->cod_amount, 2) }} {{ $settings->currency }} to be collected from the receiver on delivery.</p>
    @endif

    <div class="declaration">
        By tendering this shipment, the sender confirms the goods described above do not include any item prohibited by {{ $settings->company_name }}'s carriage policy, and that the declared value and description are accurate to the best of their knowledge.
    </div>

    @if ($settings->waybill_terms)
        <div class="section-title">Terms &amp; conditions</div>
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

</body>
</html>
