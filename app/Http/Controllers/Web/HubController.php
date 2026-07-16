<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Hub;
use App\Models\Region;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class HubController extends Controller
{
    public function index(): View
    {
        $hubs = Hub::with(['region', 'city.state.country'])->withCount('zones')->orderBy('name')->paginate(15);

        return view('hubs.index', compact('hubs'));
    }

    public function create(): View
    {
        return view('hubs.form', [
            'hub' => new Hub(),
            'regions' => Region::orderBy('name')->get(),
            'cities' => City::with('state.country')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        Hub::create($data);

        return redirect()->route('hubs.index')->with('status', 'Hub added.');
    }

    public function edit(Hub $hub): View
    {
        return view('hubs.form', [
            'hub' => $hub,
            'regions' => Region::orderBy('name')->get(),
            'cities' => City::with('state.country')->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Hub $hub): RedirectResponse
    {
        $hub->update($this->validated($request));

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
        ]);

        $validator->validate();

        $data = $validator->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        return $data;
    }
}
