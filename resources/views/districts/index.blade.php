<x-layouts.app :title="'Districts / Areas'">

    @if (session('status'))
        <div class="mb-5 rounded-xl bg-status-delivered/10 px-4 py-3 text-sm font-medium text-status-delivered">
            {{ session('status') }}
        </div>
    @endif

    <p class="mb-4 text-sm text-ink-500">The most granular operating-location level — a neighborhood or area within a city.</p>

    <div class="mb-5 flex items-center justify-between gap-4">
        <form method="GET" class="flex items-center gap-3">
            <select name="state_id" onchange="this.form.submit()"
                    class="rounded-md border border-line bg-surface-0 px-3 py-2 text-sm text-ink-900 outline-none focus:border-[var(--brand-primary)]">
                <option value="">All states/provinces</option>
                @foreach ($states as $state)
                    <option value="{{ $state->id }}" @selected(request('state_id') == $state->id)>{{ $state->name }}</option>
                @endforeach
            </select>
            <select name="city_id" onchange="this.form.submit()"
                    class="rounded-md border border-line bg-surface-0 px-3 py-2 text-sm text-ink-900 outline-none focus:border-[var(--brand-primary)]">
                <option value="">All cities</option>
                @foreach ($cities as $city)
                    <option value="{{ $city->id }}" @selected(request('city_id') == $city->id)>{{ $city->name }} ({{ $city->state->name }})</option>
                @endforeach
            </select>
        </form>
        <a href="{{ route('districts.create') }}" class="rounded-md bg-[var(--brand-primary)] px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:opacity-90 hover:shadow-md">
            + Add district/area
        </a>
    </div>

    <div class="overflow-x-auto rounded-xl border border-line bg-surface-0 shadow-sm">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="border-b border-line text-xs uppercase tracking-wide text-ink-500">
                    <th class="px-5 py-3 font-medium">Name</th>
                    <th class="px-5 py-3 font-medium">City</th>
                    <th class="px-5 py-3 font-medium">State/Province</th>
                    <th class="px-5 py-3 font-medium">Code</th>
                    <th class="px-5 py-3 font-medium">Postal code</th>
                    <th class="px-5 py-3 font-medium">Onforwarding</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($districts as $district)
                    <tr class="border-b border-line last:border-0 odd:bg-surface-0 even:bg-surface-50/50 hover:bg-[var(--brand-primary)]/5 transition-colors">
                        <td class="px-5 py-3 font-medium text-ink-900">{{ $district->name }}</td>
                        <td class="px-5 py-3 text-ink-500">{{ $district->city->name }}</td>
                        <td class="px-5 py-3 text-ink-500">{{ $district->city->state->name }}</td>
                        <td class="px-5 py-3 font-mono text-ink-500">{{ $district->code }}</td>
                        <td class="px-5 py-3 text-ink-500">{{ $district->postal_code ?? '—' }}</td>
                        <td class="px-5 py-3 text-ink-500">{{ $district->onforwardingClassification?->name ?? '—' }}</td>
                        <td class="px-5 py-3 text-right">
                            <a href="{{ route('districts.edit', $district) }}" class="text-sm font-medium text-[var(--brand-primary)] hover:underline">Edit</a>
                            <form method="POST" action="{{ route('districts.destroy', $district) }}" class="inline" onsubmit="return confirm('Remove this district/area?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="ml-3 text-sm font-medium text-status-exception transition-colors hover:text-status-exception/70">Remove</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-5 py-8 text-center text-sm text-ink-500">No districts/areas configured yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-5">{{ $districts->links() }}</div>

</x-layouts.app>
