<x-layouts.app :title="'Onforwarding Classifications'">

    @if (session('status'))
        <div class="mb-5 rounded-xl bg-status-delivered/10 px-4 py-3 text-sm font-medium text-status-delivered">
            {{ session('status') }}
        </div>
    @endif

    <p class="mb-4 text-sm text-ink-500">
        Extra-charge tiers for cities/districts outside the direct network that need handing off to complete delivery.
        Assign a classification to a city or district under Setups → Location; the surcharge applies automatically at
        booking, for whichever side of the shipment (origin or destination) it matches.
    </p>

    <div class="mb-5 flex items-center justify-between">
        <span></span>
        <a href="{{ route('onforwarding-classifications.create') }}" class="rounded-md bg-[var(--brand-primary)] px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:opacity-90 hover:shadow-md">
            + Add classification
        </a>
    </div>

    <div class="overflow-hidden rounded-xl border border-line bg-surface-0 shadow-sm">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="border-b border-line text-xs uppercase tracking-wide text-ink-500">
                    <th class="px-5 py-3 font-medium">Name</th>
                    <th class="px-5 py-3 font-medium">Surcharge</th>
                    <th class="px-5 py-3 font-medium">Default</th>
                    <th class="px-5 py-3 font-medium">Cities</th>
                    <th class="px-5 py-3 font-medium">Districts</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($classifications as $classification)
                    <tr class="border-b border-line last:border-0 odd:bg-surface-0 even:bg-surface-50/50 hover:bg-[var(--brand-primary)]/5 transition-colors">
                        <td class="px-5 py-3 font-medium text-ink-900">{{ $classification->name }}</td>
                        <td class="px-5 py-3 font-mono text-ink-900">{{ number_format($classification->surcharge_amount, 2) }}</td>
                        <td class="px-5 py-3">
                            @if ($classification->is_default)
                                <span class="inline-flex items-center rounded-full bg-status-delivered/10 px-2.5 py-0.5 text-xs font-medium text-status-delivered">Default</span>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-ink-500">{{ $classification->cities_count }}</td>
                        <td class="px-5 py-3 text-ink-500">{{ $classification->districts_count }}</td>
                        <td class="px-5 py-3 text-right">
                            <a href="{{ route('onforwarding-classifications.edit', $classification) }}" class="text-sm font-medium text-[var(--brand-primary)] hover:underline">Edit</a>
                            <form method="POST" action="{{ route('onforwarding-classifications.destroy', $classification) }}" class="inline" onsubmit="return confirm('Remove this classification? Cities/districts using it will simply become unclassified.')">
                                @csrf @method('DELETE')
                                <button type="submit" class="ml-3 text-sm font-medium text-status-exception transition-colors hover:text-status-exception/70">Remove</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-5 py-8 text-center text-sm text-ink-500">No classifications configured yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-5">{{ $classifications->links() }}</div>

</x-layouts.app>
