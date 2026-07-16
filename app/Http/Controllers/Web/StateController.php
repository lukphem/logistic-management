<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\State;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class StateController extends Controller
{
    public function index(): View
    {
        $states = State::with('country')->withCount('cities')->orderBy('name')->paginate(15);

        return view('states.index', compact('states'));
    }

    public function create(): View
    {
        return view('states.form', ['state' => new State(), 'countries' => Country::orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        State::create($this->validated($request));

        return redirect()->route('states.index')->with('status', 'State/province added.');
    }

    public function edit(State $state): View
    {
        return view('states.form', ['state' => $state, 'countries' => Country::orderBy('name')->get()]);
    }

    public function update(Request $request, State $state): RedirectResponse
    {
        $state->update($this->validated($request));

        return redirect()->route('states.index')->with('status', 'State/province updated.');
    }

    public function destroy(State $state): RedirectResponse
    {
        $state->delete();

        return redirect()->route('states.index')->with('status', 'State/province removed.');
    }

    private function validated(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'country_id' => 'required|exists:countries,id',
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:10',
        ]);

        $validator->validate();

        return $validator->validated();
    }
}
