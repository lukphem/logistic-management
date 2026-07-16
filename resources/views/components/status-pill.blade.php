@php
    $map = [
        'booked' => ['label' => 'Booked', 'class' => 'bg-status-pending/10 text-status-pending'],
        'picked_up' => ['label' => 'Picked Up', 'class' => 'bg-status-transit/10 text-status-transit'],
        'in_transit' => ['label' => 'In Transit', 'class' => 'bg-status-transit/10 text-status-transit'],
        'arrived_at_hub' => ['label' => 'Arrived at Hub', 'class' => 'bg-status-transit/10 text-status-transit'],
        'out_for_delivery' => ['label' => 'Out for Delivery', 'class' => 'bg-status-transit/10 text-status-transit'],
        'delivered' => ['label' => 'Delivered', 'class' => 'bg-status-delivered/10 text-status-delivered'],
        'exception' => ['label' => 'Exception', 'class' => 'bg-status-exception/10 text-status-exception'],
        'returned' => ['label' => 'Returned', 'class' => 'bg-status-exception/10 text-status-exception'],
        'cancelled' => ['label' => 'Cancelled', 'class' => 'bg-ink-500/10 text-ink-500'],
    ];
    $entry = $map[$status] ?? ['label' => ucfirst(str_replace('_', ' ', $status)), 'class' => 'bg-ink-500/10 text-ink-500'];
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {$entry['class']}"]) }}>
    {{ $entry['label'] }}
</span>
