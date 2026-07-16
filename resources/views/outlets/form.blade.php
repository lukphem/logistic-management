<x-layouts.app :title="$outlet->exists ? 'Edit Outlet' : 'Add Outlet'">

    <form method="POST" action="{{ $outlet->exists ? route('outlets.update', $outlet) : route('outlets.store') }}" class="max-w-xl space-y-4 rounded-xl border border-line bg-surface-0 shadow-sm p-5">
        @csrf
        @if ($outlet->exists) @method('PUT') @endif

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
                    <option value="{{ $hub->id }}" @selected(old('hub_id', $outlet->hub_id) == $hub->id)>{{ $hub->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-ink-900">Outlet name <x-required /></label>
            <input type="text" name="name" value="{{ old('name', $outlet->name) }}" placeholder="e.g. Ikeja Agent Counter"
                   class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-ink-900">Code <x-required /></label>
            <input type="text" name="code" value="{{ old('code', $outlet->code) }}" placeholder="e.g. LOS-01-OUT-03"
                   class="w-full rounded-md border border-line px-3 py-2 text-sm font-mono outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-ink-900">Address <x-required /></label>
            <textarea name="address" rows="2"
                      class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">{{ old('address', $outlet->address) }}</textarea>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="mb-1 block text-sm font-medium text-ink-900">Latitude</label>
                <input type="text" name="latitude" value="{{ old('latitude', $outlet->latitude) }}"
                       class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-ink-900">Longitude</label>
                <input type="text" name="longitude" value="{{ old('longitude', $outlet->longitude) }}"
                       class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
            </div>
        </div>

        <label class="flex items-center gap-2 text-sm text-ink-900">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $outlet->is_active ?? true)) class="rounded border-line">
            Active
        </label>

        <div class="flex justify-end gap-3 pt-2">
            <a href="{{ route('outlets.index') }}" class="rounded-md px-4 py-2 text-sm font-medium text-ink-500 hover:bg-surface-50">Cancel</a>
            <button type="submit" class="rounded-md bg-[var(--brand-primary)] px-4 py-2 text-sm font-semibold text-white hover:opacity-90">
                {{ $outlet->exists ? 'Save changes' : 'Add outlet' }}
            </button>
        </div>
    </form>

</x-layouts.app>
