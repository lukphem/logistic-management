<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\State;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class CityController extends Controller
{
    public function index(): View
    {
        $cities = City::with('state.country')->orderBy('name')->paginate(15);

        return view('cities.index', compact('cities'));
    }

    public function create(): View
    {
        return view('cities.form', ['city' => new City(), 'states' => State::with('country')->orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        City::create($this->validated($request));

        return redirect()->route('cities.index')->with('status', 'City added.');
    }

    public function edit(City $city): View
    {
        return view('cities.form', ['city' => $city, 'states' => State::with('country')->orderBy('name')->get()]);
    }

    public function update(Request $request, City $city): RedirectResponse
    {
        $city->update($this->validated($request));

        return redirect()->route('cities.index')->with('status', 'City updated.');
    }

    public function destroy(City $city): RedirectResponse
    {
        $city->delete();

        return redirect()->route('cities.index')->with('status', 'City removed.');
    }

    private function validated(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'state_id' => 'required|exists:states,id',
            'name' => 'required|string|max:255',
        ]);

        $validator->validate();

        return $validator->validated();
    }
}
