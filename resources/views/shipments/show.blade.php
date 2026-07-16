<x-layouts.app :title="'Shipment ' . $shipment->tracking_number">

    <div class="mb-6 flex items-start justify-between">
        <div>
            <p class="font-mono text-2xl font-semibold text-ink-900">{{ $shipment->tracking_number }}</p>
            <p class="mt-1 text-sm text-ink-500">
                {{ $shipment->originZone?->name ?? $shipment->origin_address }}
                <span class="mx-1">→</span>
                {{ $shipment->destinationZone?->name ?? $shipment->destination_address }}
            </p>
        </div>
        <x-status-pill :status="$shipment->current_status" class="!text-sm !px-3 !py-1" />
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

        {{-- Left: details --}}
        <div class="space-y-6 lg:col-span-2">

            <div class="rounded-xl border border-line bg-surface-0 shadow-sm p-5">
                <h2 class="mb-4 text-sm font-semibold text-ink-900">Shipment details</h2>
                <dl class="grid grid-cols-2 gap-y-3 text-sm">
                    <dt class="text-ink-500">Service type</dt>
                    <dd class="text-ink-900">{{ ucfirst($shipment->service_type) }}</dd>

                    <dt class="text-ink-500">Weight</dt>
                    <dd class="text-ink-900">{{ $shipment->weight_kg ?? '—' }} kg</dd>

                    <dt class="text-ink-500">Chargeable weight</dt>
                    <dd class="text-ink-900">{{ $shipment->chargeable_weight_kg ?? '—' }} kg</dd>

                    <dt class="text-ink-500">Assigned rider</dt>
                    <dd class="text-ink-900">{{ $shipment->assignedRider?->name ?? 'Unassigned' }}</dd>

                    <dt class="text-ink-500">COD</dt>
                    <dd class="text-ink-900">
                        @if ($shipment->is_cod)
                            {{ number_format($shipment->cod_amount, 2) }}
                            {{ $shipment->cod_remitted_at ? '· remitted' : '· pending remittance' }}
                        @else
                            No
                        @endif
                    </dd>

                    <dt class="text-ink-500">SLA</dt>
                    <dd class="{{ $shipment->sla_breached ? 'text-status-exception font-medium' : 'text-ink-900' }}">
                        {{ $shipment->sla_breached ? 'Breached' : 'On track' }}
                    </dd>
                </dl>
            </div>

            <div class="rounded-xl border border-line bg-surface-0 shadow-sm p-5">
                <h2 class="mb-4 text-sm font-semibold text-ink-900">Billing</h2>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between"><dt class="text-ink-500">Base</dt><dd class="font-mono text-ink-900">{{ number_format($shipment->base_amount, 2) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-ink-500">Surcharges</dt><dd class="font-mono text-ink-900">{{ number_format($shipment->surcharge_amount, 2) }}</dd></div>
                    @if ($shipment->discount_amount > 0)
                        <div class="flex justify-between"><dt class="text-ink-500">Discount</dt><dd class="font-mono text-status-delivered">−{{ number_format($shipment->discount_amount, 2) }}</dd></div>
                    @endif
                    <div class="flex justify-between"><dt class="text-ink-500">Insurance</dt><dd class="font-mono text-ink-900">{{ number_format($shipment->insurance_amount, 2) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-ink-500">VAT</dt><dd class="font-mono text-ink-900">{{ number_format($shipment->vat_amount, 2) }}</dd></div>
                    <div class="flex justify-between border-t border-line pt-2 font-semibold"><dt class="text-ink-900">Total</dt><dd class="font-mono text-ink-900">{{ number_format($shipment->total_amount, 2) }}</dd></div>
                </dl>
            </div>
        </div>

        {{-- Right: scan timeline, styled as a waybill stamp trail --}}
        <div class="rounded-xl border border-line bg-surface-0 shadow-sm p-5">
            <h2 class="mb-5 text-sm font-semibold text-ink-900">Checkpoint trail</h2>

            @forelse ($shipment->scanEvents as $index => $event)
                <div class="relative flex gap-4 pb-7 last:pb-0">
                    @if (!$loop->last)
                        <span class="absolute left-[15px] top-8 h-full w-px border-l border-dashed border-line"></span>
                    @endif

                    <span class="relative z-10 grid h-8 w-8 shrink-0 place-items-center rounded-sm border-2 border-dashed
                                 border-[var(--brand-primary)] bg-surface-0 text-[10px] font-bold uppercase text-[var(--brand-primary)]
                                 {{ $index % 2 === 0 ? '-rotate-3' : 'rotate-3' }}">
                        {{ $index + 1 }}
                    </span>

                    <div class="pt-0.5">
                        <p class="text-sm font-semibold text-ink-900">{{ ucfirst(str_replace('_', ' ', $event->status)) }}</p>
                        <p class="font-mono text-xs text-ink-500">{{ $event->scanned_at->format('d M Y · H:i') }}</p>
                        @if ($event->handler)
                            <p class="text-xs text-ink-500">{{ $event->handler->name }}</p>
                        @endif
                    </div>
                </div>
            @empty
                <p class="text-sm text-ink-500">No scan events recorded yet.</p>
            @endforelse
        </div>
    </div>

</x-layouts.app>
