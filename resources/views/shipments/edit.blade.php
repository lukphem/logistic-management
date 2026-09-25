<x-layouts.app :title="'Edit ' . $shipment->tracking_number">

    <div class="mb-6">
        <p class="font-mono text-2xl font-semibold text-ink-900">{{ $shipment->tracking_number }}</p>
        <p class="mt-1 text-sm text-ink-500">Editing sender/receiver details, addresses, and package info only — weight, dimensions, and service type can't be changed here, since those are exactly what the shipment's frozen price was calculated from. A shipment that needs re-pricing should be cancelled and rebooked instead.</p>
    </div>

    @if ($errors->any())
        <div class="mb-4 rounded-xl border border-status-exception/30 bg-status-exception/5 p-4 text-sm text-status-exception">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('shipments.update', $shipment) }}" class="max-w-2xl space-y-6 rounded-xl border border-line bg-surface-0 shadow-sm p-5">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="space-y-3 rounded-lg border border-line p-4">
                <p class="text-sm font-semibold text-ink-900">Sender</p>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink-900">Full name <x-required /></label>
                    <input type="text" name="sender_name" value="{{ old('sender_name', $shipment->sender_name) }}" required maxlength="255"
                           pattern="[A-Za-z\s\-'.]+" title="Letters, spaces, hyphens, and apostrophes only"
                           class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink-900">Phone <x-required /></label>
                    <input type="tel" name="sender_phone" value="{{ old('sender_phone', $shipment->sender_phone) }}" required maxlength="20" inputmode="tel"
                           pattern="\+?[0-9\s\-()]{7,20}" title="A valid phone number, 7-20 characters"
                           class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink-900">Email <span class="text-xs font-normal text-ink-500">(optional)</span></label>
                    <input type="email" name="sender_email" value="{{ old('sender_email', $shipment->sender_email) }}" maxlength="255"
                           class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink-900">Origin address <x-required /></label>
                    <textarea name="origin_address" rows="2" maxlength="2000" required
                              class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">{{ old('origin_address', $shipment->origin_address) }}</textarea>
                </div>
            </div>
            <div class="space-y-3 rounded-lg border border-line p-4">
                <p class="text-sm font-semibold text-ink-900">Receiver</p>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink-900">Full name <x-required /></label>
                    <input type="text" name="receiver_name" value="{{ old('receiver_name', $shipment->receiver_name) }}" required maxlength="255"
                           pattern="[A-Za-z\s\-'.]+" title="Letters, spaces, hyphens, and apostrophes only"
                           class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
                </div>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-ink-900">Phone <x-required /></label>
                        <input type="tel" name="receiver_phone" value="{{ old('receiver_phone', $shipment->receiver_phone) }}" required maxlength="20" inputmode="tel"
                               pattern="\+?[0-9\s\-()]{7,20}" title="A valid phone number, 7-20 characters"
                               class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-ink-900">Alternate phone <span class="text-xs font-normal text-ink-500">(optional)</span></label>
                        <input type="tel" name="receiver_alternate_phone" value="{{ old('receiver_alternate_phone', $shipment->receiver_alternate_phone) }}" maxlength="20" inputmode="tel"
                               pattern="\+?[0-9\s\-()]{7,20}" title="A valid phone number, 7-20 characters"
                               class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
                    </div>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink-900">Email <span class="text-xs font-normal text-ink-500">(optional)</span></label>
                    <input type="email" name="receiver_email" value="{{ old('receiver_email', $shipment->receiver_email) }}" maxlength="255"
                           class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink-900">Destination address <x-required /></label>
                    <textarea name="destination_address" rows="2" maxlength="2000" required
                              class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">{{ old('destination_address', $shipment->destination_address) }}</textarea>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-medium text-ink-900">Package description <x-required /></label>
                <textarea name="package_description" rows="2" maxlength="225" required
                          class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">{{ old('package_description', $shipment->package_description) }}</textarea>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-ink-900">Special instructions <span class="text-xs font-normal text-ink-500">(optional)</span></label>
                <textarea name="special_instructions" rows="2" maxlength="2000"
                          class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">{{ old('special_instructions', $shipment->special_instructions) }}</textarea>
            </div>
        </div>

        <div class="flex flex-wrap items-end gap-3">
            <div>
                <label class="mb-1 block text-xs font-medium text-ink-900">Quantity (pieces) <x-required /></label>
                <input type="number" step="1" min="1" name="quantity" value="{{ old('quantity', $shipment->quantity ?? 1) }}" required
                       class="w-24 rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-ink-900">Packaging <span class="text-xs font-normal text-ink-500">(optional)</span></label>
                <select name="carton_size" class="w-36 rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                    <option value="">Not specified</option>
                    <option value="small" @selected(old('carton_size', $shipment->carton_size) === 'small')>Small</option>
                    <option value="medium" @selected(old('carton_size', $shipment->carton_size) === 'medium')>Medium</option>
                    <option value="large" @selected(old('carton_size', $shipment->carton_size) === 'large')>Large</option>
                </select>
            </div>
        </div>

        <div class="flex justify-end gap-3 border-t border-line pt-4">
            <a href="{{ route('shipments.show', $shipment) }}" class="rounded-md border border-line px-4 py-2 text-sm font-medium text-ink-700 transition hover:bg-surface-50">Cancel</a>
            <button type="submit" class="rounded-md bg-[var(--brand-primary)] px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 hover:shadow-md">Save changes</button>
        </div>
    </form>

</x-layouts.app>
