{{-- Map: a dotted map field with several pins and crossing routes, using status colors for variety. --}}
<div class="absolute inset-0 opacity-20" style="background-image: radial-gradient(white 1px, transparent 1px); background-size: 18px 18px;"></div>

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

<div class="relative z-10 flex-1 px-12">
    <div class="relative h-48">
        <svg class="absolute inset-0 h-full w-full" viewBox="0 0 400 190" fill="none">
            <path d="M30 150 Q 140 40 200 90 T 370 30" stroke="white" stroke-opacity="0.3" stroke-width="2" stroke-dasharray="5 7" stroke-linecap="round"/>
            <path d="M30 150 Q 120 170 220 140 T 370 30" stroke="#22c55e" stroke-opacity="0.4" stroke-width="2" stroke-dasharray="5 7" stroke-linecap="round"/>
        </svg>
        <x-icon name="map-pin" class="absolute h-6 w-6 text-white" style="left: 4%; top: 72%;" />
        <x-icon name="map-pin" class="absolute h-6 w-6 text-[var(--brand-secondary)]" style="left: 48%; top: 40%;" />
        <x-icon name="map-pin" class="absolute h-7 w-7 text-status-delivered" style="left: 88%; top: 10%;" />
        <x-icon name="map-pin" class="absolute h-5 w-5 text-status-transit" style="left: 55%; top: 68%;" />
    </div>
</div>

<div class="relative z-10 p-10">
    <p class="max-w-xs text-lg font-medium leading-snug text-white">
        One network, every destination mapped.
    </p>
    <p class="mt-2 max-w-xs text-sm text-white/60">
        From local drop-offs to cross-border delivery — sign in to see it all.
    </p>
</div>
