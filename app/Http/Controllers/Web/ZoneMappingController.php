<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\RateCard;
use App\Models\Zone;
use App\Models\ZoneRateMatrix;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class ZoneMappingController extends Controller
{
    /**
     * Surfaces ZoneRateMatrix (Increment 2) in its own screen instead of
     * only being reachable from inside a specific zone_to_zone rate
     * card's edit page — a centralized view across every rate card, since
     * staff configuring pricing shouldn't need to remember which rate
     * card a given origin/destination pair lives under.
     */
    public function index(Request $request): View
    {
        $query = ZoneRateMatrix::with(['rateCard', 'originZone', 'destinationZone']);

        if ($request->filled('rate_card_id')) {
            $query->where('rate_card_id', $request->rate_card_id);
        }

        $mappings = $query->orderBy('rate_card_id')->paginate(20)->withQueryString();

        return view('zone-mappings.index', [
            'mappings' => $mappings,
            'rateCards' => RateCard::where('billing_model', 'zone_to_zone')->orderBy('name')->get(),
            'zones' => Zone::orderBy('name')->get(),
        ]);
    }

    /**
     * Shares the same underlying upsert as
     * RateCardController::setZonePrice — this is just a second entry
     * point into the same operation, not a separate implementation.
     */
    public function store(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'rate_card_id' => 'required|exists:rate_cards,id',
            'origin_zone_id' => 'required|exists:zones,id',
            'destination_zone_id' => 'required|exists:zones,id',
            'price' => 'required|numeric|min:0',
        ]);

        $validator->validate();
        $data = $validator->validated();

        ZoneRateMatrix::updateOrCreate(
            [
                'rate_card_id' => $data['rate_card_id'],
                'origin_zone_id' => $data['origin_zone_id'],
                'destination_zone_id' => $data['destination_zone_id'],
            ],
            ['price' => $data['price']]
        );

        return redirect()->route('zone-mappings.index')->with('status', 'Zone mapping saved.');
    }

    public function destroy(ZoneRateMatrix $zoneMapping): RedirectResponse
    {
        $zoneMapping->delete();

        return redirect()->route('zone-mappings.index')->with('status', 'Zone mapping removed.');
    }
}
