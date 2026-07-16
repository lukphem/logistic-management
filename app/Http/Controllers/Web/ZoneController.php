<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Hub;
use App\Models\Zone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class ZoneController extends Controller
{
    public function index(): View
    {
        $zones = Zone::with('hub')->orderBy('name')->paginate(15);

        return view('zones.index', compact('zones'));
    }

    public function create(): View
    {
        $hubs = Hub::orderBy('name')->get();

        return view('zones.form', ['zone' => new Zone(), 'hubs' => $hubs]);
    }

    public function store(Request $request): RedirectResponse
    {
        Zone::create($this->validated($request));

        return redirect()->route('zones.index')->with('status', 'Zone added.');
    }

    public function edit(Zone $zone): View
    {
        $hubs = Hub::orderBy('name')->get();

        return view('zones.form', compact('zone', 'hubs'));
    }

    public function update(Request $request, Zone $zone): RedirectResponse
    {
        $zone->update($this->validated($request));

        return redirect()->route('zones.index')->with('status', 'Zone updated.');
    }

    public function destroy(Zone $zone): RedirectResponse
    {
        $zone->delete();

        return redirect()->route('zones.index')->with('status', 'Zone removed.');
    }

    private function validated(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:zones,code,' . $request->route('zone')?->id,
            'hub_id' => 'nullable|exists:hubs,id',
        ]);

        $data = $validator->validate();

        // Blank "— None —" option submits as an empty string, not null —
        // see the fuller explanation in UserController's matching fix.
        if (($data['hub_id'] ?? null) === '') {
            $data['hub_id'] = null;
        }

        return $data;
    }
}
