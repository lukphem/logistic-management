<x-layouts.app :title="$vehicleType->exists ? 'Edit Vehicle Type' : 'Add Vehicle Type'">

    <form method="POST" action="{{ $vehicleType->exists ? route('vehicle-types.update', $vehicleType) : route('vehicle-types.store') }}" class="max-w-xl space-y-4 rounded-xl border border-line bg-surface-0 shadow-sm p-5">
        @csrf
        @if ($vehicleType->exists) @method('PUT') @endif

        @if ($errors->any())
            <div class="rounded-md bg-status-exception/10 px-4 py-3 text-sm text-status-exception">
                <ul class="list-inside list-disc">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div>
            <label class="mb-1 block text-sm font-medium text-ink-900">Name <x-required /></label>
            <input type="text" name="name" value="{{ old('name', $vehicleType->name) }}" placeholder="e.g. 30-Ton Trailer"
                   class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-ink-900">Code <x-required /></label>
            <input type="text" name="code" value="{{ old('code', $vehicleType->code) }}" placeholder="e.g. TRLR30"
                   class="w-full rounded-md border border-line px-3 py-2 text-sm font-mono outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-ink-900">Max weight capacity (kg) <span class="text-xs font-normal text-ink-500">(optional)</span></label>
            <input type="number" step="0.01" min="0" name="max_weight_capacity" value="{{ old('max_weight_capacity', $vehicleType->max_weight_capacity) }}"
                   class="w-full max-w-[12rem] rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
            <p class="mt-1 text-xs text-ink-500">A Fleet Billing rate's Max weight limit can't exceed this, and a shipment heavier than this is refused at quote time regardless of any rate.</p>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-ink-900">Cargo dimensions (cm) <span class="text-xs font-normal text-ink-500">(optional)</span></label>
            <div class="flex max-w-md gap-3">
                <input type="number" step="0.1" min="0" name="max_length_cm" value="{{ old('max_length_cm', $vehicleType->max_length_cm) }}" placeholder="Length"
                       class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
                <input type="number" step="0.1" min="0" name="max_width_cm" value="{{ old('max_width_cm', $vehicleType->max_width_cm) }}" placeholder="Width"
                       class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
                <input type="number" step="0.1" min="0" name="max_height_cm" value="{{ old('max_height_cm', $vehicleType->max_height_cm) }}" placeholder="Height"
                       class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
            </div>
            <p class="mt-1 text-xs text-ink-500">This vehicle's cargo space — separate from a shipment's own volumetric weight, which is about pricing, not fit.</p>
        </div>

        <label class="flex items-center gap-2 text-sm text-ink-900">
            <input type="checkbox" name="is_open_body" value="1" @checked(old('is_open_body', $vehicleType->is_open_body)) class="rounded border-line">
            Open body (flatbed/open truck, not enclosed)
        </label>

        <label class="flex items-center gap-2 text-sm text-ink-900">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $vehicleType->exists ? $vehicleType->is_active : true)) class="rounded border-line">
            Active
        </label>

        <div class="flex justify-end gap-3 pt-2">
            <a href="{{ route('vehicle-types.index') }}" class="rounded-md px-4 py-2 text-sm font-medium text-ink-500 hover:bg-surface-50">Cancel</a>
            <button type="submit" class="rounded-md bg-[var(--brand-primary)] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 hover:shadow-md">
                {{ $vehicleType->exists ? 'Save changes' : 'Add vehicle type' }}
            </button>
        </div>
    </form>

</x-layouts.app>
