<x-layouts.app :title="'Zone Mapping'">

    @if (session('status'))
        <div class="mb-5 rounded-xl bg-status-delivered/10 px-4 py-3 text-sm font-medium text-status-delivered">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-5 rounded-xl bg-status-exception/10 px-4 py-3 text-sm text-status-exception">
            {{ $errors->first() }}
        </div>
    @endif

    <p class="mb-4 text-sm text-ink-500">
        Origin → destination pricing for every zone-to-zone rate card in one place — the same data as each rate card's
        own zone-price editor, just not split across cards.
    </p>

    <div class="mb-5">
        <form method="GET" class="flex items-center gap-3">
            <select name="rate_card_id" onchange="this.form.submit()"
                    class="rounded-md border border-line bg-surface-0 px-3 py-2 text-sm text-ink-900 outline-none focus:border-[var(--brand-primary)]">
                <option value="">All zone-to-zone rate cards</option>
                @foreach ($rateCards as $rateCard)
                    <option value="{{ $rateCard->id }}" @selected(request('rate_card_id') == $rateCard->id)>{{ $rateCard->name }}</option>
                @endforeach
            </select>
        </form>
    </div>

    <div class="overflow-hidden rounded-xl border border-line bg-surface-0 shadow-sm">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="border-b border-line text-xs uppercase tracking-wide text-ink-500">
                    <th class="px-5 py-3 font-medium">Rate card</th>
                    <th class="px-5 py-3 font-medium">Origin</th>
                    <th class="px-5 py-3 font-medium">Destination</th>
                    <th class="px-5 py-3 font-medium">Price</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($mappings as $mapping)
                    <tr class="border-b border-line last:border-0 odd:bg-surface-0 even:bg-surface-50/50 hover:bg-[var(--brand-primary)]/5 transition-colors">
                        <td class="px-5 py-3 text-ink-900">{{ $mapping->rateCard->name }}</td>
                        <td class="px-5 py-3 text-ink-900">{{ $mapping->originZone->name }}</td>
                        <td class="px-5 py-3 text-ink-900">{{ $mapping->destinationZone->name }}</td>
                        <td class="px-5 py-3 font-mono text-ink-900">{{ number_format($mapping->price, 2) }}</td>
                        <td class="px-5 py-3 text-right">
                            <form method="POST" action="{{ route('zone-mappings.destroy', $mapping) }}" onsubmit="return confirm('Remove this mapping?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-sm font-medium text-status-exception transition-colors hover:text-status-exception/70">Remove</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-5 py-8 text-center text-sm text-ink-500">No zone mappings configured yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-5">{{ $mappings->links() }}</div>

    <div class="mt-6 rounded-xl border border-line bg-surface-0 p-5 shadow-sm">
        <h2 class="mb-4 text-sm font-semibold text-ink-900">Add a mapping</h2>
        <form method="POST" action="{{ route('zone-mappings.store') }}" class="flex flex-wrap items-end gap-4">
            @csrf
            <div>
                <label class="mb-1 block text-sm font-medium text-ink-900">Rate card <x-required /></label>
                <select name="rate_card_id" class="w-52 rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                    @foreach ($rateCards as $rateCard)
                        <option value="{{ $rateCard->id }}">{{ $rateCard->name }}</option>
                    @endforeach
                </select>
                @if ($rateCards->isEmpty())
                    <p class="mt-1 text-xs text-status-exception">No zone-to-zone rate cards exist yet — create one under Billing → Rate Cards first.</p>
                @endif
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-ink-900">Origin zone <x-required /></label>
                <select name="origin_zone_id" class="w-44 rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                    @foreach ($zones as $zone)
                        <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-ink-900">Destination zone <x-required /></label>
                <select name="destination_zone_id" class="w-44 rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                    @foreach ($zones as $zone)
                        <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-ink-900">Price <x-required /></label>
                <input type="number" step="0.01" min="0" name="price" class="w-32 rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
            </div>
            <button type="submit" class="rounded-md bg-[var(--brand-primary)] px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:opacity-90 hover:shadow-md">
                Save mapping
            </button>
        </form>
    </div>

</x-layouts.app>
