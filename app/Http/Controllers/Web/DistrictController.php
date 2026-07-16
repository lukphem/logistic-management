<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\District;
use App\Models\OnforwardingClassification;
use App\Models\State;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class DistrictController extends Controller
{
    public function index(Request $request): View
    {
        $query = District::with(['city.state.country', 'onforwardingClassification']);

        if ($request->filled('city_id')) {
            $query->where('city_id', $request->city_id);
        } elseif ($request->filled('state_id')) {
            $query->whereHas('city', fn ($q) => $q->where('state_id', $request->state_id));
        }

        $districts = $query->orderBy('name')->paginate(15)->withQueryString();

        return view('districts.index', [
            'districts' => $districts,
            'states' => State::orderBy('name')->get(),
            'cities' => City::with('state')->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('districts.form', [
            'district' => new District(),
            'cities' => City::with('state.country')->orderBy('name')->get(),
            'classifications' => OnforwardingClassification::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        District::create($this->validated($request));

        return redirect()->route('districts.index')->with('status', 'District/area added.');
    }

    public function edit(District $district): View
    {
        return view('districts.form', [
            'district' => $district,
            'cities' => City::with('state.country')->orderBy('name')->get(),
            'classifications' => OnforwardingClassification::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, District $district): RedirectResponse
    {
        $district->update($this->validated($request, $district->id));

        return redirect()->route('districts.index')->with('status', 'District/area updated.');
    }

    public function destroy(District $district): RedirectResponse
    {
        $district->delete();

        return redirect()->route('districts.index')->with('status', 'District/area removed.');
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        $validator = Validator::make($request->all(), [
            'city_id' => 'required|exists:cities,id',
            'name' => 'required|string|max:255',
            'short_code' => [
                'required', 'string', 'max:10',
                Rule::unique('districts', 'short_code')
                    ->where('city_id', $request->city_id)
                    ->ignore($ignoreId),
            ],
            'onforwarding_classification_id' => 'nullable|exists:onforwarding_classifications,id',
        ]);

        $validator->validate();
        $data = $validator->validated();

        // Blank "No classification" option submits as an empty string,
        // not null — normalize immediately, the same class of bug fixed
        // in Increment 20.
        if (($data['onforwarding_classification_id'] ?? null) === '') {
            $data['onforwarding_classification_id'] = null;
        }

        return $data;
    }
}
