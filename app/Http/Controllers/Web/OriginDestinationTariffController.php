<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\OriginDestinationTariff;
use App\Models\ServiceType;
use App\Models\State;
use App\Services\CsvService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class OriginDestinationTariffController extends Controller
{
    public function __construct(private CsvService $csv)
    {
    }

    public function index(): View
    {
        $tariffs = OriginDestinationTariff::with(['serviceType', 'originState', 'originCity', 'destinationState', 'destinationCity'])
            ->orderBy('service_type_id')->orderBy('origin_state_id')->orderBy('destination_state_id')->orderBy('min_weight')
            ->paginate(20);

        return view('origin-destination-billing.index', compact('tariffs'));
    }

    public function create(): View
    {
        return view('origin-destination-billing.form', [
            'tariff' => new OriginDestinationTariff(),
            ...$this->formOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        OriginDestinationTariff::create($data);

        return redirect()->route('origin-destination-billing.index')->with('status', 'Route rate added.');
    }

    public function edit(OriginDestinationTariff $tariff): View
    {
        return view('origin-destination-billing.form', [
            'tariff' => $tariff,
            ...$this->formOptions(),
        ]);
    }

    public function update(Request $request, OriginDestinationTariff $tariff): RedirectResponse
    {
        $data = $this->validated($request, $tariff);

        $tariff->update($data);

        return redirect()->route('origin-destination-billing.index')->with('status', 'Route rate updated.');
    }

    public function destroy(OriginDestinationTariff $tariff): RedirectResponse
    {
        $tariff->delete();

        return redirect()->route('origin-destination-billing.index')->with('status', 'Route rate removed.');
    }

    /**
     * Matches the columns given for this billing model directly:
     * Origin Code / Dest Code / Product Code / Base Weight / Max Weight
     * / Max Weight Limit / Base Charge / Additional Weight / Additional
     * Charge / Transit Day. Origin/Dest Code is a state's code, or
     * "STATECODE-CITYCODE" for a city-specific row (matching how
     * City::code is already composed, e.g. "NG-LA-IKJ") — kept as two
     * separate code fields here (state code, optional city code) so a
     * business's existing rate-card spreadsheet maps onto this with
     * minimal rework.
     */
    public function export(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $rows = OriginDestinationTariff::with(['serviceType', 'originState', 'originCity', 'destinationState', 'destinationCity'])
            ->orderBy('service_type_id')->orderBy('origin_state_id')->orderBy('destination_state_id')->orderBy('min_weight')
            ->get()
            ->map(fn ($t) => [
                $t->originState->short_code, $t->originCity?->short_code,
                $t->destinationState->short_code, $t->destinationCity?->short_code,
                $t->serviceType->code,
                $t->min_weight, $t->max_weight, $t->max_weight_limit,
                $t->base_charge, $t->additional_weight, $t->additional_charge, $t->transit_days,
            ]);

        return $this->csv->download('origin-destination-billing.csv', [
            'origin_state_code', 'origin_city_code',
            'destination_state_code', 'destination_city_code',
            'product_code',
            'base_weight', 'max_weight', 'max_weight_limit',
            'base_charge', 'additional_weight', 'additional_charge', 'transit_days',
        ], $rows);
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate(['file' => 'required|file|mimes:csv,txt']);

        $rows = $this->csv->parse($request->file('file'));
        $count = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $originState = State::where('short_code', strtoupper(trim($row['origin_state_code'] ?? '')))->first();
            $destinationState = State::where('short_code', strtoupper(trim($row['destination_state_code'] ?? '')))->first();
            $serviceType = ServiceType::where('code', strtoupper(trim($row['product_code'] ?? '')))->first();
            $minWeight = $row['base_weight'] ?? null;
            $maxWeight = $row['max_weight'] ?? null;

            if (! $originState || ! $destinationState || ! $serviceType || ! is_numeric($minWeight) || ! is_numeric($maxWeight)) {
                $skipped++;
                continue;
            }

            $originCity = ! empty($row['origin_city_code'])
                ? City::where('state_id', $originState->id)->where('short_code', strtoupper(trim($row['origin_city_code'])))->first()
                : null;
            $destinationCity = ! empty($row['destination_city_code'])
                ? City::where('state_id', $destinationState->id)->where('short_code', strtoupper(trim($row['destination_city_code'])))->first()
                : null;

            OriginDestinationTariff::updateOrCreate(
                [
                    'service_type_id' => $serviceType->id,
                    'origin_state_id' => $originState->id,
                    'origin_city_id' => $originCity?->id,
                    'destination_state_id' => $destinationState->id,
                    'destination_city_id' => $destinationCity?->id,
                    'min_weight' => $minWeight,
                    'max_weight' => $maxWeight,
                ],
                [
                    'max_weight_limit' => is_numeric($row['max_weight_limit'] ?? null) ? $row['max_weight_limit'] : $minWeight,
                    'base_charge' => is_numeric($row['base_charge'] ?? null) ? $row['base_charge'] : 0,
                    'additional_weight' => is_numeric($row['additional_weight'] ?? null) ? $row['additional_weight'] : 1,
                    'additional_charge' => is_numeric($row['additional_charge'] ?? null) ? $row['additional_charge'] : 0,
                    'transit_days' => is_numeric($row['transit_days'] ?? null) ? $row['transit_days'] : null,
                    'is_active' => true,
                ]
            );
            $count++;
        }

        return back()->with('status', "Imported {$count} route rates" . ($skipped ? ", skipped {$skipped} (unknown state/product code or missing weight)." : '.'));
    }

    private function formOptions(): array
    {
        return [
            'serviceTypes' => ServiceType::where('billing_model', 'origin_destination_billing')->orderBy('name')->get(),
            'states' => State::orderBy('name')->get(),
            'cities' => City::orderBy('name')->get(),
        ];
    }

    private function validated(Request $request, ?OriginDestinationTariff $ignoring = null): array
    {
        $validator = Validator::make($request->all(), [
            'service_type_id' => 'required|exists:service_types,id',
            'origin_state_id' => 'required|exists:states,id',
            'origin_city_id' => 'nullable|exists:cities,id',
            'destination_state_id' => 'required|exists:states,id',
            'destination_city_id' => 'nullable|exists:cities,id',
            'min_weight' => 'required|numeric|min:0',
            'max_weight' => 'required|numeric|gt:min_weight',
            'max_weight_limit' => 'required|numeric|min:0',
            'base_charge' => 'required|numeric|min:0',
            'additional_weight' => 'required|numeric|min:0.01',
            'additional_charge' => 'required|numeric|min:0',
            'transit_days' => 'nullable|integer|min:0',
            'is_active' => 'sometimes|boolean',
        ]);

        $data = $validator->validate();
        $data['is_active'] = $request->boolean('is_active', true);

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
