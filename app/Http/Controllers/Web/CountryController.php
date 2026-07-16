<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Country;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class CountryController extends Controller
{
    public function index(): View
    {
        $countries = Country::withCount('states')->orderBy('name')->paginate(15);

        return view('countries.index', compact('countries'));
    }

    public function create(): View
    {
        return view('countries.form', ['country' => new Country()]);
    }

    public function store(Request $request): RedirectResponse
    {
        Country::create($this->validated($request));

        return redirect()->route('countries.index')->with('status', 'Country added.');
    }

    public function edit(Country $country): View
    {
        return view('countries.form', compact('country'));
    }

    public function update(Request $request, Country $country): RedirectResponse
    {
        $country->update($this->validated($request));

        return redirect()->route('countries.index')->with('status', 'Country updated.');
    }

    public function destroy(Country $country): RedirectResponse
    {
        $country->delete();

        return redirect()->route('countries.index')->with('status', 'Country removed.');
    }

    private function validated(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:3|unique:countries,code,' . $request->route('country')?->id,
        ]);

        $validator->validate();

        return $validator->validated();
    }
}
