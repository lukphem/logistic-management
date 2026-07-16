<x-layouts.app :title="'Rate Cards'">

    @if (session('status'))
        <div class="mb-5 rounded-xl bg-status-delivered/10 px-4 py-3 text-sm font-medium text-status-delivered">
            {{ session('status') }}
        </div>
    @endif

    <div class="mb-5 flex items-center justify-between">
        <p class="text-sm text-ink-500">The standard rate every client is charged unless placed on a special (discounted) billing profile.</p>
        <a href="{{ route('rate-cards.create') }}" class="rounded-md bg-[var(--brand-primary)] px-4 py-2 text-sm font-medium text-white hover:opacity-90">
            + Add rate card
        </a>
    </div>

    <div class="overflow-hidden rounded-xl border border-line bg-surface-0 shadow-sm">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="border-b border-line text-xs uppercase tracking-wide text-ink-500">
                    <th class="px-5 py-3 font-medium">Name</th>
                    <th class="px-5 py-3 font-medium">Service type</th>
                    <th class="px-5 py-3 font-medium">Billing model</th>
                    <th class="px-5 py-3 font-medium">Priority</th>
                    <th class="px-5 py-3 font-medium">Status</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rateCards as $rateCard)
                    <tr class="border-b border-line last:border-0 hover:bg-surface-50 transition-colors">
                        <td class="px-5 py-3 font-medium text-ink-900">{{ $rateCard->name }}</td>
                        <td class="px-5 py-3 text-ink-500">{{ ucfirst($rateCard->service_type) }}</td>
                        <td class="px-5 py-3 text-ink-500">{{ $billingModels[$rateCard->billing_model] ?? $rateCard->billing_model }}</td>
                        <td class="px-5 py-3 text-ink-500">{{ $rateCard->priority }}</td>
                        <td class="px-5 py-3">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $rateCard->is_active ? 'bg-status-delivered/10 text-status-delivered' : 'bg-ink-500/10 text-ink-500' }}">
                                {{ $rateCard->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-right">
                            <a href="{{ route('rate-cards.edit', $rateCard) }}" class="text-sm font-medium text-[var(--brand-primary)] hover:underline">Edit</a>
                            <form method="POST" action="{{ route('rate-cards.destroy', $rateCard) }}" class="inline" onsubmit="return confirm('Remove this rate card?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="ml-3 text-sm font-medium text-status-exception hover:underline">Remove</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-5 py-8 text-center text-sm text-ink-500">No rate cards configured yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-5">{{ $rateCards->links() }}</div>

</x-layouts.app>
