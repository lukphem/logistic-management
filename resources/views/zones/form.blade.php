<x-layouts.app :title="$zone->exists ? 'Edit Zone' : 'Add Zone'">

    <form method="POST" action="{{ $zone->exists ? route('zones.update', $zone) : route('zones.store') }}" class="max-w-xl space-y-4 rounded-xl border border-line bg-surface-0 shadow-sm p-5">
        @csrf
        @if ($zone->exists) @method('PUT') @endif

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
            <label class="mb-1 block text-sm font-medium text-ink-900">Zone name</label>
            <input type="text" name="name" value="{{ old('name', $zone->name) }}" placeholder="e.g. Lagos Mainland"
                   class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-ink-900">Code</label>
            <input type="text" name="code" value="{{ old('code', $zone->code) }}" placeholder="e.g. LAG-M"
                   class="w-full rounded-md border border-line px-3 py-2 text-sm font-mono outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-ink-900">Hub</label>
            <select name="hub_id" class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                <option value="">— None —</option>
                @foreach ($hubs as $hub)
                    <option value="{{ $hub->id }}" @selected(old('hub_id', $zone->hub_id) == $hub->id)>{{ $hub->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="flex justify-end gap-3 pt-2">
            <a href="{{ route('zones.index') }}" class="rounded-md px-4 py-2 text-sm font-medium text-ink-500 hover:bg-surface-50">Cancel</a>
            <button type="submit" class="rounded-md bg-[var(--brand-primary)] px-4 py-2 text-sm font-semibold text-white hover:opacity-90">
                {{ $zone->exists ? 'Save changes' : 'Add zone' }}
            </button>
        </div>
    </form>

</x-layouts.app>
