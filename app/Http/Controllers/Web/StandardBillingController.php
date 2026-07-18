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
            'zones' => Zone::orderBy('name')->get(),
        ]);
    }

    /**
     * Creates one or more weight-band tariffs for the same service type
     * in a single submission — e.g. 0.5–20kg and 20.5–40kg entered as two
     * rows on one form, instead of visiting "Add tariff" twice. Each row
     * still becomes its own StandardBillingTariff record; nothing about
     * how they're matched at quote time changes, this only changes how
     * many you can set up in one go.
     *
     * Each row can also carry ONE zone price (zone/charge/additional
     * charge/transit days) — filling those in creates the tariff AND its
     * first zone price together, instead of needing a separate trip to
     * the edit page just to price the one zone you already had in mind.
     * Leaving them blank still creates the tariff with no zone prices
     * yet, exactly as before — prices can always be added afterward from
     * the edit page, one at a time or via CSV.
     */
    public function store(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'service_type_id' => 'required|exists:service_types,id',
            'ranges' => 'required|array|min:1',
            'ranges.*.min_weight' => 'required|numeric|min:0',
            'ranges.*.max_weight' => 'required|numeric|gt:ranges.*.min_weight',
            'ranges.*.additional_weight' => 'required|numeric|min:0.01',
            'ranges.*.zone_id' => 'nullable|exists:zones,id',
            'ranges.*.charge' => 'nullable|required_with:ranges.*.zone_id|numeric|min:0',
            'ranges.*.zone_additional_charge' => 'nullable|numeric|min:0',
            'ranges.*.transit_days' => 'nullable|integer|min:0',
        ]);

        $validator->after(function ($validator) use ($request) {
            $serviceTypeId = $request->input('service_type_id');
            $ranges = $request->input('ranges', []);

            // Existing active tariffs for this service type — every new
            // row is checked against these too, not just against each
            // other, since a duplicate/overlap against an already-saved
            // tariff is exactly what caused the "same weight range
            // twice" bug this validation exists to prevent.
            $existing = StandardBillingTariff::where('service_type_id', $serviceTypeId)
                ->where('is_active', true)
                ->get(['min_weight', 'max_weight']);

            $seen = [];

            foreach ($ranges as $index => $range) {
                if (! isset($range['min_weight'], $range['max_weight'])) {
                    continue; // already flagged by the required/numeric rules above
                }

                $min = (float) $range['min_weight'];
                $max = (float) $range['max_weight'];

                foreach ($existing as $tariff) {
                    if ($this->rangesOverlap($min, $max, (float) $tariff->min_weight, (float) $tariff->max_weight)) {
                        $validator->errors()->add(
                            "ranges.{$index}.min_weight",
                            "Row " . ($index + 1) . " ({$min}–{$max}kg) overlaps an existing tariff for this service type ({$tariff->min_weight}–{$tariff->max_weight}kg)."
                        );
                    }
                }

                foreach ($seen as $otherIndex => $other) {
                    if ($this->rangesOverlap($min, $max, $other[0], $other[1])) {
                        $validator->errors()->add(
                            "ranges.{$index}.min_weight",
                            "Row " . ($index + 1) . " ({$min}–{$max}kg) overlaps row " . ($otherIndex + 1) . " ({$other[0]}–{$other[1]}kg) in this same submission."
                        );
                    }
                }

                $seen[$index] = [$min, $max];
            }
        });

        $data = $validator->validate();
        $isActive = $request->boolean('is_active', true);

        $created = 0;
        $priced = 0;
        foreach ($data['ranges'] as $range) {
            $tariff = StandardBillingTariff::create([
                'service_type_id' => $data['service_type_id'],
                'min_weight' => $range['min_weight'],
                'max_weight' => $range['max_weight'],
                'additional_weight' => $range['additional_weight'],
                'is_active' => $isActive,
            ]);
            $created++;

            if (! empty($range['zone_id']) && isset($range['charge']) && $range['charge'] !== '') {
                TariffZonePrice::create([
                    'tariff_id' => $tariff->id,
                    'zone_id' => $range['zone_id'],
                    'charge' => $range['charge'],
                    'additional_charge' => $range['zone_additional_charge'] ?? 0,
                    'transit_days' => ($range['transit_days'] ?? '') !== '' ? $range['transit_days'] : null,
                ]);
                $priced++;
            }
        }

        $message = $created === 1
            ? 'Tariff created' . ($priced ? ' with its zone price.' : ' — add zone prices below.')
            : "{$created} tariffs created" . ($priced ? " ({$priced} with a zone price already set)." : ' — add zone prices to each from the list.');

        return redirect()->route('standard-billing.index')->with('status', $message);
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
        $tariff->update($this->validated($request, $tariff));

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
            $min = (float) $request->input('min_weight');
            $max = (float) $request->input('max_weight');

            $others = StandardBillingTariff::where('service_type_id', $request->input('service_type_id'))
                ->where('is_active', true)
                ->when($ignoring, fn ($query) => $query->where('id', '!=', $ignoring->id))
                ->get(['min_weight', 'max_weight']);

            foreach ($others as $tariff) {
                if ($this->rangesOverlap($min, $max, (float) $tariff->min_weight, (float) $tariff->max_weight)) {
                    $validator->errors()->add(
                        'max_weight',
                        "This range ({$min}–{$max}kg) overlaps another tariff for this service type ({$tariff->min_weight}–{$tariff->max_weight}kg)."
                    );
                }
            }
        });

        $data = $validator->validate();
        $data['is_active'] = $request->boolean('is_active', true);

        return $data;
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
