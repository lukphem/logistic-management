<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Region;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class RegionController extends Controller
{
    public function index(): View
    {
        $regions = Region::withCount('hubs')->orderBy('name')->paginate(15);

        return view('regions.index', compact('regions'));
    }

    public function create(): View
    {
        return view('regions.form', ['region' => new Region()]);
    }

    public function store(Request $request): RedirectResponse
    {
        Region::create($this->validated($request));

        return redirect()->route('regions.index')->with('status', 'Region added.');
    }

    public function edit(Region $region): View
    {
        return view('regions.form', compact('region'));
    }

    public function update(Request $request, Region $region): RedirectResponse
    {
        $region->update($this->validated($request));

        return redirect()->route('regions.index')->with('status', 'Region updated.');
    }

    public function destroy(Region $region): RedirectResponse
    {
        // Hubs under this region aren't deleted — they just fall back to
        // no region (nullOnDelete in the migration), same as removing a
        // hub never deletes its zones.
        $region->delete();

        return redirect()->route('regions.index')->with('status', 'Region removed.');
    }

    private function validated(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:regions,code,' . $request->route('region')?->id,
        ]);

        $validator->validate();

        return $validator->validated();
    }
}
