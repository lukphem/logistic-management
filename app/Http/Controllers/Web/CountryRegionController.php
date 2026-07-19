<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\CountryRegion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class CountryRegionController extends Controller
{
    public function index(): View
    {
        $countryRegions = CountryRegion::withCount('countries')->orderBy('name')->paginate(15);

        return view('country-regions.index', compact('countryRegions'));
    }

    public function create(): View
    {
        return view('country-regions.form', ['countryRegion' => new CountryRegion()]);
    }

    public function store(Request $request): RedirectResponse
    {
        CountryRegion::create($this->validated($request));

        return redirect()->route('country-regions.index')->with('status', 'Region added.');
    }

    public function edit(CountryRegion $countryRegion): View
    {
        return view('country-regions.form', compact('countryRegion'));
    }

    public function update(Request $request, CountryRegion $countryRegion): RedirectResponse
    {
        $countryRegion->update($this->validated($request));

        return redirect()->route('country-regions.index')->with('status', 'Region updated.');
    }

    public function destroy(CountryRegion $countryRegion): RedirectResponse
    {
        $countryRegion->delete();

        return redirect()->route('country-regions.index')->with('status', 'Region removed.');
    }

    private function validated(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:country_regions,name,' . $request->route('countryRegion')?->id,
        ]);

        $validator->validate();

        return $validator->validated();
    }
}
