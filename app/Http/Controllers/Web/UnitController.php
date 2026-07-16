<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Hub;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class UnitController extends Controller
{
    public function index(): View
    {
        $units = Unit::with('hub')->orderBy('name')->paginate(15);

        return view('units.index', compact('units'));
    }

    public function create(): View
    {
        return view('units.form', ['unit' => new Unit(), 'hubs' => Hub::orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        Unit::create($this->validated($request));

        return redirect()->route('units.index')->with('status', 'Unit added.');
    }

    public function edit(Unit $unit): View
    {
        return view('units.form', ['unit' => $unit, 'hubs' => Hub::orderBy('name')->get()]);
    }

    public function update(Request $request, Unit $unit): RedirectResponse
    {
        $unit->update($this->validated($request));

        return redirect()->route('units.index')->with('status', 'Unit updated.');
    }

    public function destroy(Unit $unit): RedirectResponse
    {
        $unit->delete();

        return redirect()->route('units.index')->with('status', 'Unit removed.');
    }

    private function validated(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'hub_id' => 'required|exists:hubs,id',
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:units,code,' . $request->route('unit')?->id,
        ]);

        $validator->validate();

        return $validator->validated();
    }
}
