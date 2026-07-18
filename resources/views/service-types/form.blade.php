<x-layouts.app :title="$serviceType->exists ? 'Edit Service Type' : 'Add Service Type'">

    <form method="POST" action="{{ $serviceType->exists ? route('service-types.update', $serviceType) : route('service-types.store') }}" class="max-w-xl space-y-4 rounded-xl border border-line bg-surface-0 shadow-sm p-5">
        @csrf
        @if ($serviceType->exists) @method('PUT') @endif

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
            <input type="text" name="name" value="{{ old('name', $serviceType->name) }}" placeholder="e.g. Express"
                   class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-ink-900">Code <x-required /></label>
            <input type="text" name="code" value="{{ old('code', $serviceType->code) }}" placeholder="e.g. EXP"
                   class="w-full max-w-[10rem] rounded-md border border-line px-3 py-2 text-sm font-mono uppercase outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
        </div>

        <label class="flex items-center gap-2 text-sm text-ink-900">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $serviceType->exists ? $serviceType->is_active : true)) class="rounded border-line">
            Active (selectable when booking a shipment)
        </label>

        <div class="flex justify-end gap-3 pt-2">
            <a href="{{ route('service-types.index') }}" class="rounded-md px-4 py-2 text-sm font-medium text-ink-500 hover:bg-surface-50">Cancel</a>
            <button type="submit" class="rounded-md bg-[var(--brand-primary)] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 hover:shadow-md">
                {{ $serviceType->exists ? 'Save changes' : 'Add service type' }}
            </button>
        </div>
    </form>

</x-layouts.app>
