<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ServiceType;
use App\Models\StandardBillingTariff;
use App\Models\TariffZonePrice;
use App\Models\Zone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class StandardBillingController extends Controller
{
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
        $existingPrices = $tariff->zonePrices()->get()->keyBy('zone_id');

        $zoneRows = Zone::orderBy('name')->get()->map(function (Zone $zone) use ($existingPrices) {
            $price = $existingPrices->get($zone->id);

            return (object) [
                'zone' => $zone,
                'charge' => $price?->charge,
                'additional_charge' => $price?->additional_charge,
                'transit_days' => $price?->transit_days,
            ];
        });

        return view('standard-billing.form', [
            'tariff' => $tariff,
            'serviceTypes' => ServiceType::where('billing_model', 'standard_billing')->orderBy('name')->get(),
            'zoneRows' => $zoneRows,
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
     * Saves every zone's price in one submit — the whole tariff's
     * pricing is set up in a single screen instead of adding zones one
     * at a time. A blank charge means "no price for this zone yet" and
     * removes any existing row for it; anything else upserts.
     */
    public function updateZonePrices(Request $request, StandardBillingTariff $tariff): RedirectResponse
    {
        $rows = $request->input('zone_prices', []);

        $rules = [];
        foreach (Zone::pluck('id') as $zoneId) {
            $rules["{$zoneId}.charge"] = 'nullable|numeric|min:0';
            $rules["{$zoneId}.additional_charge"] = 'nullable|numeric|min:0';
            $rules["{$zoneId}.transit_days"] = 'nullable|integer|min:0';
        }

        $validator = Validator::make($rows, $rules);

        $validator->validate();
        $validated = $validator->validated();

        $saved = 0;
        $cleared = 0;

        foreach (Zone::pluck('id') as $zoneId) {
            $row = $validated[$zoneId] ?? [];
            $charge = $row['charge'] ?? null;

            if ($charge === null || $charge === '') {
                $deleted = TariffZonePrice::where('tariff_id', $tariff->id)->where('zone_id', $zoneId)->delete();
                $cleared += $deleted;
                continue;
            }

            TariffZonePrice::updateOrCreate(
                ['tariff_id' => $tariff->id, 'zone_id' => $zoneId],
                [
                    'charge' => $charge,
                    'additional_charge' => $row['additional_charge'] ?? 0,
                    'transit_days' => ($row['transit_days'] ?? '') !== '' ? $row['transit_days'] : null,
                ]
            );
            $saved++;
        }

        $message = "Saved {$saved} zone price" . ($saved === 1 ? '' : 's');
        $message .= $cleared ? ", cleared {$cleared}." : '.';

        return redirect()->route('standard-billing.edit', $tariff)->with('status', $message);
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
