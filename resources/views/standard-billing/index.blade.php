<x-layouts.app :title="'Standard Billing'">

    @if (session('status'))
        <div class="mb-5 rounded-xl bg-status-delivered/10 px-4 py-3 text-sm font-medium text-status-delivered">
            {{ session('status') }}
        </div>
    @endif

    <p class="mb-4 text-sm text-ink-500">
        Weight-band tariffs, priced per zone. A shipment matches a tariff by service type and weight, then the
        resolved zone (Billing → Zone Mapping) picks which price applies. Domestic and International are configured
        separately, since every service type is restricted to exactly one or the other.
    </p>

    <x-csv-actions :export-route="route('standard-billing.export')" :import-route="route('standard-billing.import')" label="Standard Billing" />
    <p class="mb-5 -mt-3 text-xs text-ink-500">
        Covers every tariff and zone price together (8 columns, including service type and weight range) — one row per
        zone, sharing service type/weight range across rows. Import creates tariffs and prices that don't exist yet,
        and updates ones that do. Covers both Domestic and International in one file. To bulk-add zones to just one
        already-existing tariff instead, use the smaller Export/Import on that tariff's own edit page.
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

    <script>
        function showStandardBillingTab(tab) {
            document.getElementById('tab-domestic').style.display = tab === 'domestic' ? '' : 'none';
            document.getElementById('tab-international').style.display = tab === 'international' ? '' : 'none';

            document.getElementById('tab-btn-domestic').classList.toggle('border-[var(--brand-primary)]', tab === 'domestic');
            document.getElementById('tab-btn-domestic').classList.toggle('text-ink-900', tab === 'domestic');
            document.getElementById('tab-btn-domestic').classList.toggle('font-semibold', tab === 'domestic');
            document.getElementById('tab-btn-domestic').classList.toggle('border-transparent', tab !== 'domestic');
            document.getElementById('tab-btn-domestic').classList.toggle('text-ink-500', tab !== 'domestic');

            document.getElementById('tab-btn-international').classList.toggle('border-[var(--brand-primary)]', tab === 'international');
            document.getElementById('tab-btn-international').classList.toggle('text-ink-900', tab === 'international');
            document.getElementById('tab-btn-international').classList.toggle('font-semibold', tab === 'international');
            document.getElementById('tab-btn-international').classList.toggle('border-transparent', tab !== 'international');
            document.getElementById('tab-btn-international').classList.toggle('text-ink-500', tab !== 'international');
        }

        // Restores the tab a redirect (e.g. after deleting a tariff)
        // asked to land on, via ?tab=domestic|international in the URL.
        (function () {
            const params = new URLSearchParams(window.location.search);
            const tab = params.get('tab');
            if (tab === 'international') {
                showStandardBillingTab('international');
            }
        })();
    </script>

</x-layouts.app>
