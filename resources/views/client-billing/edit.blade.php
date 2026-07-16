<x-layouts.app :title="'Billing — ' . $name">

    @if ($errors->any())
        <div class="mb-5 rounded-xl bg-status-exception/10 px-4 py-3 text-sm text-status-exception">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('client-billing.update', [$type, $id]) }}" class="max-w-lg space-y-6">
        @csrf
        @method('PUT')

        <div class="rounded-xl border border-line bg-surface-0 p-5 shadow-sm">
            <h2 class="mb-4 text-sm font-semibold text-ink-900">Billing type</h2>

            <div class="space-y-3">
                <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-line p-3 has-[:checked]:border-[var(--brand-primary)] has-[:checked]:bg-[var(--brand-primary)]/5">
                    <input type="radio" name="billing_type" value="standard" class="mt-1"
                           @checked(old('billing_type', $profile->billing_type ?? 'standard') === 'standard')
                           onchange="document.getElementById('discount-field').style.display='none'">
                    <span>
                        <span class="block text-sm font-medium text-ink-900">Standard</span>
                        <span class="block text-xs text-ink-500">Pays exactly what the active rate card says. No discount.</span>
                    </span>
                </label>

                <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-line p-3 has-[:checked]:border-[var(--brand-primary)] has-[:checked]:bg-[var(--brand-primary)]/5">
                    <input type="radio" name="billing_type" value="special" class="mt-1"
                           @checked(old('billing_type', $profile->billing_type ?? 'standard') === 'special')
                           onchange="document.getElementById('discount-field').style.display=''">
                    <span>
                        <span class="block text-sm font-medium text-ink-900">Special</span>
                        <span class="block text-xs text-ink-500">Gets a percentage off the standard rate, recalculated at every quote.</span>
                    </span>
                </label>
            </div>

            <div id="discount-field" class="mt-4" style="{{ old('billing_type', $profile->billing_type ?? 'standard') === 'special' ? '' : 'display:none' }}">
                <label class="mb-1 block text-sm font-medium text-ink-900">Discount percentage</label>
                <div class="flex items-center gap-2">
                    <input type="number" step="0.1" min="0" max="100" name="discount_percentage"
                           value="{{ old('discount_percentage', $profile->discount_percentage ?? '') }}"
                           class="w-32 rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
                    <span class="text-sm text-ink-500">% off freight + surcharges (VAT and insurance are never discounted)</span>
                </div>
            </div>

            <div class="mt-4">
                <label class="mb-1 block text-sm font-medium text-ink-900">Notes</label>
                <input type="text" name="notes" value="{{ old('notes', $profile->notes ?? '') }}" placeholder="e.g. Agreed by Ops Manager, contract dated..."
                       class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('client-billing.index') }}" class="rounded-md px-4 py-2.5 text-sm font-medium text-ink-500 hover:text-ink-900">Cancel</a>
            <button type="submit" class="rounded-md bg-[var(--brand-primary)] px-5 py-2.5 text-sm font-semibold text-white hover:opacity-90">
                Save
            </button>
        </div>
    </form>

</x-layouts.app>
