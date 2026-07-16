<x-layouts.app :title="$unit->exists ? 'Edit Unit' : 'Add Unit'">

    <form method="POST" action="{{ $unit->exists ? route('units.update', $unit) : route('units.store') }}" class="max-w-xl space-y-4 rounded-xl border border-line bg-surface-0 shadow-sm p-5">
        @csrf
        @if ($unit->exists) @method('PUT') @endif

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
            <label class="mb-1 block text-sm font-medium text-ink-900">Hub <x-required /></label>
            <select name="hub_id" class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                @foreach ($hubs as $hub)
                    <option value="{{ $hub->id }}" @selected(old('hub_id', $unit->hub_id) == $hub->id)>{{ $hub->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-ink-900">Unit name <x-required /></label>
            <input type="text" name="name" value="{{ old('name', $unit->name) }}" placeholder="e.g. Operations, Customer Service, Dispatch, Warehouse, Finance"
                   class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-ink-900">Code <x-required /></label>
            <input type="text" name="code" value="{{ old('code', $unit->code) }}" placeholder="e.g. LOS-01-OPS"
                   class="w-full rounded-md border border-line px-3 py-2 text-sm font-mono outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
        </div>

        <div class="flex justify-end gap-3 pt-2">
            <a href="{{ route('units.index') }}" class="rounded-md px-4 py-2 text-sm font-medium text-ink-500 hover:bg-surface-50">Cancel</a>
            <button type="submit" class="rounded-md bg-[var(--brand-primary)] px-4 py-2 text-sm font-semibold text-white hover:opacity-90">
                {{ $unit->exists ? 'Save changes' : 'Add unit' }}
            </button>
        </div>
    </form>

</x-layouts.app>
