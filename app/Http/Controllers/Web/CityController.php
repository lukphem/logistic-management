<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Country;
use App\Models\Hub;
use App\Models\OnforwardingClassification;
use App\Models\State;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class CityController extends Controller
{
    public function index(Request $request): View
    {
        $query = City::with(['state.country', 'operationalHub', 'onforwardingClassification']);

        if ($request->filled('state_id')) {
            $query->where('state_id', $request->state_id);
        } elseif ($request->filled('country_id')) {
            $query->whereHas('state', fn ($q) => $q->where('country_id', $request->country_id));
        }

        $cities = $query->orderBy('name')->paginate(15)->withQueryString();

        return view('cities.index', [
            'cities' => $cities,
            'countries' => Country::orderBy('name')->get(),
            'states' => State::with('country')->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('cities.form', [
            'city' => new City(),
            'states' => State::with('country')->orderBy('name')->get(),
            'hubs' => Hub::with('city.state')->orderBy('name')->get(),
            'classifications' => OnforwardingClassification::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        City::create($this->validated($request));

        return redirect()->route('cities.index')->with('status', 'City added.');
    }

    public function edit(City $city): View
    {
        return view('cities.form', [
            'city' => $city,
            'states' => State::with('country')->orderBy('name')->get(),
            'hubs' => Hub::with('city.state')->orderBy('name')->get(),
            'classifications' => OnforwardingClassification::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, City $city): RedirectResponse
    {
        $city->update($this->validated($request, $city->id));

        return redirect()->route('cities.index')->with('status', 'City updated.');
    }

    public function destroy(City $city): RedirectResponse
    {
        $city->delete();

        return redirect()->route('cities.index')->with('status', 'City removed.');
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        $validator = Validator::make($request->all(), [
            'state_id' => 'required|exists:states,id',
            'name' => 'required|string|max:255',
            'short_code' => [
                'required', 'string', 'max:10',
                Rule::unique('cities', 'short_code')
                    ->where('state_id', $request->state_id)
                    ->ignore($ignoreId),
            ],
            // Optional — only needed to disambiguate when the city's
            // state is covered by more than one hub. See City::operationalHub().
            'operational_hub_id' => 'nullable|exists:hubs,id',
            'onforwarding_classification_id' => 'nullable|exists:onforwarding_classifications,id',
            'postal_code' => 'nullable|string|max:20',
        ]);

        $validator->validate();
        $data = $validator->validated();

        // Blank "No specific hub"/"No classification" options submit as
        // an empty string, not null — normalize immediately, the same
        // class of bug fixed in Increment 20.
        foreach (['operational_hub_id', 'onforwarding_classification_id'] as $field) {
            if (($data[$field] ?? null) === '') {
                $data[$field] = null;
            }
        }

        return $data;
    }
}
