{{-- Warehouse: a warm grid of package icons, brand-secondary leading the palette. --}}
<div class="absolute inset-0 grid grid-cols-6 gap-6 p-8 opacity-[0.12]">
    @for ($i = 0; $i < 30; $i++)
        <x-icon name="package" class="h-8 w-8 text-white" />
    @endfor
</div>

<div class="relative z-10 p-10">
    <div class="flex items-center gap-2.5">
        @if (config('branding.logo_url'))
            <img src="{{ config('branding.logo_url') }}" alt="{{ config('branding.company_name') }}" class="h-9 w-9 rounded-md object-cover ring-1 ring-white/20">
        @else
            <span class="grid h-9 w-9 place-items-center rounded-md bg-white font-mono text-sm font-bold text-[var(--brand-primary)]">
                {{ strtoupper(substr(config('branding.company_name'), 0, 2)) }}
            </span>
        @endif
        <span class="text-sm font-semibold tracking-wide text-white">{{ config('branding.company_name') }}</span>
    </div>
</div>

<div class="relative z-10 flex flex-1 items-center px-14">
    <div class="grid h-24 w-24 shrink-0 place-items-center rounded-2xl bg-[var(--brand-secondary)] shadow-lg">
        <x-icon name="package" class="h-11 w-11 text-ink-900" />
    </div>
    <div class="ml-6">
        <p class="text-sm font-medium uppercase tracking-widest text-[var(--brand-secondary)]">In the warehouse</p>
        <p class="mt-1 max-w-[14rem] text-sm text-white/70">Every parcel scanned, sorted, and staged for the next leg out.</p>
    </div>
</div>

<div class="relative z-10 p-10">
    <p class="max-w-xs text-lg font-medium leading-snug text-white">
        Built for the whole operation — hubs, riders, and billing in one place.
    </p>
</div>
