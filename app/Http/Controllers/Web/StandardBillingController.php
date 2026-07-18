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
        return view('standard-billing.form', [
            'tariff' => $tariff,
            'serviceTypes' => ServiceType::where('billing_model', 'standard_billing')->orderBy('name')->get(),
            'zones' => Zone::orderBy('name')->get(),
            'zonePrices' => $tariff->zonePrices()->with('zone')->get(),
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
     * Adds/updates one (zone, price) row on this tariff — however many
     * zones exist, one row each, no fixed zone1..zoneN columns.
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
