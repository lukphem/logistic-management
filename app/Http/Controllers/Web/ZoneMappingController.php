<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\State;
use App\Models\Zone;
use App\Models\ZoneCountryMapping;
use App\Models\ZoneMapping;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class ZoneMappingController extends Controller
{
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
        $domesticMappings = ZoneMapping::with(['stateA', 'stateB', 'zone'])
            ->orderBy('state_a_id')
            ->orderBy('state_b_id')
            ->paginate(20, ['*'], 'domestic_page');

        $internationalMappings = ZoneCountryMapping::with(['country', 'zone'])
            ->orderBy('country_id')
            ->paginate(20, ['*'], 'international_page');

        return view('zone-mappings.index', [
            'domesticMappings' => $domesticMappings,
            'internationalMappings' => $internationalMappings,
            'zones' => Zone::orderBy('name')->get(),
        ]);
    }

    /**
     * Idempotent — safe to run again later (e.g. after a new state is
     * added under Setups → Location) since it only creates pairs that
     * don't already exist, never touching zones already assigned to an
     * existing pair.
     */
    /**
     * Every newly generated pair is pre-assigned a zone using
     * ZoneMapping::determineDefaultZoneTier() (same state / same
     * territory / territory-to-territory with or without an airport on
     * both sides) — staff can still reassign any individual pair
     * afterward via the inline picker. Pairs that already exist (already
     * manually assigned or from a previous generation) are never
     * touched, regardless of what the rule would now compute for them.
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
     * Same idempotency guarantee as generateDomestic — safe to re-run
     * after adding a new country.
     */
    public function generateInternational(): RedirectResponse
    {
        $created = 0;

        foreach (Country::where('code', '!=', 'NG')->get() as $country) {
            $mapping = ZoneCountryMapping::firstOrCreate(['country_id' => $country->id]);
            $created += $mapping->wasRecentlyCreated ? 1 : 0;
        }

        return redirect()->route('zone-mappings.index')->with('status', "International countries generated ({$created} new).");
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
}
