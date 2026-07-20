<x-layouts.app :title="'Acknowledgement'">

    @if (session('status'))
        <div class="mb-5 rounded-xl bg-status-delivered/10 px-4 py-3 text-sm font-medium text-status-delivered">
            {{ session('status') }}
        </div>
    @endif

    <p class="mb-5 text-sm text-ink-500">
        A signed acknowledgement copy sent back to origin is priced as its own small reverse shipment — pick which
        service type and weight to price that reverse leg at, then set what percentage of that rate to charge as the
        acknowledgement fee.
    </p>

    <form method="POST" action="{{ route('additional-services.update', $additionalService) }}" class="max-w-xl space-y-4 rounded-xl border border-line bg-surface-0 shadow-sm p-5">
        @csrf
        @method('PUT')

        @if ($errors->any())
            <div class="rounded-md bg-status-exception/10 px-4 py-3 text-sm text-status-exception">
                <ul class="list-inside list-disc">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <label class="flex items-center gap-2 text-sm text-ink-900">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $additionalService->is_active)) class="rounded border-line">
            Active (selectable when booking or checking a rate)
        </label>

        <div>
            <label class="mb-1 block text-sm font-medium text-ink-900">Service type for the return document <x-required /></label>
            <select name="reverse_service_type_id" class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                <option value="">— Select —</option>
                @foreach ($serviceTypes as $serviceType)
                    <option value="{{ $serviceType->id }}" @selected(old('reverse_service_type_id', $option?->reverse_service_type_id) == $serviceType->id)>{{ $serviceType->name }}</option>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-ink-500">Prices the return document as its own shipment on the same route, using this service type.</p>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-ink-900">Document weight (kg) <x-required /></label>
            <input type="number" step="0.01" min="0.01" name="reverse_weight_kg" value="{{ old('reverse_weight_kg', $option?->reverse_weight_kg) }}" placeholder="e.g. 0.1"
                   class="w-full max-w-[10rem] rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-ink-900">Percentage of the reverse shipment's rate <x-required /></label>
            <input type="number" step="0.01" min="0" name="price" value="{{ old('price', $option?->price) }}" placeholder="e.g. 2.5"
                   class="w-full max-w-[10rem] rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
            <p class="mt-1 text-xs text-ink-500">Charges this percentage of what the return document's own reverse shipment would cost — not a cut of the outbound freight.</p>
        </div>

        <label class="flex items-center gap-2 text-sm text-ink-900">
            <input type="checkbox" name="is_vatable" value="1" @checked(old('is_vatable', $option?->is_vatable ?? true)) class="rounded border-line">
            Taxable (included in VAT)
        </label>

        <div class="flex justify-end gap-3 pt-2">
            <a href="{{ route('additional-services.index') }}" class="rounded-md px-4 py-2 text-sm font-medium text-ink-500 hover:bg-surface-50">Cancel</a>
            <button type="submit" class="rounded-md bg-[var(--brand-primary)] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 hover:shadow-md">
                Save changes
            </button>
        </div>
    </form>

</x-layouts.app>
