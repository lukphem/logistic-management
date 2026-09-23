<x-layouts.app :title="'Shipment ' . $shipment->tracking_number">

    @if (session('status'))
        <div class="mb-4 rounded-xl border border-status-delivered/30 bg-status-delivered/5 px-4 py-3 text-sm text-status-delivered">
            {{ session('status') }}
        </div>
    @endif
    @if ($errors->any())
        <div class="mb-4 rounded-xl border border-status-exception/30 bg-status-exception/5 px-4 py-3 text-sm text-status-exception">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="mb-6 flex items-start justify-between">
        <div>
            <p class="font-mono text-2xl font-semibold text-ink-900">
                {{ $shipment->tracking_number }}
                @if ($shipment->is_test)
                    <span class="ml-2 inline-flex items-center rounded-full bg-status-exception/10 px-2 py-0.5 text-xs font-medium text-status-exception align-middle">Test</span>
                @endif
            </p>
            @if ($shipment->originHub || $shipment->destinationHub)
                <p class="text-xs text-ink-500">
                    @if ($shipment->originHub && $shipment->destinationHub)
                        {{ $shipment->originHub->name }} ({{ $shipment->originHub->code }}) → {{ $shipment->destinationHub->name }} ({{ $shipment->destinationHub->code }})
                    @elseif ($shipment->originHub)
                        Originated at {{ $shipment->originHub->name }} ({{ $shipment->originHub->code }})
                    @endif
                </p>
            @endif
            <p class="mt-1 text-sm text-ink-500">
                {{ $shipment->originCity?->name ?? $shipment->originZone?->name ?? $shipment->origin_address }}
                <span class="mx-1">→</span>
                {{ $shipment->destinationCity?->name ?? $shipment->destinationZone?->name ?? $shipment->destination_address }}
            </p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('shipments.label', $shipment) }}" target="_blank" class="rounded-md border border-line px-3 py-1.5 text-sm font-medium text-ink-700 transition hover:bg-surface-50">Print Label</a>
            <a href="{{ route('shipments.waybill', $shipment) }}" target="_blank" class="rounded-md border border-line px-3 py-1.5 text-sm font-medium text-ink-700 transition hover:bg-surface-50">Print Waybill</a>
            @if (\App\Models\Setting::current()->paystack_enabled)
                @if ($shipment->payment_status === 'paid')
                    <span class="inline-flex items-center rounded-full bg-status-delivered/10 px-3 py-1 text-sm font-medium text-status-delivered">Paid</span>
                @else
                    <a href="{{ route('payments.pay', $shipment) }}" class="rounded-md bg-[var(--brand-primary)] px-3 py-1.5 text-sm font-medium text-white transition hover:opacity-90">Pay with Paystack</a>
                    @if ($shipment->payment_reference)
                        <form method="POST" action="{{ route('payments.check-status') }}" class="inline">
                            @csrf
                            <input type="hidden" name="reference" value="{{ $shipment->payment_reference }}">
                            <button type="submit" class="rounded-md border border-line px-3 py-1.5 text-sm font-medium text-ink-700 transition hover:bg-surface-50">Check status</button>
                        </form>
                    @endif
                @endif
            @endif
            @can('shipments:update')
                @if (! in_array($shipment->current_status, ['delivered', 'returned'], true))
                    <a href="{{ route('shipments.edit', $shipment) }}" class="rounded-md border border-line px-3 py-1.5 text-sm font-medium text-ink-700 transition hover:bg-surface-50">Edit</a>
                @endif
            @endcan
            @can('shipments:delete')
                @if ($shipment->current_status === 'booked')
                    @if ($shipment->hasCollectedPayment())
                        <span class="rounded-md border border-line px-3 py-1.5 text-sm font-medium text-ink-400" title="Already paid for — refund through finance first, then cancel.">Cancel Shipment</span>
                    @else
                        <form method="POST" action="{{ route('shipments.destroy', $shipment) }}" class="inline" onsubmit="return confirm('Cancel {{ $shipment->tracking_number }}? This only works before it\'s been picked up or dropped off.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="rounded-md border border-status-exception/30 px-3 py-1.5 text-sm font-medium text-status-exception transition hover:bg-status-exception/5">Cancel Shipment</button>
                        </form>
                    @endif
                @endif
            @endcan
            <x-status-pill :status="$shipment->current_status" class="!text-sm !px-3 !py-1" />
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

        {{-- Left: details --}}
        <div class="space-y-6 lg:col-span-2">

            <div class="rounded-xl border border-line bg-surface-0 shadow-sm p-5">
                <h2 class="mb-4 text-sm font-semibold text-ink-900">Client &amp; account</h2>
                <dl class="grid grid-cols-2 gap-y-3 text-sm">
                    <dt class="text-ink-500">Client</dt>
                    <dd class="text-ink-900">{{ $shipment->clientUser?->name ?? 'Walk-in customer' }}</dd>

                    <dt class="text-ink-500">Account</dt>
                    <dd class="text-ink-900">{{ $shipment->clientAccount?->account_name ?? '—' }} {{ $shipment->clientAccount?->account_number ? '(' . $shipment->clientAccount->account_number . ')' : '' }}</dd>

                    <dt class="text-ink-500">Payment type</dt>
                    <dd class="text-ink-900">{{ $shipment->clientAccount?->payment_type === 'credit' ? 'Credit' : ($shipment->clientAccount ? 'Cash' : '—') }}</dd>

                    <dt class="text-ink-500">Booked via</dt>
                    <dd class="text-ink-900">{{ $shipment->apiClient ? $shipment->apiClient->name . ' (' . ucfirst($shipment->apiClient->mode) . ' API)' : 'Web' }}</dd>
                </dl>
            </div>

            <div class="rounded-xl border border-line bg-surface-0 shadow-sm p-5">
                <h2 class="mb-4 text-sm font-semibold text-ink-900">Sender</h2>
                <dl class="grid grid-cols-2 gap-y-3 text-sm">
                    <dt class="text-ink-500">Name</dt>
                    <dd class="text-ink-900">{{ $shipment->sender_name }}</dd>

                    <dt class="text-ink-500">Phone</dt>
                    <dd class="text-ink-900 font-mono">{{ $shipment->sender_phone }}</dd>

                    <dt class="text-ink-500">Email</dt>
                    <dd class="text-ink-900">{{ $shipment->sender_email ?? '—' }}</dd>

                    <dt class="text-ink-500">Address</dt>
                    <dd class="text-ink-900">{{ $shipment->origin_address }}</dd>
                </dl>
            </div>

            <div class="rounded-xl border border-line bg-surface-0 shadow-sm p-5">
                <h2 class="mb-4 text-sm font-semibold text-ink-900">Receiver</h2>
                <dl class="grid grid-cols-2 gap-y-3 text-sm">
                    <dt class="text-ink-500">Name</dt>
                    <dd class="text-ink-900">{{ $shipment->receiver_name }}</dd>

                    <dt class="text-ink-500">Phone</dt>
                    <dd class="text-ink-900 font-mono">{{ $shipment->receiver_phone }}</dd>

                    @if ($shipment->receiver_alternate_phone)
                        <dt class="text-ink-500">Alternate phone</dt>
                        <dd class="text-ink-900 font-mono">{{ $shipment->receiver_alternate_phone }}</dd>
                    @endif

                    <dt class="text-ink-500">Email</dt>
                    <dd class="text-ink-900">{{ $shipment->receiver_email ?? '—' }}</dd>

                    <dt class="text-ink-500">Address</dt>
                    <dd class="text-ink-900">{{ $shipment->destination_address }}</dd>
                </dl>
            </div>

            <div class="rounded-xl border border-line bg-surface-0 shadow-sm p-5">
                <h2 class="mb-4 text-sm font-semibold text-ink-900">Package</h2>
                <dl class="grid grid-cols-2 gap-y-3 text-sm">
                    <dt class="text-ink-500">Description</dt>
                    <dd class="text-ink-900">{{ $shipment->package_description }}</dd>

                    @if ($shipment->special_instructions)
                        <dt class="text-ink-500">Special instructions</dt>
                        <dd class="text-ink-900">{{ $shipment->special_instructions }}</dd>
                    @endif

                    <dt class="text-ink-500">Weight</dt>
                    <dd class="text-ink-900">{{ $shipment->weight_kg ?? '—' }} kg</dd>

                    <dt class="text-ink-500">Chargeable weight</dt>
                    <dd class="text-ink-900">{{ $shipment->chargeable_weight_kg ?? '—' }} kg</dd>

                    @if ($shipment->length_cm || $shipment->width_cm || $shipment->height_cm)
                        <dt class="text-ink-500">Dimensions (L×W×H)</dt>
                        <dd class="text-ink-900">{{ $shipment->length_cm ?? '—' }} × {{ $shipment->width_cm ?? '—' }} × {{ $shipment->height_cm ?? '—' }} cm</dd>
                    @endif

                    <dt class="text-ink-500">Quantity</dt>
                    <dd class="text-ink-900">{{ $shipment->quantity ?? 1 }}</dd>

                    <dt class="text-ink-500">Packaging</dt>
                    <dd class="text-ink-900">{{ $shipment->carton_size ? ucfirst($shipment->carton_size) : '—' }}</dd>

                    <dt class="text-ink-500">Service type</dt>
                    <dd class="text-ink-900">{{ $shipment->serviceType->name ?? '—' }}</dd>

                    <dt class="text-ink-500">Assigned rider</dt>
                    <dd class="text-ink-900">{{ $shipment->assignedRider?->name ?? 'Unassigned' }}</dd>

                    <dt class="text-ink-500">Current location</dt>
                    <dd class="text-ink-900">
                        @if ($shipment->currentOutlet)
                            {{ $shipment->currentOutlet->name }} <span class="text-xs text-ink-500">(outlet)</span>
                        @elseif ($shipment->currentHub)
                            {{ $shipment->currentHub->name }} <span class="text-xs text-ink-500">(hub)</span>
                        @else
                            —
                        @endif
                    </dd>

                    <dt class="text-ink-500">COD</dt>
                    <dd class="text-ink-900">
                        @if ($shipment->is_cod)
                            {{ number_format($shipment->cod_amount, 2) }}
                            @if ($shipment->payment_status === 'paid')
                                · settled
                            @elseif ($shipment->cash_collected_at)
                                · collected, pending settlement
                            @else
                                · pending collection
                            @endif
                        @else
                            No
                        @endif
                    </dd>

                    @if ($shipment->collection_method)
                        <dt class="text-ink-500">Collection method</dt>
                        <dd class="text-ink-900">
                            {{ $shipment->collection_method === 'cash' ? 'Cash' : 'Paystack' }}
                            @if ($shipment->collection_method === 'cash' && $shipment->cash_collected_at)
                                <span class="text-xs text-ink-500">(collected {{ $shipment->cash_collected_at->format('d M Y') }}{{ $shipment->cashSettlement ? ', settled ' . $shipment->cashSettlement->paid_at?->format('d M Y') : '' }})</span>
                            @endif
                        </dd>
                    @endif

                    <dt class="text-ink-500">Pickup requested</dt>
                    <dd class="text-ink-900">
                        @if ($shipment->is_pickup_requested)
                            Yes {{ $shipment->pickup_amount > 0 ? '(' . number_format($shipment->pickup_amount, 2) . ')' : '(no charge)' }}
                        @else
                            No
                        @endif
                    </dd>

                    <dt class="text-ink-500">SLA</dt>
                    <dd class="{{ $shipment->sla_breached ? 'text-status-exception font-medium' : 'text-ink-900' }}">
                        {{ $shipment->sla_breached ? 'Breached' : 'On track' }}
                    </dd>

                    <dt class="text-ink-500">Booked</dt>
                    <dd class="text-ink-900">{{ $shipment->created_at->format('d M Y · H:i') }}</dd>

                    @if ($shipment->promised_delivery_at)
                        <dt class="text-ink-500">Promised delivery</dt>
                        <dd class="text-ink-900">{{ $shipment->promised_delivery_at->format('d M Y') }}</dd>
                    @endif
                </dl>
            </div>

            <div class="rounded-xl border border-line bg-surface-0 shadow-sm p-5">
                <h2 class="mb-4 text-sm font-semibold text-ink-900">Billing</h2>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between"><dt class="text-ink-500">Base</dt><dd class="font-mono text-ink-900">{{ number_format($shipment->base_amount, 2) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-ink-500">Surcharges</dt><dd class="font-mono text-ink-900">{{ number_format($shipment->surcharge_amount, 2) }}</dd></div>
                    @if ($shipment->onforwarding_amount > 0)
                        <div class="flex justify-between"><dt class="text-ink-500">Onforwarding</dt><dd class="font-mono text-ink-900">{{ number_format($shipment->onforwarding_amount, 2) }}</dd></div>
                    @endif
                    @if ($shipment->pickup_amount > 0)
                        <div class="flex justify-between"><dt class="text-ink-500">Pickup fee</dt><dd class="font-mono text-ink-900">{{ number_format($shipment->pickup_amount, 2) }}</dd></div>
                    @endif
                    @if ($shipment->discount_amount > 0)
                        <div class="flex justify-between"><dt class="text-ink-500">Discount</dt><dd class="font-mono text-status-delivered">−{{ number_format($shipment->discount_amount, 2) }}</dd></div>
                    @endif
                    <div class="flex justify-between"><dt class="text-ink-500">Insurance</dt><dd class="font-mono text-ink-900">{{ number_format($shipment->insurance_amount, 2) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-ink-500">VAT</dt><dd class="font-mono text-ink-900">{{ number_format($shipment->vat_amount, 2) }}</dd></div>
                    <div class="flex justify-between border-t border-line pt-2 font-semibold"><dt class="text-ink-900">Total</dt><dd class="font-mono text-ink-900">{{ number_format($shipment->total_amount, 2) }}</dd></div>
                    <div class="flex justify-between pt-1">
                        <dt class="text-ink-500">Payment status</dt>
                        <dd class="{{ $shipment->payment_status === 'paid' ? 'text-status-delivered' : ($shipment->payment_status === 'failed' ? 'text-status-exception' : 'text-ink-500') }} font-medium">
                            {{ ucfirst($shipment->payment_status) }}
                            @if ($shipment->paid_at)
                                <span class="font-normal text-ink-500">({{ $shipment->paid_at->format('d M Y') }})</span>
                            @endif
                        </dd>
                    </div>
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
                        @if ($event->outlet)
                            <p class="text-xs text-ink-500">{{ $event->outlet->name }}</p>
                        @endif
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
