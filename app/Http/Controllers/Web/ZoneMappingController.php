<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\State;
use App\Models\Zone;
use App\Models\ZoneMapping;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class ZoneMappingController extends Controller
{
    /**
     * Assigns a route between two STATES to a Zone — e.g. "Abuja to
     * Lagos = Zone 2." One row covers the route both ways (see
     * ZoneMapping::resolveZone / its saving hook, which always stores
     * the lower state ID first) — "Lagos to Abuja" is the same mapping,
     * not a second row.
     *
     * That single per-pair assignment is what lets the
     * origin_destination_weight rate table (managed on each rate card's
     * own edit page) resolve pricing for any shipment between two mapped
     * states.
     *
     * The older zone-to-zone price matrix (ZoneRateMatrix, for the
     * 'zone_to_zone' billing model) is unaffected by this — it's managed
     * from each zone_to_zone rate card's own edit page, same as before
     * this screen existed.
     */
    public function index(Request $request): View
    {
        $query = ZoneMapping::with(['stateA.country', 'stateB.country', 'zone']);

        if ($request->filled('zone_id')) {
            $query->where('zone_id', $request->zone_id);
        }

        $mappings = $query->orderBy('zone_id')->paginate(20)->withQueryString();

        return view('zone-mappings.index', [
            'mappings' => $mappings,
            'zones' => Zone::orderBy('name')->get(),
            'states' => State::with('country')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'state_a_id' => 'required|exists:states,id|different:state_b_id',
            'state_b_id' => 'required|exists:states,id',
            'zone_id' => 'required|exists:zones,id',
        ]);

        $validator->validate();
        $data = $validator->validated();

        // Normalize order before the upsert lookup too — otherwise
        // saving "Lagos, Abuja" when "Abuja, Lagos" already exists would
        // create a second row instead of updating the first, since the
        // model's saving-hook normalization only reorders the values
        // being saved, not the WHERE clause used to find an existing row.
        [$a, $b] = $data['state_a_id'] <= $data['state_b_id']
            ? [$data['state_a_id'], $data['state_b_id']]
            : [$data['state_b_id'], $data['state_a_id']];

        ZoneMapping::updateOrCreate(
            ['state_a_id' => $a, 'state_b_id' => $b],
            ['zone_id' => $data['zone_id']]
        );

        return redirect()->route('zone-mappings.index')->with('status', 'Route assigned to zone.');
    }

    public function destroy(ZoneMapping $zoneMapping): RedirectResponse
    {
        $zoneMapping->delete();

        return redirect()->route('zone-mappings.index')->with('status', 'Zone assignment removed.');
    }
}
