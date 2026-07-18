<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ServiceType;
use App\Models\StandardBillingTariff;
use App\Models\TariffZonePrice;
use App\Models\Zone;
use App\Services\CsvService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class StandardBillingController extends Controller
{
    public function __construct(private CsvService $csv)
    {
    }

    public function index(): View
    {
        $tariffs = StandardBillingTariff::with('serviceType')->withCount('zonePrices')
            ->orderBy('service_type_id')->orderBy('min_weight')->paginate(15);

        return view('standard-billing.index', compact('tariffs'));
    }

    public function create(): View
    {
        return view('standard-billing.form', [
            'tariff' => new StandardBillingTariff(),
            'serviceTypes' => ServiceType::where('billing_model', 'standard_billing')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $tariff = StandardBillingTariff::create($this->validated($request));

        return redirect()->route('standard-billing.edit', $tariff)->with('status', 'Tariff created — add zone prices below.');
    }

    public function edit(StandardBillingTariff $tariff): View
    {
        return view('standard-billing.form', [
            'tariff' => $tariff,
            'serviceTypes' => ServiceType::where('billing_model', 'standard_billing')->orderBy('name')->get(),
            'zones' => Zone::orderBy('name')->get(),
            'zonePrices' => $tariff->zonePrices()->with('zone')->orderBy('zone_id')->get(),
        ]);
    }

    public function update(Request $request, StandardBillingTariff $tariff): RedirectResponse
    {
        $tariff->update($this->validated($request));

        return redirect()->route('standard-billing.edit', $tariff)->with('status', 'Tariff updated.');
    }

    public function destroy(StandardBillingTariff $tariff): RedirectResponse
    {
        $tariff->delete();

        return redirect()->route('standard-billing.index')->with('status', 'Tariff removed.');
    }

    /**
     * One line at a time, via a plain form — same pattern as every
     * other setup screen in this app. Adds or updates whichever zone is
     * selected; re-selecting an already-priced zone updates it rather
     * than duplicating.
     */
    public function addZonePrice(Request $request, StandardBillingTariff $tariff): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'zone_id' => 'required|exists:zones,id',
            'charge' => 'required|numeric|min:0',
            'additional_charge' => 'nullable|numeric|min:0',
            'transit_days' => 'nullable|integer|min:0',
        ]);

        $data = $validator->validate();

        TariffZonePrice::updateOrCreate(
            ['tariff_id' => $tariff->id, 'zone_id' => $data['zone_id']],
            [
                'charge' => $data['charge'],
                'additional_charge' => $data['additional_charge'] ?? 0,
                'transit_days' => $data['transit_days'] ?? null,
            ]
        );

        return redirect()->route('standard-billing.edit', $tariff)->with('status', 'Zone price saved.');
    }

    public function destroyZonePrice(StandardBillingTariff $tariff, TariffZonePrice $zonePrice): RedirectResponse
    {
        $zonePrice->delete();

        return redirect()->route('standard-billing.edit', $tariff)->with('status', 'Zone price removed.');
    }

    /**
     * Zone prices are scoped to a single tariff (each tariff is a
     * different weight band/service type), so export/import work on
     * this one tariff's rows, not the whole standard_billing_tariffs
     * table — the natural key is the zone's code, same pattern used
     * throughout every other CSV round trip in this app.
     */
    public function exportZonePrices(StandardBillingTariff $tariff): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $rows = $tariff->zonePrices()->with('zone')->orderBy('zone_id')->get()->map(fn ($p) => [
            $p->zone->code, $p->charge, $p->additional_charge, $p->transit_days,
        ]);

        return $this->csv->download(
            "tariff-{$tariff->id}-zone-prices.csv",
            ['zone_code', 'charge', 'additional_charge', 'transit_days'],
            $rows
        );
    }

    public function importZonePrices(Request $request, StandardBillingTariff $tariff): RedirectResponse
    {
        $request->validate(['file' => 'required|file|mimes:csv,txt']);

        $rows = $this->csv->parse($request->file('file'));
        $count = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $zone = Zone::where('code', strtoupper(trim($row['zone_code'] ?? '')))->first();

            if (! $zone || ! is_numeric($row['charge'] ?? null)) {
                $skipped++;
                continue;
            }

            TariffZonePrice::updateOrCreate(
                ['tariff_id' => $tariff->id, 'zone_id' => $zone->id],
                [
                    'charge' => $row['charge'],
                    'additional_charge' => is_numeric($row['additional_charge'] ?? null) ? $row['additional_charge'] : 0,
                    'transit_days' => ($row['transit_days'] ?? '') !== '' ? (int) $row['transit_days'] : null,
                ]
            );
            $count++;
        }

        return redirect()->route('standard-billing.edit', $tariff)->with('status', "Imported {$count} zone prices" . ($skipped ? ", skipped {$skipped} (unknown zone code or missing charge)." : '.'));
    }

    private function validated(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'service_type_id' => 'required|exists:service_types,id',
            'min_weight' => 'required|numeric|min:0',
            'max_weight' => 'required|numeric|gt:min_weight',
            'additional_weight' => 'required|numeric|min:0.01',
            'is_active' => 'sometimes|boolean',
        ]);

        $data = $validator->validate();
        $data['is_active'] = $request->boolean('is_active', true);

        return $data;
    }
}
