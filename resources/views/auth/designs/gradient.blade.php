{{-- Vibrant: a multi-tone gradient blending both brand colors plus accent tones, more colorful than a flat brand-primary background. --}}
<div class="absolute inset-0" style="background: linear-gradient(135deg, var(--brand-primary) 0%, #7c3aed 35%, var(--brand-secondary) 70%, #f97316 100%);"></div>
<div class="absolute inset-0 bg-gradient-to-t from-black/25 to-transparent"></div>

<div class="relative z-10 p-10">
    <div class="flex items-center gap-2.5">
        @if (config('branding.logo_url'))
            <img src="{{ config('branding.logo_url') }}" alt="{{ config('branding.company_name') }}" class="h-9 w-9 rounded-md object-cover ring-1 ring-white/20">
        @else
            <span class="grid h-9 w-9 place-items-center rounded-md bg-white/90 font-mono text-sm font-bold text-ink-900">
                {{ strtoupper(substr(config('branding.company_name'), 0, 2)) }}
            </span>
        @endif
        <span class="text-sm font-semibold tracking-wide text-white">{{ config('branding.company_name') }}</span>
    </div>
</div>

<div class="relative z-10 flex-1 px-12">
    <div class="relative h-48">
        <x-icon name="truck" class="truck-drive absolute h-10 w-10 text-white drop-shadow" style="left: 10%; top: 55%;" />
        <x-icon name="package" class="absolute h-8 w-8 text-white/80" style="left: 55%; top: 15%;" />
        <x-icon name="package" class="absolute h-6 w-6 text-white/60" style="left: 75%; top: 55%;" />
        <x-icon name="map-pin" class="absolute h-8 w-8 text-white" style="left: 85%; top: 10%;" />
        <x-icon name="route" class="absolute h-7 w-7 text-white/70" style="left: 30%; top: 5%;" />
    </div>
</div>

<div class="relative z-10 p-10">
    <p class="max-w-xs text-lg font-medium leading-snug text-white">
        Logistics, in full color.
    </p>
    <p class="mt-2 max-w-xs text-sm text-white/70">
        Sign in to manage every hub, shipment, and invoice.
    </p>
</div>
