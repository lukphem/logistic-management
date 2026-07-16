<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Zone;
use App\Models\ZoneMapping;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class ZoneMappingController extends Controller
{
    /**
     * Assigns each city to a Zone (e.g. "Port Harcourt = Zone 2") — one
     * row per city, not per city pair. That single assignment is what
     * lets the origin_destination_weight rate table (managed on each
     * rate card's own edit page) resolve pricing for ANY route between
     * two mapped cities, without needing a row for every possible pair.
     *
     * The older zone-to-zone price matrix (ZoneRateMatrix, for the
     * 'zone_to_zone' billing model) is unaffected by this — it's managed
     * from each zone_to_zone rate card's own edit page, same as before
     * this screen existed.
     */
    public function index(Request $request): View
    {
        $query = ZoneMapping::with('city.state.country', 'zone');

        if ($request->filled('zone_id')) {
            $query->where('zone_id', $request->zone_id);
        }

        $mappings = $query->orderBy('zone_id')->paginate(20)->withQueryString();

        return view('zone-mappings.index', [
            'mappings' => $mappings,
            'zones' => Zone::orderBy('name')->get(),
            'cities' => City::with('state.country')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'city_id' => 'required|exists:cities,id',
            'zone_id' => 'required|exists:zones,id',
        ]);

        $validator->validate();
        $data = $validator->validated();

        ZoneMapping::updateOrCreate(
            ['city_id' => $data['city_id']],
            ['zone_id' => $data['zone_id']]
        );

        return redirect()->route('zone-mappings.index')->with('status', 'City assigned to zone.');
    }

    public function destroy(ZoneMapping $zoneMapping): RedirectResponse
    {
        $zoneMapping->delete();

        return redirect()->route('zone-mappings.index')->with('status', 'Zone assignment removed.');
    }
}
