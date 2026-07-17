@props(['name', 'class' => 'h-5 w-5'])

@php
    $paths = [
        'dashboard' => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
        'box' => '<path d="M3 7.5 12 3l9 4.5v9L12 21l-9-4.5v-9Z"/><path d="M3 7.5 12 12l9-4.5"/><path d="M12 12v9"/>',
        'setups' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.6a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1Z"/>',
        'building' => '<rect x="4" y="3" width="16" height="18" rx="1"/><path d="M9 21v-4h6v4M9 7h1M14 7h1M9 11h1M14 11h1M9 15h1M14 15h1"/>',
        'layers' => '<path d="m12 2 9 5-9 5-9-5 9-5Z"/><path d="m3 12 9 5 9-5"/><path d="m3 17 9 5 9-5"/>',
        'list-check' => '<path d="m4 6 1.5 1.5L8 5"/><path d="M11 6h9"/><path d="m4 12 1.5 1.5L8 11"/><path d="M11 12h9"/><path d="m4 18 1.5 1.5L8 17"/><path d="M11 18h9"/>',
        'sliders' => '<path d="M4 6h9M17 6h3M4 12h3M9 12h11M4 18h13M19 18h1"/><circle cx="15" cy="6" r="2"/><circle cx="7" cy="12" r="2"/><circle cx="17" cy="18" r="2"/>',
        'chevron' => '<path d="m6 9 6 6 6-6"/>',
        'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/>',
        'search' => '<circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/>',
        'truck' => '<path d="M3 6h10v9H3z"/><path d="M13 10h4l4 3.5V15h-8z"/><circle cx="7" cy="18" r="2"/><circle cx="17" cy="18" r="2"/>',
        'map-pin' => '<path d="M12 21s-7-6.5-7-11.5a7 7 0 1 1 14 0C19 14.5 12 21 12 21Z"/><circle cx="12" cy="9.5" r="2.3"/>',
        'route' => '<circle cx="5" cy="6" r="2"/><circle cx="19" cy="18" r="2"/><path d="M5 8v3a4 4 0 0 0 4 4h6a4 4 0 0 1 4 4" stroke-dasharray="2.5 3"/>',
        'package' => '<path d="M21 8 12 3 3 8v8l9 5 9-5V8Z"/><path d="M3 8l9 5 9-5"/><path d="M12 13v8"/><path d="m7 5.5 9 5"/>',
        'menu' => '<path d="M4 7h16M4 12h16M4 17h16"/>',
        'close' => '<path d="M6 6l12 12M18 6 6 18"/>',
    ];
@endphp

<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
     stroke-linecap="round" stroke-linejoin="round" {{ $attributes->merge(['class' => $class]) }}>
    {!! $paths[$name] ?? '' !!}
</svg>
