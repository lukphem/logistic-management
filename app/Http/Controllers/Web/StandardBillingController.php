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
        $tariffs = StandardBillingTariff::with(['serviceType', 'zonePrices.zone'])
            ->orderBy('service_type_id')->orderBy('min_weight')->paginate(15);

        return view('standard-billing.index', compact('tariffs'));
    }

    public function create(): View
    {
        return view('standard-billing.form', [
            'tariff' => new StandardBillingTariff(),
            'serviceTypes' => ServiceType::where('billing_model', 'standard_billing')->orderBy('name')->get(),
            'zones' => Zone::orderBy('name')->get(),
        ]);
    }

    /**
     * One weight range, plus as many zone prices as needed for it, all
     * in a single submission — service type + min/max/additional weight
     * once, then a repeatable Zone/Charge/Additional charge/Transit days
     * section. Creates one StandardBillingTariff and, for every zone row
     * that was filled in, its matching TariffZonePrice — no separate
     * trip to the edit page needed for the common case of setting up a
     * tariff with several zones already known up front.
     */
    public function store(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'service_type_id' => 'required|exists:service_types,id',
            'min_weight' => 'required|numeric|min:0',
            'max_weight' => 'required|numeric|gt:min_weight',
            'additional_weight' => 'required|numeric|min:0.01',
            'zone_prices' => 'nullable|array',
            'zone_prices.*.zone_id' => 'nullable|exists:zones,id',
            'zone_prices.*.charge' => 'nullable|required_with:zone_prices.*.zone_id|numeric|min:0',
            'zone_prices.*.additional_charge' => 'nullable|numeric|min:0',
            'zone_prices.*.transit_days' => 'nullable|integer|min:0',
        ]);

        $validator->after(function ($validator) use ($request) {
            $this->rejectIfOverlapping(
                $validator,
                'max_weight',
                (int) $request->input('service_type_id'),
                (float) $request->input('min_weight'),
                (float) $request->input('max_weight')
            );
        });

        $data = $validator->validate();

        $tariff = StandardBillingTariff::create([
            'service_type_id' => $data['service_type_id'],
            'min_weight' => $data['min_weight'],
            'max_weight' => $data['max_weight'],
            'additional_weight' => $data['additional_weight'],
            'is_active' => $request->boolean('is_active', true),
        ]);

        $priced = 0;
        foreach ($data['zone_prices'] ?? [] as $row) {
            if (empty($row['zone_id']) || ! isset($row['charge']) || $row['charge'] === '') {
                continue;
            }

            TariffZonePrice::create([
                'tariff_id' => $tariff->id,
                'zone_id' => $row['zone_id'],
                'charge' => $row['charge'],
                'additional_charge' => $row['additional_charge'] ?? 0,
                'transit_days' => ($row['transit_days'] ?? '') !== '' ? $row['transit_days'] : null,
            ]);
            $priced++;
        }

        $message = 'Tariff created' . ($priced ? " with {$priced} zone price" . ($priced === 1 ? '' : 's') . '.' : ' — add zone prices below.');

        return redirect()->route('standard-billing.edit', $tariff)->with('status', $message);
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

    /**
     * Same single-form principle as store(): the tariff's own fields and
     * every zone price are submitted together. Each zone-price row may
     * carry a hidden `id` (an existing TariffZonePrice) — present + blank
     * charge deletes it, present + filled updates it, absent + filled
     * creates a new one. Any existing price whose id isn't present in
     * this submission at all was removed client-side (the "Remove"
     * button deletes its row from the DOM) and gets deleted here too.
     */
    public function update(Request $request, StandardBillingTariff $tariff): RedirectResponse
    {
        $data = $this->validated($request, $tariff);

        $validator = Validator::make($request->all(), [
            'zone_prices' => 'nullable|array',
            'zone_prices.*.id' => 'nullable|integer|exists:tariff_zone_prices,id',
            'zone_prices.*.zone_id' => 'nullable|exists:zones,id',
            'zone_prices.*.charge' => 'nullable|numeric|min:0',
            'zone_prices.*.additional_charge' => 'nullable|numeric|min:0',
            'zone_prices.*.transit_days' => 'nullable|integer|min:0',
        ]);
        $zoneData = $validator->validate();

        $tariff->update($data);

        $submittedIds = [];
        $saved = 0;

        foreach ($zoneData['zone_prices'] ?? [] as $row) {
            $hasCharge = isset($row['charge']) && $row['charge'] !== '';

            if (! empty($row['id'])) {
                $submittedIds[] = $row['id'];

                if (! $hasCharge) {
                    TariffZonePrice::where('id', $row['id'])->where('tariff_id', $tariff->id)->delete();
                    continue;
                }

                TariffZonePrice::where('id', $row['id'])->where('tariff_id', $tariff->id)->update([
                    'zone_id' => $row['zone_id'],
                    'charge' => $row['charge'],
                    'additional_charge' => $row['additional_charge'] ?? 0,
                    'transit_days' => ($row['transit_days'] ?? '') !== '' ? $row['transit_days'] : null,
                ]);
                $saved++;
                continue;
            }

            if ($hasCharge && ! empty($row['zone_id'])) {
                $new = TariffZonePrice::create([
                    'tariff_id' => $tariff->id,
                    'zone_id' => $row['zone_id'],
                    'charge' => $row['charge'],
                    'additional_charge' => $row['additional_charge'] ?? 0,
                    'transit_days' => ($row['transit_days'] ?? '') !== '' ? $row['transit_days'] : null,
                ]);
                $submittedIds[] = $new->id;
                $saved++;
            }
        }

        // A row removed via the "Remove" button never reaches this
        // request at all — its existing price is deleted here since it's
        // no longer represented in the submission.
        TariffZonePrice::where('tariff_id', $tariff->id)->whereNotIn('id', $submittedIds)->delete();

        return redirect()->route('standard-billing.edit', $tariff)->with('status', "Tariff updated, {$saved} zone price" . ($saved === 1 ? '' : 's') . ' saved.');
    }

    public function destroy(StandardBillingTariff $tariff): RedirectResponse
    {
        $tariff->delete();

        return redirect()->route('standard-billing.index')->with('status', 'Tariff removed.');
    }

    /**
     * Zone prices for ONE existing tariff — for adding more zones to a
     * tariff that already exists. See exportAll()/importAll() below for
     * the combined format that creates tariffs and their zone prices
     * together from scratch.
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

    /**
     * The combined format matching what the create form now collects in
     * one go: service type + weight range + zone price, one row per
     * zone. A tariff with no zone prices yet still gets one row (blank
     * zone columns) so its weight range isn't lost on export. Covers
     * every tariff, not just one.
     */
    public function exportAll(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $rows = [];

        foreach (StandardBillingTariff::with(['serviceType', 'zonePrices.zone'])->orderBy('service_type_id')->orderBy('min_weight')->get() as $tariff) {
            if ($tariff->zonePrices->isEmpty()) {
                $rows[] = [$tariff->serviceType->code, $tariff->min_weight, $tariff->max_weight, $tariff->additional_weight, '', '', '', ''];
                continue;
            }

            foreach ($tariff->zonePrices as $price) {
                $rows[] = [
                    $tariff->serviceType->code, $tariff->min_weight, $tariff->max_weight, $tariff->additional_weight,
                    $price->zone->code, $price->charge, $price->additional_charge, $price->transit_days,
                ];
            }
        }

        return $this->csv->download(
            'standard-billing.csv',
            ['service_type_code', 'min_weight', 'max_weight', 'additional_weight', 'zone_code', 'charge', 'additional_charge', 'transit_days'],
            $rows
        );
    }

    /**
     * Creates tariffs AND their zone prices together from one file —
     * rows sharing the same (service_type_code, min_weight, max_weight)
     * build up one tariff's several zone prices. An exact-match tariff
     * is reused (so re-importing an amended export updates rather than
     * duplicates); a NEW range that overlaps an existing active tariff
     * for that service type is skipped, same protection as the form
     * (Increment 51) — CSV import can't be used to bypass it.
     */
    public function importAll(Request $request): RedirectResponse
    {
        $request->validate(['file' => 'required|file|mimes:csv,txt']);

        $rows = $this->csv->parse($request->file('file'));
        $tariffCache = [];
        $tariffsCreated = 0;
        $pricesSaved = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $serviceType = ServiceType::where('code', strtoupper(trim($row['service_type_code'] ?? '')))->first();
            $minWeight = $row['min_weight'] ?? null;
            $maxWeight = $row['max_weight'] ?? null;

            if (! $serviceType || ! is_numeric($minWeight) || ! is_numeric($maxWeight)) {
                $skipped++;
                continue;
            }

            $cacheKey = "{$serviceType->id}:{$minWeight}:{$maxWeight}";

            if (! isset($tariffCache[$cacheKey])) {
                $tariff = StandardBillingTariff::where('service_type_id', $serviceType->id)
                    ->where('min_weight', $minWeight)->where('max_weight', $maxWeight)->first();

                if (! $tariff) {
                    $overlaps = StandardBillingTariff::where('service_type_id', $serviceType->id)
                        ->where('is_active', true)
                        ->get(['min_weight', 'max_weight'])
                        ->contains(fn ($t) => $this->rangesOverlap((float) $minWeight, (float) $maxWeight, (float) $t->min_weight, (float) $t->max_weight));

                    if ($overlaps) {
                        $skipped++;
                        continue;
                    }

                    $tariff = StandardBillingTariff::create([
                        'service_type_id' => $serviceType->id,
                        'min_weight' => $minWeight,
                        'max_weight' => $maxWeight,
                        'additional_weight' => is_numeric($row['additional_weight'] ?? null) ? $row['additional_weight'] : 1,
                        'is_active' => true,
                    ]);
                    $tariffsCreated++;
                }

                $tariffCache[$cacheKey] = $tariff;
            }

            if (! empty($row['zone_code'])) {
                $zone = Zone::where('code', strtoupper(trim($row['zone_code'])))->first();

                if ($zone && is_numeric($row['charge'] ?? null)) {
                    TariffZonePrice::updateOrCreate(
                        ['tariff_id' => $tariffCache[$cacheKey]->id, 'zone_id' => $zone->id],
                        [
                            'charge' => $row['charge'],
                            'additional_charge' => is_numeric($row['additional_charge'] ?? null) ? $row['additional_charge'] : 0,
                            'transit_days' => ($row['transit_days'] ?? '') !== '' ? (int) $row['transit_days'] : null,
                        ]
                    );
                    $pricesSaved++;
                }
            }
        }

        return redirect()->route('standard-billing.index')->with(
            'status',
            "Imported: {$tariffsCreated} tariffs created, {$pricesSaved} zone prices saved" . ($skipped ? ", {$skipped} rows skipped (unknown service type, overlapping range, or missing weight)." : '.')
        );
    }

    private function validated(Request $request, ?StandardBillingTariff $ignoring = null): array
    {
        $validator = Validator::make($request->all(), [
            'service_type_id' => 'required|exists:service_types,id',
            'min_weight' => 'required|numeric|min:0',
            'max_weight' => 'required|numeric|gt:min_weight',
            'additional_weight' => 'required|numeric|min:0.01',
            'is_active' => 'sometimes|boolean',
        ]);

        $validator->after(function ($validator) use ($request, $ignoring) {
            $this->rejectIfOverlapping(
                $validator,
                'max_weight',
                (int) $request->input('service_type_id'),
                (float) $request->input('min_weight'),
                (float) $request->input('max_weight'),
                $ignoring
            );
        });

        $data = $validator->validate();
        $data['is_active'] = $request->boolean('is_active', true);

        return $data;
    }

    /**
     * Shared by store() and validated() (update) — adds a validation
     * error if the given range overlaps any other ACTIVE tariff for the
     * same service type. Only compares against active tariffs, since an
     * inactive one can't be matched by a real quote anyway.
     */
    private function rejectIfOverlapping($validator, string $errorField, int $serviceTypeId, float $min, float $max, ?StandardBillingTariff $ignoring = null): void
    {
        $others = StandardBillingTariff::where('service_type_id', $serviceTypeId)
            ->where('is_active', true)
            ->when($ignoring, fn ($query) => $query->where('id', '!=', $ignoring->id))
            ->get(['min_weight', 'max_weight']);

        foreach ($others as $tariff) {
            if ($this->rangesOverlap($min, $max, (float) $tariff->min_weight, (float) $tariff->max_weight)) {
                $validator->errors()->add(
                    $errorField,
                    "This range ({$min}–{$max}kg) overlaps another tariff for this service type ({$tariff->min_weight}–{$tariff->max_weight}kg)."
                );
            }
        }
    }

    /**
     * Standard closed-interval overlap test — two ranges overlap unless
     * one ends strictly before the other begins. Touching endpoints
     * (0–20 and 20–40) count as overlapping on purpose: a shipment at
     * exactly 20kg would otherwise match two tariffs at once, and
     * PricingEngine already treats max_weight as an inclusive boundary.
     */
    private function rangesOverlap(float $minA, float $maxA, float $minB, float $maxB): bool
    {
        return $minA <= $maxB && $minB <= $maxA;
    }
}
