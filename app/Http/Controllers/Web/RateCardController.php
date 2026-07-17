<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\RateCard;
use App\Models\Zone;
use App\Models\ZoneRateMatrix;
use App\Models\ZoneWeightRate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class RateCardController extends Controller
{
    private array $billingModels = [
        'flat' => 'Flat Rate',
        'distance' => 'Distance-Based',
        'zone_to_zone' => 'Zone-to-Zone (Origin-Destination)',
        'origin_destination_weight' => 'Origin-Destination + Weight (by Service Type)',
        'weight' => 'Weight-Based',
        'volumetric' => 'Volumetric/Dimensional',
        'hybrid' => 'Hybrid (Base + Distance + Weight)',
        'service_multiplier' => 'Service-Type Multiplier',
        'time_surcharge' => 'Time-Based Surcharge',
        'contract' => 'Client-Specific Contract Rate',
        'truckload' => 'Truckload (Flat Rate per Truck)',
        'carton_rate' => 'Carton Rate (Flat Rate per Carton)',
    ];

    public function index(): View
    {
        $rateCards = RateCard::orderBy('service_type')->orderByDesc('priority')->paginate(15);

        return view('rate-cards.index', ['rateCards' => $rateCards, 'billingModels' => $this->billingModels]);
    }

    public function create(): View
    {
        return view('rate-cards.form', [
            'rateCard' => new RateCard(),
            'billingModels' => $this->billingModels,
            'serviceNames' => config('branding.service_names', []),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $rateCard = RateCard::create($this->validated($request));

        return redirect()->route('rate-cards.edit', $rateCard)->with('status', 'Rate card created.');
    }

    public function edit(RateCard $rateCard): View
    {
        $zones = Zone::orderBy('name')->get();

        $matrixEntries = $rateCard->billing_model === 'zone_to_zone'
            ? $rateCard->zoneMatrixEntries()->with(['originZone', 'destinationZone'])->get()
            : collect();

        $weightRates = $rateCard->billing_model === 'origin_destination_weight'
            ? ZoneWeightRate::where('rate_card_id', $rateCard->id)->with('zone')->orderBy('zone_id')->orderBy('min_weight')->get()
            : collect();

        return view('rate-cards.form', [
            'rateCard' => $rateCard,
            'billingModels' => $this->billingModels,
            'serviceNames' => config('branding.service_names', []),
            'zones' => $zones,
            'matrixEntries' => $matrixEntries,
            'weightRates' => $weightRates,
        ]);
    }

    public function update(Request $request, RateCard $rateCard): RedirectResponse
    {
        $rateCard->update($this->validated($request));

        return redirect()->route('rate-cards.edit', $rateCard)->with('status', 'Rate card updated.');
    }

    public function destroy(RateCard $rateCard): RedirectResponse
    {
        $rateCard->delete();

        return redirect()->route('rate-cards.index')->with('status', 'Rate card removed.');
    }

    /**
     * Upserts one zone-to-zone matrix entry at a time — mirrors the API's
     * RateController::setZonePrice, since staff configure these one pair
     * at a time from this same screen.
     */
    public function setZonePrice(Request $request, RateCard $rateCard): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'origin_zone_id' => 'required|exists:zones,id',
            'destination_zone_id' => 'required|exists:zones,id',
            'price' => 'required|numeric|min:0',
        ]);

        $validator->validate();

        ZoneRateMatrix::updateOrCreate(
            [
                'rate_card_id' => $rateCard->id,
                'origin_zone_id' => $request->origin_zone_id,
                'destination_zone_id' => $request->destination_zone_id,
            ],
            ['price' => $request->price]
        );

        return redirect()->route('rate-cards.edit', $rateCard)->with('status', 'Zone price saved.');
    }

    public function destroyZonePrice(RateCard $rateCard, ZoneRateMatrix $zonePrice): RedirectResponse
    {
        $zonePrice->delete();

        return redirect()->route('rate-cards.edit', $rateCard)->with('status', 'Zone price removed.');
    }

    /**
     * Adds one row to the origin_destination_weight rate table: a
     * (zone, weight band, service type) combination with its price,
     * transit days, and per-extra-kg overage rate. The zone itself
     * covers both directions of travel between whichever two states are
     * mapped to it (see ZoneMapping) — there's no separate "from"/"to"
     * dimension to set here.
     */
    public function addWeightRate(Request $request, RateCard $rateCard): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'zone_id' => 'required|exists:zones,id',
            'min_weight' => 'required|numeric|min:0',
            'max_weight' => 'required|numeric|gt:min_weight',
            'service_type' => 'required|string|max:100',
            'price' => 'required|numeric|min:0',
            'transit_days' => 'nullable|integer|min:0',
            'extra_amount_per_extra_kg' => 'nullable|numeric|min:0',
        ]);

        $validator->validate();
        $data = $validator->validated();
        $data['rate_card_id'] = $rateCard->id;
        $data['extra_amount_per_extra_kg'] = $data['extra_amount_per_extra_kg'] ?? 0;

        ZoneWeightRate::create($data);

        return redirect()->route('rate-cards.edit', $rateCard)->with('status', 'Rate row added.');
    }

    public function destroyWeightRate(RateCard $rateCard, ZoneWeightRate $weightRate): RedirectResponse
    {
        $weightRate->delete();

        return redirect()->route('rate-cards.edit', $rateCard)->with('status', 'Rate row removed.');
    }

    private function validated(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'service_type' => 'required|string|max:100',
            'billing_model' => 'required|in:' . implode(',', array_keys($this->billingModels)),
            'is_active' => 'sometimes|boolean',
            'priority' => 'nullable|integer|min:0',
            // model_config fields — only the ones relevant to the selected
            // billing_model are actually present in the request; the rest
            // are simply absent and filtered out below.
            'amount' => 'nullable|numeric',
            'per_km' => 'nullable|numeric',
            'per_kg' => 'nullable|numeric',
            'divisor' => 'nullable|numeric',
            'base_fare' => 'nullable|numeric',
            'multiplier' => 'nullable|numeric',
            'peak_multiplier' => 'nullable|numeric',
            'weekend_multiplier' => 'nullable|numeric',
            'fixed_amount' => 'nullable|numeric',
            'rate_per_truckload' => 'nullable|numeric',
            'rate_per_carton' => 'nullable|numeric',
        ]);

        $validator->validate();
        $data = $validator->validated();

        $data['is_active'] = $request->boolean('is_active', true);
        $data['priority'] = $data['priority'] ?? 0;

        $configKeysByModel = [
            'flat' => ['amount'],
            'distance' => ['per_km'],
            'weight' => ['per_kg'],
            'volumetric' => ['divisor', 'per_kg'],
            'hybrid' => ['base_fare', 'per_km', 'per_kg'],
            'service_multiplier' => ['multiplier'],
            'time_surcharge' => ['peak_multiplier', 'weekend_multiplier'],
            'contract' => ['fixed_amount'],
            'zone_to_zone' => [], // prices live in zone_rate_matrix instead
            'origin_destination_weight' => [], // prices live in zone_weight_rates instead
            'truckload' => ['rate_per_truckload'],
            'carton_rate' => ['rate_per_carton'],
        ];

        $relevantKeys = $configKeysByModel[$data['billing_model']] ?? [];
        $modelConfig = collect($data)->only($relevantKeys)->filter(fn ($v) => $v !== null)->all();

        return [
            'name' => $data['name'],
            'service_type' => $data['service_type'],
            'billing_model' => $data['billing_model'],
            'is_active' => $data['is_active'],
            'priority' => $data['priority'],
            'model_config' => $modelConfig ?: null,
        ];
    }
}
