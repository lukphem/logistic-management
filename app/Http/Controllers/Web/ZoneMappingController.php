<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\State;
use App\Models\ThirdPartyCountryMapping;
use App\Models\Zone;
use App\Models\ZoneCountryMapping;
use App\Models\ZoneMapping;
use App\Services\CsvService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class ZoneMappingController extends Controller
{
    public function __construct(private CsvService $csv)
    {
    }

    /**
     * Two independent sections on one screen:
     *
     * Domestic — every combination of Nigeria's states (~666 pairs),
     * auto-generated rather than entered one at a time, since the
     * business is Nigeria-based and the full set is known and fixed.
     * One row covers a route both ways (see ZoneMapping::resolveZone).
     *
     * International — one row per (non-Nigeria) country, since an
     * international shipment only ever needs to know which zone the
     * OTHER country belongs to; Nigeria is always the fixed origin side,
     * so there's no pair to resolve the way there is domestically.
     *
     * Both start with no zone assigned when generated — staff fill them
     * in via the inline picker on each row.
     */
    public function index(Request $request): View
    {
        $domesticMappings = ZoneMapping::with(['stateA.territory', 'stateB.territory', 'zone'])
            ->orderBy('state_a_id')
            ->orderBy('state_b_id')
            ->paginate(20, ['*'], 'domestic_page');

        $internationalMappings = ZoneCountryMapping::with(['countryA', 'countryB.countryRegion', 'zone'])
            ->orderBy('country_b_id')
            ->paginate(20, ['*'], 'international_page');

        // Not paginated — deliberately a short, manually-curated list
        // (see the migration's comment), not a bulk-generated set.
        $thirdPartyMappings = ThirdPartyCountryMapping::with(['countryA', 'countryB', 'zone'])
            ->orderBy('country_a_id')
            ->get();

        return view('zone-mappings.index', [
            'domesticMappings' => $domesticMappings,
            'internationalMappings' => $internationalMappings,
            'thirdPartyMappings' => $thirdPartyMappings,
            'domesticZones' => Zone::where('applies_domestic', true)->orderBy('name')->get(),
            'internationalZones' => Zone::where('applies_international', true)->orderBy('name')->get(),
            'countries' => Country::orderBy('name')->get(),
        ]);
    }

    /**
     * Every newly generated pair is pre-assigned a zone using
     * ZoneMapping::determineDefaultZoneTier() (same state / same
     * territory / territory-to-territory with or without an airport on
     * both sides) — staff can still reassign any individual pair
     * afterward via the inline picker. Pairs that already exist (already
     * manually assigned or from a previous generation) are never
     * touched, regardless of what the rule would now compute for them.
     *
     * Also generates one self-pair per state (state_a_id = state_b_id),
     * representing a shipment that stays within the same state —
     * previously impossible to price at all, since the cross-state loop
     * below only ever pairs two DIFFERENT states and nothing filled the
     * gap. determineDefaultZoneTier() already always returns tier 1 for
     * a state paired with itself; this just makes sure that row actually
     * gets created so it's settable (and resolvable by PricingEngine).
     */
    public function generateDomestic(): RedirectResponse
    {
        $nigeria = Country::where('code', 'NG')->first();

        if (! $nigeria) {
            return back()->withErrors(['country' => 'Nigeria isn\'t set up under Setups → Location → Countries yet.']);
        }

        $states = State::where('country_id', $nigeria->id)->get()->values();
        $defaultZones = Zone::ensureDefaultZones();
        $created = 0;

        foreach ($states as $state) {
            $mapping = ZoneMapping::firstOrCreate(
                ['state_a_id' => $state->id, 'state_b_id' => $state->id],
                ['zone_id' => $defaultZones[ZoneMapping::determineDefaultZoneTier($state, $state)]->id]
            );
            $created += $mapping->wasRecentlyCreated ? 1 : 0;
        }

        for ($i = 0; $i < $states->count(); $i++) {
            for ($j = $i + 1; $j < $states->count(); $j++) {
                $stateA = $states[$i];
                $stateB = $states[$j];
                [$a, $b] = $stateA->id < $stateB->id ? [$stateA->id, $stateB->id] : [$stateB->id, $stateA->id];

                $tier = ZoneMapping::determineDefaultZoneTier($stateA, $stateB);

                $mapping = ZoneMapping::firstOrCreate(
                    ['state_a_id' => $a, 'state_b_id' => $b],
                    ['zone_id' => $defaultZones[$tier]->id]
                );
                $created += $mapping->wasRecentlyCreated ? 1 : 0;
            }
        }

        return redirect()->route('zone-mappings.index')->with('status', "Domestic combinations generated ({$created} new).");
    }

    /**
     * A bulk version of determineDefaultZoneTier() — instead of the
     * fixed rule only ever applying to brand-new pairs at generation
     * time, this lets staff pick which Zone applies to each of the four
     * conditions (same state / same territory / different territory
     * with the airport condition met / different territory without),
     * including choosing whether the airport condition needs BOTH
     * states to have one or just EITHER, and then applies that rule to
     * EVERY existing domestic pair at once — overwriting whatever zone
     * was there before, including manual overrides. This is
     * deliberately destructive and bulk; the confirm dialog on the
     * button is the only guard, so use it as a reset, not a routine
     * action. Individual rows can still be adjusted afterward via the
     * inline picker exactly as before.
     */
    public function applyDomesticRule(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'zone_same_state' => 'required|exists:zones,id',
            'zone_same_territory' => 'required|exists:zones,id',
            'zone_airport_condition_met' => 'required|exists:zones,id',
            'zone_airport_condition_not_met' => 'required|exists:zones,id',
            'airport_condition' => 'required|in:both,either',
        ]);

        $zoneByTier = [
            1 => $validated['zone_same_state'],
            2 => $validated['zone_same_territory'],
            3 => $validated['zone_airport_condition_met'],
            4 => $validated['zone_airport_condition_not_met'],
        ];

        $updated = 0;

        ZoneMapping::with(['stateA', 'stateB'])->chunkById(200, function ($mappings) use (&$updated, $zoneByTier, $validated) {
            foreach ($mappings as $mapping) {
                $tier = $this->tierForCustomRule($mapping->stateA, $mapping->stateB, $validated['airport_condition']);
                $mapping->update(['zone_id' => $zoneByTier[$tier]]);
                $updated++;
            }
        });

        return redirect()->route('zone-mappings.index')->with('status', "Rule applied to {$updated} domestic mappings.");
    }

    /**
     * Same shape as ZoneMapping::determineDefaultZoneTier(), except the
     * airport condition is a parameter here rather than hardcoded to
     * "both" — 'either' means only one of the two states needs an
     * airport for the condition to count as met.
     */
    private function tierForCustomRule(State $stateA, State $stateB, string $airportCondition): int
    {
        if ($stateA->id === $stateB->id) {
            return 1;
        }

        if ($stateA->territory_id && $stateA->territory_id === $stateB->territory_id) {
            return 2;
        }

        $airportMet = $airportCondition === 'both'
            ? ($stateA->has_airport && $stateB->has_airport)
            : ($stateA->has_airport || $stateB->has_airport);

        return $airportMet ? 3 : 4;
    }

    /**
     * Same idempotency guarantee as generateDomestic — safe to re-run
     * after adding a new country.
     */
    public function generateInternational(): RedirectResponse
    {
        $nigeria = Country::where('code', 'NG')->first();

        if (! $nigeria) {
            return back()->withErrors(['country' => 'Nigeria isn\'t set up under Setups → Location → Countries yet.']);
        }

        $created = 0;

        foreach (Country::where('code', '!=', 'NG')->get() as $country) {
            $mapping = ZoneCountryMapping::firstOrCreate(['country_a_id' => $nigeria->id, 'country_b_id' => $country->id]);
            $created += $mapping->wasRecentlyCreated ? 1 : 0;
        }

        return redirect()->route('zone-mappings.index')->with('status', "International countries generated ({$created} new).");
    }

    /**
     * The international counterpart to applyDomesticRule() — every
     * comparison is against NIGERIA specifically (always the fixed
     * origin side for international shipments in this system, unlike
     * the domestic rule which compares two arbitrary states).
     *
     * Two selectable grouping methods:
     *   'continent'        — 2-tier: same continent as Nigeria, or not.
     *   'continent_region'  — 3-tier (default/recommended): same Country
     *                          Region as Nigeria, same continent but a
     *                          different region, or a different
     *                          continent entirely. Country Region is
     *                          staff-defined (Setups → Location →
     *                          Country Regions), so this also covers a
     *                          proximity-based framing ("Bordering
     *                          Nigeria") if that's how regions end up
     *                          named — same mechanism either way.
     *
     * Same destructive/bulk posture as applyDomesticRule(): overwrites
     * every existing international mapping, confirmed before running.
     */
    public function applyInternationalRule(Request $request): RedirectResponse
    {
        $nigeria = Country::where('code', 'NG')->first();

        if (! $nigeria) {
            return back()->withErrors(['country' => 'Nigeria isn\'t set up under Setups → Location → Countries yet.']);
        }

        $method = $request->input('grouping_method', 'continent_region');

        if ($method === 'continent') {
            $validated = $request->validate([
                'zone_same_continent' => 'required|exists:zones,id',
                'zone_different_continent' => 'required|exists:zones,id',
            ]);
        } else {
            $validated = $request->validate([
                'zone_same_region' => 'required|exists:zones,id',
                'zone_same_continent_different_region' => 'required|exists:zones,id',
                'zone_different_continent' => 'required|exists:zones,id',
            ]);
        }

        $updated = 0;

        ZoneCountryMapping::with('country')->chunkById(100, function ($mappings) use (&$updated, $method, $validated, $nigeria) {
            foreach ($mappings as $mapping) {
                $country = $mapping->country;
                $sameContinent = $country->continent && $country->continent === $nigeria->continent;

                if ($method === 'continent') {
                    $zoneId = $sameContinent ? $validated['zone_same_continent'] : $validated['zone_different_continent'];
                } else {
                    $sameRegion = $country->country_region_id && $country->country_region_id === $nigeria->country_region_id;

                    $zoneId = match (true) {
                        $sameRegion => $validated['zone_same_region'],
                        $sameContinent => $validated['zone_same_continent_different_region'],
                        default => $validated['zone_different_continent'],
                    };
                }

                $mapping->update(['zone_id' => $zoneId]);
                $updated++;
            }
        });

        return redirect()->route('zone-mappings.index')->with('status', "Rule applied to {$updated} international mappings.");
    }

    public function updateZone(Request $request, ZoneMapping $zoneMapping): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'zone_id' => 'nullable|exists:zones,id',
        ]);

        $validator->validate();
        $data = $validator->validated();

        $zoneMapping->update(['zone_id' => $data['zone_id'] ?: null]);

        return back()->with('status', 'Zone updated.');
    }

    public function updateCountryZone(Request $request, ZoneCountryMapping $zoneCountryMapping): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'zone_id' => 'nullable|exists:zones,id',
        ]);

        $validator->validate();
        $data = $validator->validated();

        $zoneCountryMapping->update(['zone_id' => $data['zone_id'] ?: null]);

        return back()->with('status', 'Zone updated.');
    }

    /**
     * Adds one third-party route — Country A, Country B, Zone. No bulk
     * generation, no bulk rule; each route is added deliberately, one
     * at a time, matching how occasionally this actually comes up.
     * A duplicate pair (in either order, thanks to the model's
     * normalization) updates the existing row's zone instead of
     * erroring — the unique constraint would otherwise reject it.
     */
    public function storeThirdParty(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'country_a_id' => 'required|exists:countries,id|different:country_b_id',
            'country_b_id' => 'required|exists:countries,id',
            'zone_id' => 'nullable|exists:zones,id',
        ]);

        $data = $validator->validate();

        [$a, $b] = $data['country_a_id'] <= $data['country_b_id']
            ? [$data['country_a_id'], $data['country_b_id']]
            : [$data['country_b_id'], $data['country_a_id']];

        ThirdPartyCountryMapping::updateOrCreate(
            ['country_a_id' => $a, 'country_b_id' => $b],
            ['zone_id' => $data['zone_id'] ?: null]
        );

        return back()->with('status', 'Third-party route saved.');
    }

    /**
     * Generates every pair among the SELECTED countries only — not all
     * ~178 countries, which would mean over 15,000 possible pairs for a
     * business relationship that's occasional, not routine. Staff pick
     * which countries they actually cross-trade with here; this creates
     * one row per pair among just that set, unassigned unless a pair
     * already exists (untouched, same idempotency as generateDomestic).
     */
    public function generateThirdParty(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'country_ids' => 'required|array|min:2',
            'country_ids.*' => 'exists:countries,id',
        ]);

        $countryIds = array_values(array_unique($validated['country_ids']));
        $created = 0;

        for ($i = 0; $i < count($countryIds); $i++) {
            for ($j = $i + 1; $j < count($countryIds); $j++) {
                [$a, $b] = $countryIds[$i] <= $countryIds[$j]
                    ? [$countryIds[$i], $countryIds[$j]]
                    : [$countryIds[$j], $countryIds[$i]];

                $mapping = ThirdPartyCountryMapping::firstOrCreate(['country_a_id' => $a, 'country_b_id' => $b]);
                $created += $mapping->wasRecentlyCreated ? 1 : 0;
            }
        }

        return back()->with('status', "Cross-trade combinations generated ({$created} new).");
    }

    /**
     * Same shape as applyInternationalRule(), except every comparison is
     * between the pair's own two countries — neither is fixed as
     * Nigeria the way the international rule is, since a cross-trade
     * route genuinely doesn't involve Nigeria on either side.
     */
    public function applyThirdPartyRule(Request $request): RedirectResponse
    {
        $method = $request->input('grouping_method', 'continent_region');

        if ($method === 'continent') {
            $validated = $request->validate([
                'zone_same_continent' => 'required|exists:zones,id',
                'zone_different_continent' => 'required|exists:zones,id',
            ]);
        } else {
            $validated = $request->validate([
                'zone_same_region' => 'required|exists:zones,id',
                'zone_same_continent_different_region' => 'required|exists:zones,id',
                'zone_different_continent' => 'required|exists:zones,id',
            ]);
        }

        $updated = 0;

        ThirdPartyCountryMapping::with(['countryA', 'countryB'])->chunkById(100, function ($mappings) use (&$updated, $method, $validated) {
            foreach ($mappings as $mapping) {
                $countryA = $mapping->countryA;
                $countryB = $mapping->countryB;
                $sameContinent = $countryA->continent && $countryA->continent === $countryB->continent;

                if ($method === 'continent') {
                    $zoneId = $sameContinent ? $validated['zone_same_continent'] : $validated['zone_different_continent'];
                } else {
                    $sameRegion = $countryA->country_region_id && $countryA->country_region_id === $countryB->country_region_id;

                    $zoneId = match (true) {
                        $sameRegion => $validated['zone_same_region'],
                        $sameContinent => $validated['zone_same_continent_different_region'],
                        default => $validated['zone_different_continent'],
                    };
                }

                $mapping->update(['zone_id' => $zoneId]);
                $updated++;
            }
        });

        return back()->with('status', "Rule applied to {$updated} cross-trade routes.");
    }

    public function updateThirdPartyZone(Request $request, ThirdPartyCountryMapping $thirdPartyCountryMapping): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'zone_id' => 'nullable|exists:zones,id',
        ]);

        $data = $validator->validate();

        $thirdPartyCountryMapping->update(['zone_id' => $data['zone_id'] ?: null]);

        return back()->with('status', 'Zone updated.');
    }

    public function destroyThirdParty(ThirdPartyCountryMapping $thirdPartyCountryMapping): RedirectResponse
    {
        $thirdPartyCountryMapping->delete();

        return back()->with('status', 'Third-party route removed.');
    }

    /**
     * Exports every domestic pair, assigned or not — the whole ~666-row
     * set is exactly what makes hand-editing this screen slow, so this
     * is the highest-value CSV round trip in the whole app: download,
     * fill in the zone column in a spreadsheet, re-upload.
     */
    public function exportDomestic(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $rows = ZoneMapping::with(['stateA', 'stateB', 'zone'])
            ->orderBy('state_a_id')->orderBy('state_b_id')
            ->get()
            ->map(fn ($m) => [$m->stateA->code, $m->stateB->code, $m->zone?->code]);

        return $this->csv->download('domestic-zone-mapping.csv', ['state_a_code', 'state_b_code', 'zone_code'], $rows);
    }

    public function importDomestic(Request $request): RedirectResponse
    {
        $request->validate(['file' => 'required|file|mimes:csv,txt']);

        $rows = $this->csv->parse($request->file('file'));
        $count = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $stateA = State::where('code', strtoupper(trim($row['state_a_code'] ?? '')))->first();
            $stateB = State::where('code', strtoupper(trim($row['state_b_code'] ?? '')))->first();

            if (! $stateA || ! $stateB) {
                $skipped++;
                continue;
            }

            // Blank zone_code leaves the pair unassigned rather than
            // skipping the row entirely — useful for a CSV that's
            // pre-populating pairs before anyone's decided their zones.
            $zone = ! empty($row['zone_code'])
                ? Zone::where('code', strtoupper(trim($row['zone_code'])))->first()
                : null;

            [$a, $b] = $stateA->id < $stateB->id ? [$stateA->id, $stateB->id] : [$stateB->id, $stateA->id];

            ZoneMapping::updateOrCreate(
                ['state_a_id' => $a, 'state_b_id' => $b],
                ['zone_id' => $zone?->id]
            );
            $count++;
        }

        return redirect()->route('zone-mappings.index')->with('status', "Imported {$count} domestic mappings" . ($skipped ? ", skipped {$skipped} (unknown state code)." : '.'));
    }

    public function exportInternational(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $rows = ZoneCountryMapping::with(['country', 'zone'])
            ->orderBy('country_b_id')
            ->get()
            ->map(fn ($m) => [$m->country->code, $m->zone?->code]);

        return $this->csv->download('international-zone-mapping.csv', ['country_code', 'zone_code'], $rows);
    }

    public function importInternational(Request $request): RedirectResponse
    {
        $request->validate(['file' => 'required|file|mimes:csv,txt']);

        $nigeria = Country::where('code', 'NG')->first();

        if (! $nigeria) {
            return back()->withErrors(['country' => 'Nigeria isn\'t set up under Setups → Location → Countries yet.']);
        }

        $rows = $this->csv->parse($request->file('file'));
        $count = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $country = Country::where('code', strtoupper(trim($row['country_code'] ?? '')))->first();

            if (! $country) {
                $skipped++;
                continue;
            }

            $zone = ! empty($row['zone_code'])
                ? Zone::where('code', strtoupper(trim($row['zone_code'])))->first()
                : null;

            ZoneCountryMapping::updateOrCreate(
                ['country_a_id' => $nigeria->id, 'country_b_id' => $country->id],
                ['zone_id' => $zone?->id]
            );
            $count++;
        }

        return redirect()->route('zone-mappings.index')->with('status', "Imported {$count} international mappings" . ($skipped ? ", skipped {$skipped} (unknown country code)." : '.'));
    }
}
