@component('mail::message')
# {{ $statusLabel }}

Your shipment **{{ $shipment->tracking_number }}** has been updated:

**{{ $statusLabel }}**

@if ($shipment->promised_delivery_at && $shipment->current_status !== 'delivered')
Estimated delivery: **{{ $shipment->promised_delivery_at->format('d M Y') }}**
@endif

@component('mail::button', ['url' => route('tracking.show', $shipment->tracking_number)])
Track this shipment
@endcomponent

Thanks,<br>
{{ config('branding.company_name') }}
@endcomponent
