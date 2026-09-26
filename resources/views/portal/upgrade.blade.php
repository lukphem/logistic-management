<x-layouts.portal title="Upgrade to Organization">

    <div class="mb-6">
        <p class="text-2xl font-semibold text-ink-900">Upgrade to an Organization Account</p>
        <p class="mt-1 text-sm text-ink-500">Submit your organization's details — an admin reviews every request before it's applied, so this won't change your account immediately.</p>
    </div>

    @if ($errors->any())
        <div class="mb-4 rounded-md bg-status-exception/10 px-3 py-2 text-sm text-status-exception">
            {{ $errors->first() }}
        </div>
    @endif

    @if ($account?->account_type === 'organization')
        <div class="rounded-xl border border-line bg-surface-0 shadow-sm p-5 text-sm text-ink-700">
            This account is already an organization account — nothing further to request.
        </div>
    @elseif ($pendingRequest)
        <div class="rounded-xl border border-line bg-surface-0 shadow-sm p-5">
            <p class="text-sm font-semibold text-ink-900">Request pending review</p>
            <p class="mt-1 text-sm text-ink-500">Submitted {{ $pendingRequest->created_at->format('d M Y') }} for <strong>{{ $pendingRequest->company_name }}</strong>. You'll be able to submit another request if this one is declined.</p>
        </div>
    @else
        <div class="max-w-lg rounded-xl border border-line bg-surface-0 shadow-sm p-5">
            <form method="POST" action="{{ route('portal.upgrade.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink-900">Company name <x-required /></label>
                    <input type="text" name="company_name" required maxlength="255" value="{{ old('company_name') }}" class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink-900">RC number <x-required /></label>
                    <input type="text" name="rc_number" required maxlength="255" value="{{ old('rc_number') }}" class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink-900">Tax ID (TIN) <span class="text-xs font-normal text-ink-500">(optional)</span></label>
                    <input type="text" name="tin" maxlength="255" value="{{ old('tin') }}" class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink-900">Industry <span class="text-xs font-normal text-ink-500">(optional)</span></label>
                    <input type="text" name="industry" maxlength="255" value="{{ old('industry') }}" class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink-900">Contact person name <x-required /></label>
                    <input type="text" name="contact_person_name" required maxlength="255" value="{{ old('contact_person_name') }}" class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink-900">Contact person role <span class="text-xs font-normal text-ink-500">(optional)</span></label>
                    <input type="text" name="contact_person_role" maxlength="255" value="{{ old('contact_person_role') }}" class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
                </div>
                <button type="submit" class="w-full rounded-md bg-[var(--brand-primary)] py-2.5 text-sm font-semibold text-white shadow-sm hover:opacity-90">Submit for review</button>
            </form>
        </div>
    @endif

</x-layouts.portal>
