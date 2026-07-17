{{-- Route: a truck driving a dashed path between two map pins. --}}
<div class="relative z-10 p-10">
    <div class="flex items-center gap-2.5">
        @if (config('branding.logo_url'))
            <img src="{{ config('branding.logo_url') }}" alt="{{ config('branding.company_name') }}" class="h-9 w-9 rounded-md object-cover ring-1 ring-white/20">
        @else
            <span class="grid h-9 w-9 place-items-center rounded-md bg-[var(--brand-secondary)] font-mono text-sm font-bold text-ink-900">
                {{ strtoupper(substr(config('branding.company_name'), 0, 2)) }}
            </span>
        @endif
        <span class="text-sm font-semibold tracking-wide text-white">{{ config('branding.company_name') }}</span>
    </div>
</div>

<div class="relative z-10 flex-1 px-14">
    <div class="relative mt-6 h-40">
        <x-icon name="map-pin" class="absolute left-0 top-2 h-7 w-7 text-[var(--brand-secondary)]" />
        <x-icon name="map-pin" class="absolute right-0 bottom-0 h-7 w-7 text-white" />
        <svg class="absolute inset-0 h-full w-full" viewBox="0 0 400 160" fill="none">
            <path d="M20 20 Q 200 20 380 140" stroke="white" stroke-opacity="0.35" stroke-width="2.5" stroke-dasharray="6 8" stroke-linecap="round"/>
        </svg>
        <x-icon name="truck" class="truck-drive absolute h-9 w-9 text-white" style="left: 160px; top: 62px;" />
    </div>

    <x-icon name="package" class="absolute h-6 w-6 text-white/25" style="left: 60%; top: 8%;" />
    <x-icon name="package" class="absolute h-5 w-5 text-white/20" style="left: 15%; top: 55%;" />
    <x-icon name="package" class="absolute h-7 w-7 text-white/15" style="left: 78%; top: 62%;" />
</div>

<div class="relative z-10 p-10">
    <p class="max-w-xs text-lg font-medium leading-snug text-white">
        Every shipment, tracked from pickup to doorstep.
    </p>
    <p class="mt-2 max-w-xs text-sm text-white/60">
        Sign in to manage hubs, shipments, and billing across your network.
    </p>
</div>
