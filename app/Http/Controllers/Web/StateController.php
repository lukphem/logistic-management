<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\State;
use App\Models\Territory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class StateController extends Controller
{
    public function index(Request $request): View
    {
        $query = State::with('country')->withCount('cities');

        if ($request->filled('country_id')) {
            $query->where('country_id', $request->country_id);
        }

        $states = $query->orderBy('name')->paginate(15)->withQueryString();

        return view('states.index', [
            'states' => $states,
            'countries' => Country::orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('states.form', [
            'state' => new State(),
            'countries' => Country::orderBy('name')->get(),
            'territories' => Territory::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        State::create($this->validated($request));

        return redirect()->route('states.index')->with('status', 'State/province added.');
    }

    public function edit(State $state): View
    {
        return view('states.form', [
            'state' => $state,
            'countries' => Country::orderBy('name')->get(),
            'territories' => Territory::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, State $state): RedirectResponse
    {
        $state->update($this->validated($request, $state->id));

        return redirect()->route('states.index')->with('status', 'State/province updated.');
    }

    public function destroy(State $state): RedirectResponse
    {
        $state->delete();

        return redirect()->route('states.index')->with('status', 'State/province removed.');
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        $validator = Validator::make($request->all(), [
            'country_id' => 'required|exists:countries,id',
            'name' => 'required|string|max:255',
            // Uniqueness scoped to the country, not global — "LA" can be
            // reused as a short_code in a different country without
            // colliding, since the composed code (country prefix) is what
            // actually needs to be unique.
            'short_code' => [
                'required', 'string', 'max:10',
                Rule::unique('states', 'short_code')
                    ->where('country_id', $request->country_id)
                    ->ignore($ignoreId),
            ],
            'postal_code' => 'nullable|string|max:20',
            'territory_id' => 'nullable|exists:territories,id',
            'has_airport' => 'sometimes|boolean',
        ]);

        $validator->validate();
        $data = $validator->validated();

        $data['has_airport'] = $request->boolean('has_airport');

        // Blank "No territory" option submits as an empty string, not
        // null — same class of bug fixed in Increment 20.
        if (($data['territory_id'] ?? null) === '') {
            $data['territory_id'] = null;
        }

        return $data;
    }
}
