<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Country;
use App\Models\FleetBillingTariff;
use App\Models\ServiceType;
use App\Models\State;
use App\Models\VehicleType;
use App\Services\CsvService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class FleetBillingTariffController extends Controller
{
    public function __construct(private CsvService $csv)
    {
    }

    public function create(): View
    {
        return view('fleet-billing.form', [
            'tariff' => new FleetBillingTariff(),
            ...$this->formOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        FleetBillingTariff::create($data);

        return redirect()->route('standard-billing.index', ['model' => 'fleet'])->with('status', 'Fleet rate added.');
    }

    public function edit(FleetBillingTariff $tariff): View
    {
        return view('fleet-billing.form', [
            'tariff' => $tariff,
            ...$this->formOptions(),
        ]);
    }

    public function update(Request $request, FleetBillingTariff $tariff): RedirectResponse
    {
        $data = $this->validated($request, $tariff);

        $tariff->update($data);

        return redirect()->route('standard-billing.index', ['model' => 'fleet'])->with('status', 'Fleet rate updated.');
    }

    public function destroy(FleetBillingTariff $tariff): RedirectResponse
    {
        $tariff->delete();

        return redirect()->route('standard-billing.index', ['model' => 'fleet'])->with('status', 'Fleet rate removed.');
    }

    public function export(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $rows = FleetBillingTariff::with([
            'serviceType', 'vehicleType', 'originState', 'originCity', 'originCountry',
            'destinationState', 'destinationCity', 'destinationCountry',
        ])
            ->orderBy('service_type_id')->orderBy('vehicle_type_id')->orderBy('min_weight')
            ->get()
            ->map(fn ($t) => [
                $t->vehicleType->code,
                $t->originState?->short_code, $t->originCity?->short_code, $t->originCountry?->code,
                $t->destinationState?->short_code, $t->destinationCity?->short_code, $t->destinationCountry?->code,
                $t->serviceType->code,
                $t->min_weight, $t->max_weight, $t->max_weight_limit, $t->base_charge, $t->additional_weight, $t->additional_charge,
                $t->fuel_surcharge_percentage,
                $t->empty_return_charge_type, $t->empty_return_charge_value, $t->transit_days,
            ]);

        return $this->csv->download('fleet-billing.csv', [
            'vehicle_type_code',
            'origin_state_code', 'origin_city_code', 'origin_country_code',
            'destination_state_code', 'destination_city_code', 'destination_country_code',
            'product_code',
            'base_weight', 'max_weight', 'max_weight_limit', 'weight_base_charge', 'additional_weight', 'additional_charge',
            'fuel_surcharge_percentage',
            'empty_return_charge_type', 'empty_return_charge_value', 'transit_days',
        ], $rows);
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate(['file' => 'required|file|mimes:csv,txt']);

        $rows = $this->csv->parse($request->file('file'));
        $count = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $vehicleType = VehicleType::where('code', strtoupper(trim($row['vehicle_type_code'] ?? '')))->first();
            $serviceType = ServiceType::where('code', strtoupper(trim($row['product_code'] ?? '')))->first();
            $minWeight = $row['base_weight'] ?? null;
            $maxWeightLimit = $row['max_weight_limit'] ?? null;

            $originCountry = ! empty($row['origin_country_code'])
                ? Country::where('code', strtoupper(trim($row['origin_country_code'])))->first()
                : null;
            $originState = ! $originCountry && ! empty($row['origin_state_code'])
                ? State::where('short_code', strtoupper(trim($row['origin_state_code'])))->first()
                : null;

            $destinationCountry = ! empty($row['destination_country_code'])
                ? Country::where('code', strtoupper(trim($row['destination_country_code'])))->first()
                : null;
            $destinationState = ! $destinationCountry && ! empty($row['destination_state_code'])
                ? State::where('short_code', strtoupper(trim($row['destination_state_code'])))->first()
                : null;

            $originResolved = $originCountry || $originState;
            $destinationResolved = $destinationCountry || $destinationState;

            if (! $vehicleType || ! $serviceType || ! $originResolved || ! $destinationResolved || ! is_numeric($minWeight) || ! is_numeric($maxWeightLimit)) {
                $skipped++;
                continue;
            }

            $originCity = ($originState && ! empty($row['origin_city_code']))
                ? City::where('state_id', $originState->id)->where('short_code', strtoupper(trim($row['origin_city_code'])))->first()
                : null;
            $destinationCity = ($destinationState && ! empty($row['destination_city_code']))
                ? City::where('state_id', $destinationState->id)->where('short_code', strtoupper(trim($row['destination_city_code'])))->first()
                : null;

            FleetBillingTariff::updateOrCreate(
                [
                    'service_type_id' => $serviceType->id,
                    'vehicle_type_id' => $vehicleType->id,
                    'origin_state_id' => $originState?->id,
                    'origin_city_id' => $originCity?->id,
                    'origin_country_id' => $originCountry?->id,
                    'destination_state_id' => $destinationState?->id,
                    'destination_city_id' => $destinationCity?->id,
                    'destination_country_id' => $destinationCountry?->id,
                    'min_weight' => $minWeight,
                    'max_weight_limit' => $maxWeightLimit,
                ],
                [
                    'max_weight' => is_numeric($row['max_weight'] ?? null) ? $row['max_weight'] : $minWeight,
                    'base_charge' => is_numeric($row['weight_base_charge'] ?? null) ? $row['weight_base_charge'] : 0,
                    'additional_weight' => is_numeric($row['additional_weight'] ?? null) ? $row['additional_weight'] : 1,
                    'additional_charge' => is_numeric($row['additional_charge'] ?? null) ? $row['additional_charge'] : 0,
                    'fuel_surcharge_percentage' => is_numeric($row['fuel_surcharge_percentage'] ?? null) ? $row['fuel_surcharge_percentage'] : 0,
                    'empty_return_charge_type' => in_array($row['empty_return_charge_type'] ?? null, ['flat', 'percentage']) ? $row['empty_return_charge_type'] : 'flat',
                    'empty_return_charge_value' => is_numeric($row['empty_return_charge_value'] ?? null) ? $row['empty_return_charge_value'] : 0,
                    'transit_days' => is_numeric($row['transit_days'] ?? null) ? $row['transit_days'] : null,
                    'is_active' => true,
                ]
            );
            $count++;
        }

        return redirect()->route('standard-billing.index', ['model' => 'fleet'])->with('status', "Imported {$count} fleet rates" . ($skipped ? ", skipped {$skipped} (unknown vehicle/state/country/product code or missing weight)." : '.'));
    }

    private function formOptions(): array
    {
        return [
            'serviceTypes' => ServiceType::where('billing_model', 'fleet_billing')->orderBy('name')->get(),
            'vehicleTypes' => VehicleType::where('is_active', true)->orderBy('name')->get(),
            'states' => State::orderBy('name')->get(),
            'cities' => City::orderBy('name')->get(),
            'countries' => Country::where('code', '!=', 'NG')->orderBy('name')->get(),
        ];
    }

    private function validated(Request $request, ?FleetBillingTariff $ignoring = null): array
    {
        $validator = Validator::make($request->all(), [
            'service_type_id' => 'required|exists:service_types,id',
            'vehicle_type_id' => 'required|exists:vehicle_types,id',
            'origin_type' => 'required|in:state,country',
            'origin_state_id' => 'required_if:origin_type,state|nullable|exists:states,id',
            'origin_city_id' => 'nullable|exists:cities,id',
            'origin_country_id' => 'required_if:origin_type,country|nullable|exists:countries,id',
            'destination_type' => 'required|in:state,country',
            'destination_state_id' => 'required_if:destination_type,state|nullable|exists:states,id',
            'destination_city_id' => 'nullable|exists:cities,id',
            'destination_country_id' => 'required_if:destination_type,country|nullable|exists:countries,id',
            'min_weight' => 'required|numeric|min:0',
            'max_weight' => 'required|numeric|min:0',
            'max_weight_limit' => 'required|numeric|gt:min_weight',
            'base_charge' => 'required|numeric|min:0',
            'additional_weight' => 'required|numeric|min:0.01',
            'additional_charge' => 'required|numeric|min:0',
            'fuel_surcharge_percentage' => 'required|numeric|min:0|max:100',
            'empty_return_charge_type' => 'required|in:flat,percentage',
            'empty_return_charge_value' => 'required|numeric|min:0',
            'transit_days' => 'nullable|integer|min:0',
            'is_active' => 'sometimes|boolean',
        ]);

        // A tariff's Max weight limit can't exceed the selected
        // vehicle's own real capacity — a lower, more restrictive
        // figure is fine (e.g. a specific lane with a bridge weight
        // limit), just never higher than the vehicle can actually
        // carry. PricingEngine separately checks a SHIPMENT's weight
        // against this same capacity at quote time too, regardless of
        // how the matched tariff happens to be configured.
        $validator->after(function ($validator) use ($request) {
            $vehicleType = \App\Models\VehicleType::find($request->input('vehicle_type_id'));
            $maxWeightLimit = (float) $request->input('max_weight_limit');

            if ($vehicleType?->max_weight_capacity && $maxWeightLimit > (float) $vehicleType->max_weight_capacity) {
                $validator->errors()->add(
                    'max_weight_limit',
                    "Max weight limit ({$maxWeightLimit}kg) can't exceed {$vehicleType->name}'s capacity ({$vehicleType->max_weight_capacity}kg)."
                );
            }
        });

        $data = $validator->validate();
        $data['is_active'] = $request->boolean('is_active', true);

        if ($data['origin_type'] === 'country') {
            $data['origin_state_id'] = null;
            $data['origin_city_id'] = null;
        } else {
            $data['origin_country_id'] = null;
        }

        if ($data['destination_type'] === 'country') {
            $data['destination_state_id'] = null;
            $data['destination_city_id'] = null;
        } else {
            $data['destination_country_id'] = null;
        }

        unset($data['origin_type'], $data['destination_type']);

        if (($data['origin_city_id'] ?? null) === '') {
            $data['origin_city_id'] = null;
        }
        if (($data['destination_city_id'] ?? null) === '') {
            $data['destination_city_id'] = null;
        }
        if (($data['transit_days'] ?? null) === '') {
            $data['transit_days'] = null;
        }

        return $data;
    }
}
