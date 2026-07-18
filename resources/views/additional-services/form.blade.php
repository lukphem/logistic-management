<x-layouts.app :title="$additionalService->exists ? 'Edit Service' : 'Add Service'">

    <form method="POST" action="{{ $additionalService->exists ? route('additional-services.update', $additionalService) : route('additional-services.store') }}" class="max-w-xl space-y-4 rounded-xl border border-line bg-surface-0 shadow-sm p-5">
        @csrf
        @if ($additionalService->exists) @method('PUT') @endif

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
            <input type="text" name="name" value="{{ old('name', $additionalService->name) }}" placeholder="e.g. Packaging"
                   class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-ink-900">Price <x-required /></label>
            <input type="number" step="0.01" min="0" name="price" value="{{ old('price', $additionalService->price) }}"
                   class="w-full max-w-[10rem] rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
        </div>

        <label class="flex items-center gap-2 text-sm text-ink-900">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $additionalService->exists ? $additionalService->is_active : true)) class="rounded border-line">
            Active (selectable when booking or checking a rate)
        </label>

        <div class="flex justify-end gap-3 pt-2">
            <a href="{{ route('additional-services.index') }}" class="rounded-md px-4 py-2 text-sm font-medium text-ink-500 hover:bg-surface-50">Cancel</a>
            <button type="submit" class="rounded-md bg-[var(--brand-primary)] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 hover:shadow-md">
                {{ $additionalService->exists ? 'Save changes' : 'Add service' }}
            </button>
        </div>
    </form>

</x-layouts.app>
