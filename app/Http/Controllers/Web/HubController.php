<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Hub;
use App\Models\Region;
use App\Models\State;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class HubController extends Controller
{
    public function index(): View
    {
        $hubs = Hub::with(['region', 'city.state.country'])->withCount(['zones', 'states'])->orderBy('name')->paginate(15);

        return view('hubs.index', compact('hubs'));
    }

    public function create(): View
    {
        return view('hubs.form', [
            'hub' => new Hub(),
            'regions' => Region::orderBy('name')->get(),
            'cities' => City::with('state.country')->orderBy('name')->get(),
            'states' => State::with('country')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $stateIds = $data['state_ids'] ?? [];
        unset($data['state_ids']);

        $hub = Hub::create($data);
        $hub->states()->sync($stateIds);

        return redirect()->route('hubs.index')->with('status', 'Hub added.');
    }

    public function edit(Hub $hub): View
    {
        return view('hubs.form', [
            'hub' => $hub->load('states'),
            'regions' => Region::orderBy('name')->get(),
            'cities' => City::with('state.country')->orderBy('name')->get(),
            'states' => State::with('country')->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Hub $hub): RedirectResponse
    {
        $data = $this->validated($request);
        $stateIds = $data['state_ids'] ?? [];
        unset($data['state_ids']);

        $hub->update($data);
        $hub->states()->sync($stateIds);

        return redirect()->route('hubs.index')->with('status', 'Hub updated.');
    }

    public function destroy(Hub $hub): RedirectResponse
    {
        $hub->delete();

        return redirect()->route('hubs.index')->with('status', 'Hub removed.');
    }

    private function validated(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:hubs,code,' . $request->route('hub')?->id,
            'region_id' => 'nullable|exists:regions,id',
            'city_id' => 'nullable|exists:cities,id',
            'address' => 'required|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'is_active' => 'sometimes|boolean',
            // The states this hub operationally picks up from/delivers
            // to — separate from city_id, which is just its home location.
            'state_ids' => 'nullable|array',
            'state_ids.*' => 'exists:states,id',
        ]);

        $validator->validate();

        $data = $validator->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        // HTML blank options ("No region", "No city set") submit as an
        // empty string, not null — and validated() passes that straight
        // through. Inserting '' into a nullable foreign key column fails
        // the FK constraint, silently breaking the whole save. See the
        // matching fix and fuller explanation in UserController.
        foreach (['region_id', 'city_id'] as $field) {
            if (($data[$field] ?? null) === '') {
                $data[$field] = null;
            }
        }

        return $data;
    }
}
