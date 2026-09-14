<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Waybill {{ $shipment->tracking_number }}</title>
    <style>
        @page { size: A4; margin: 0.45in; }
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; }
        body { font-family: Arial, Helvetica, sans-serif; color: #111; font-size: 10.5px; line-height: 1.3; background: #ddd; }

        .toolbar { display: flex; align-items: center; padding: 14px 20px; background: #f2f2f2; border-bottom: 2px solid #ccc; }
        .print-btn { padding: 8px 16px; font-size: 13px; border: 1px solid #111; background: #111; color: #fff; border-radius: 4px; cursor: pointer; }
        @media print { .toolbar { display: none !important; } }

        .paper { max-width: 8.27in; margin: 24px auto; padding: 0.45in; background: #fff; box-shadow: 0 2px 12px rgba(0,0,0,0.2); overflow-wrap: break-word; word-break: break-word; }
        @media print {
            body { background: #fff; }
            .paper { max-width: none; margin: 0; padding: 0; box-shadow: none; }
        }

        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #111; padding-bottom: 8px; margin-bottom: 10px; }
        .company img { height: 32px; max-width: 110px; object-fit: contain; }
        .company-name { font-weight: bold; font-size: 13px; overflow-wrap: break-word; }
        .doc-title { text-align: right; }
        .doc-title h1 { margin: 0; font-size: 15px; letter-spacing: 1px; }
        .doc-title .tracking-number { font-family: 'Courier New', monospace; font-size: 12px; font-weight: bold; overflow-wrap: break-word; }
        .doc-title .doc-date { font-size: 9px; color: #666; }

        table.grid-table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        table.grid-table td { border: 1px solid #ccc; padding: 3px 6px; vertical-align: top; overflow-wrap: break-word; }
        table.grid-table td.section-head { background: #eee; font-weight: bold; text-transform: uppercase; font-size: 8.5px; letter-spacing: 0.5px; }
        table.grid-table td.lbl { color: #666; font-size: 9px; width: 30%; white-space: nowrap; }

        table.billing { width: 100%; border-collapse: collapse; margin-top: 4px; }
        table.billing td { border: 1px solid #ccc; padding: 3px 6px; overflow-wrap: break-word; }
        table.billing td.amount { text-align: right; font-family: 'Courier New', monospace; }
        table.billing tr.total td { font-weight: bold; background: #f2f2f2; }

        .declaration { font-size: 8.5px; color: #555; margin-top: 6px; overflow-wrap: break-word; }
        .terms { font-size: 8px; color: #555; margin-top: 6px; white-space: pre-line; overflow-wrap: break-word; }

        .signatures { display: flex; gap: 30px; margin-top: 20px; }
        .signature-block { flex: 1; }
        .signature-line { border-top: 1px solid #111; margin-top: 26px; padding-top: 3px; font-size: 9px; color: #555; }

        .footer { margin-top: 10px; padding-top: 6px; border-top: 1px solid #ddd; font-size: 8px; color: #999; text-align: center; overflow-wrap: break-word; }
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

    <table class="grid-table">
        <tr><td class="section-head" colspan="4">Sender</td><td class="section-head" colspan="4">Receiver</td></tr>
        <tr>
            <td class="lbl">Name</td><td colspan="3">{{ $shipment->sender_name }}</td>
            <td class="lbl">Name</td><td colspan="3">{{ $shipment->receiver_name }}</td>
        </tr>
        <tr>
            <td class="lbl">Phone</td><td colspan="3">{{ $shipment->sender_phone }}</td>
            <td class="lbl">Phone</td>
            <td colspan="3">
                {{ $shipment->receiver_phone }}
                @if ($shipment->receiver_alternate_phone)
                    / {{ $shipment->receiver_alternate_phone }}
                @endif
            </td>
        </tr>
        <tr>
            <td class="lbl">Address</td><td colspan="3">{{ $shipment->origin_address }}</td>
            <td class="lbl">Address</td><td colspan="3">{{ $shipment->destination_address }}</td>
        </tr>
    </table>

    <table class="grid-table">
        <tr><td class="section-head" colspan="4">Shipment particulars</td></tr>
        <tr>
            <td class="lbl">Client</td><td>{{ $shipment->clientUser?->name ?? 'Walk-in customer' }}</td>
            <td class="lbl">Account</td><td>{{ $shipment->clientAccount?->account_name ?? '—' }}</td>
        </tr>
        <tr>
            <td class="lbl">Service type</td><td>{{ $shipment->serviceType?->name ?? $shipment->shipping_type }}</td>
            <td class="lbl">Route</td><td>{{ $shipment->originCity?->name ?? '—' }} → {{ $shipment->destinationCity?->name ?? '—' }}</td>
        </tr>
        <tr>
            <td class="lbl">Weight / Qty</td><td>{{ $shipment->weight_kg ?? '—' }} kg / {{ $shipment->quantity ?? 1 }}</td>
            <td class="lbl">Packaging</td><td>{{ $shipment->carton_size ? ucfirst($shipment->carton_size) : '—' }}</td>
        </tr>
        <tr>
            <td class="lbl">Goods</td><td colspan="3">{{ $shipment->package_description }}</td>
        </tr>
        @if ($shipment->special_instructions)
            <tr>
                <td class="lbl">Instructions</td><td colspan="3">{{ $shipment->special_instructions }}</td>
            </tr>
        @endif
    </table>

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

    <p class="declaration">By tendering this shipment, the sender confirms the goods described above do not include any item prohibited by {{ $settings->company_name }}'s carriage policy, and that the declared value and description are accurate to the best of their knowledge.</p>

    @if ($settings->waybill_terms)
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
