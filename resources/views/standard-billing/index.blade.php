<x-layouts.app :title="'Standard Billing'">

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

    <div class="mb-6 flex gap-2 rounded-lg bg-surface-50 p-1">
        <button type="button" id="model-btn-standard" onclick="showBillingModelTab('standard')"
                class="flex-1 rounded-md bg-surface-0 px-3 py-2 text-sm font-semibold text-ink-900 shadow-sm">
            Standard Billing
        </button>
        <button type="button" id="model-btn-origin-destination" onclick="showBillingModelTab('origin-destination')"
                class="flex-1 rounded-md px-3 py-2 text-sm font-medium text-ink-500 hover:text-ink-900">
            Origin to Destination
        </button>
    </div>

    <div id="model-standard">
        <p class="mb-4 text-sm text-ink-500">
            Weight-band tariffs, priced per zone. A shipment matches a tariff by service type and weight, then the
            resolved zone (Billing → Zone Mapping) picks which price applies. Domestic and International are configured
            separately — Cross-Trade (Third-Country Shipping) service types are International too, so their tariffs
            appear under that same tab, alongside regular Nigeria-anchored ones.
        </p>

        <x-csv-actions :export-route="route('standard-billing.export')" :import-route="route('standard-billing.import')" label="Standard Billing" />
        <p class="mb-5 -mt-3 text-xs text-ink-500">
            Covers every tariff and zone price together (8 columns, including service type and weight range) — one row per
            zone, sharing service type/weight range across rows. Import creates tariffs and prices that don't exist yet,
            and updates ones that do. To bulk-add zones to just one already-existing tariff instead, use the smaller
            Export/Import on that tariff's own edit page.
        </p>

        <div class="mb-5 flex gap-2 border-b border-line">
            <button type="button" id="tab-btn-domestic" onclick="showStandardBillingTab('domestic')"
                    class="border-b-2 border-[var(--brand-primary)] px-1 pb-3 text-sm font-semibold text-ink-900">
                Domestic
            </button>
            <button type="button" id="tab-btn-international" onclick="showStandardBillingTab('international')"
                    class="border-b-2 border-transparent px-1 pb-3 text-sm font-medium text-ink-500 hover:text-ink-900">
                International
            </button>
        </div>

        <div id="tab-domestic">
            @include('standard-billing._tariff-table', ['tariffs' => $domesticTariffs, 'addTariffRoute' => route('standard-billing.create', ['route_type' => 'domestic'])])
        </div>

        <div id="tab-international" style="display:none">
            @include('standard-billing._tariff-table', ['tariffs' => $internationalTariffs, 'addTariffRoute' => route('standard-billing.create', ['route_type' => 'international'])])
        </div>
    </div>

    <div id="model-origin-destination" style="display:none">
        @include('origin-destination-billing._route-rate-table')
    </div>

    <script>
        function showBillingModelTab(model) {
            ['standard', 'origin-destination'].forEach(function (name) {
                document.getElementById('model-' + name).style.display = model === name ? '' : 'none';

                const btn = document.getElementById('model-btn-' + name);
                btn.classList.toggle('bg-surface-0', model === name);
                btn.classList.toggle('shadow-sm', model === name);
                btn.classList.toggle('text-ink-900', model === name);
                btn.classList.toggle('font-semibold', model === name);
                btn.classList.toggle('text-ink-500', model !== name);
                btn.classList.toggle('font-medium', model !== name);
            });
        }

        function showStandardBillingTab(tab) {
            ['domestic', 'international'].forEach(function (name) {
                document.getElementById('tab-' + name).style.display = tab === name ? '' : 'none';

                const btn = document.getElementById('tab-btn-' + name);
                btn.classList.toggle('border-[var(--brand-primary)]', tab === name);
                btn.classList.toggle('text-ink-900', tab === name);
                btn.classList.toggle('font-semibold', tab === name);
                btn.classList.toggle('border-transparent', tab !== name);
                btn.classList.toggle('text-ink-500', tab !== name);
            });
        }

        // Restores state a redirect (e.g. after saving a tariff or a
        // route rate) asked to land on: ?model=origin-destination for
        // the outer tab, ?tab=international for Standard Billing's own
        // inner tab.
        (function () {
            const params = new URLSearchParams(window.location.search);
            if (params.get('model') === 'origin-destination') {
                showBillingModelTab('origin-destination');
            }
            if (params.get('tab') === 'international') {
                showStandardBillingTab('international');
            }
        })();
    </script>

</x-layouts.app>
