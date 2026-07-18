<x-layouts.app :title="'Rate Checker'">

    <p class="mb-5 text-sm text-ink-500">
        Check what a shipment would cost without creating one — uses the exact same pricing logic as a real booking,
        so the number here is always what a customer would actually be charged.
    </p>

    <form method="GET" class="max-w-2xl space-y-4 rounded-xl border border-line bg-surface-0 shadow-sm p-5">
        <div>
            <label class="mb-1 block text-sm font-medium text-ink-900">Service type <x-required /></label>
            <select name="service_type_id" class="w-full max-w-sm rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                <option value="">Select a service type</option>
                @foreach ($serviceTypes as $serviceType)
                    <option value="{{ $serviceType->id }}" @selected(request('service_type_id') == $serviceType->id)>{{ $serviceType->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-ink-900">Route type</label>
            <div class="flex gap-3">
                <label class="flex flex-1 cursor-pointer items-center gap-2 rounded-lg border border-line p-3 text-sm text-ink-900 has-[:checked]:border-[var(--brand-primary)] has-[:checked]:bg-[var(--brand-primary)]/5">
                    <input type="radio" name="route_type" value="domestic" id="route-domestic"
                           @checked(request('route_type', 'domestic') === 'domestic')
                           onchange="document.getElementById('domestic-fields').style.display=''; document.getElementById('international-fields').style.display='none';">
                    Domestic
                </label>
                <label class="flex flex-1 cursor-pointer items-center gap-2 rounded-lg border border-line p-3 text-sm text-ink-900 has-[:checked]:border-[var(--brand-primary)] has-[:checked]:bg-[var(--brand-primary)]/5">
                    <input type="radio" name="route_type" value="international" id="route-international"
                           @checked(request('route_type') === 'international')
                           onchange="document.getElementById('domestic-fields').style.display='none'; document.getElementById('international-fields').style.display='';">
                    International
                </label>
            </div>
        </div>

        <div id="domestic-fields" class="grid grid-cols-1 gap-4 sm:grid-cols-2" style="{{ request('route_type') === 'international' ? 'display:none' : '' }}">
            <div>
                <label class="mb-1 block text-sm font-medium text-ink-900">Origin city</label>
                <select name="origin_city_id" class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                    <option value="">Select a city</option>
                    @foreach ($cities as $city)
                        <option value="{{ $city->id }}" @selected(request('origin_city_id') == $city->id)>{{ $city->name }} ({{ $city->state->name }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-ink-900">Destination city</label>
                <select name="destination_city_id" class="w-full rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                    <option value="">Select a city</option>
                    @foreach ($cities as $city)
                        <option value="{{ $city->id }}" @selected(request('destination_city_id') == $city->id)>{{ $city->name }} ({{ $city->state->name }})</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div id="international-fields" style="{{ request('route_type') !== 'international' ? 'display:none' : '' }}">
            <label class="mb-1 block text-sm font-medium text-ink-900">Destination country</label>
            <select name="destination_country_id" class="w-full max-w-sm rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)]">
                <option value="">Select a country</option>
                @foreach ($countries as $country)
                    <option value="{{ $country->id }}" @selected(request('destination_country_id') == $country->id)>{{ $country->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-ink-900">Weight (kg) <x-required /></label>
            <input type="number" step="0.01" min="0" name="weight_kg" value="{{ request('weight_kg') }}"
                   class="w-full max-w-[10rem] rounded-md border border-line px-3 py-2 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/20">
        </div>

        <div class="flex justify-end pt-2">
            <button type="submit" class="rounded-md bg-[var(--brand-primary)] px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 hover:shadow-md">
                Check rate
            </button>
        </div>
    </form>

    @if ($error)
        <div class="mt-6 max-w-2xl rounded-xl border border-status-exception/30 bg-status-exception/5 p-5">
            <p class="text-sm font-medium text-status-exception">Can't be rated</p>
            <p class="mt-1 text-sm text-ink-900">{{ $error }}</p>
        </div>
    @elseif ($result)
        <div class="mt-6 max-w-2xl rounded-xl border border-line bg-surface-0 p-5 shadow-sm">
            <p class="mb-4 text-sm font-semibold text-ink-900">Quote</p>
            <dl class="grid grid-cols-2 gap-4 text-sm sm:grid-cols-4">
                <div>
                    <dt class="text-ink-500">Price</dt>
                    <dd class="font-mono text-lg font-semibold text-ink-900">{{ number_format($result['base_amount'], 2) }}</dd>
                </div>
                <div>
                    <dt class="text-ink-500">Transit days</dt>
                    <dd class="text-ink-900">{{ $result['transit_days'] ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-ink-500">Zone</dt>
                    <dd class="text-ink-900">{{ $result['zone_name'] ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-ink-500">Route type</dt>
                    <dd class="text-ink-900">{{ ucfirst($result['shipping_type']) }}</dd>
                </div>
            </dl>
            <p class="mt-4 text-xs text-ink-500">Base freight only — doesn't include VAT, insurance, onforwarding, or any client discount.</p>
        </div>
    @endif

</x-layouts.app>
