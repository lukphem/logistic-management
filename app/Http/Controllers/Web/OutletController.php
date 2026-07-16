<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Hub;
use App\Models\Outlet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class OutletController extends Controller
{
    public function index(): View
    {
        $outlets = Outlet::with('hub')->orderBy('name')->paginate(15);

        return view('outlets.index', compact('outlets'));
    }

    public function create(): View
    {
        return view('outlets.form', ['outlet' => new Outlet(), 'hubs' => Hub::orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        Outlet::create($this->validated($request));

        return redirect()->route('outlets.index')->with('status', 'Outlet added.');
    }

    public function edit(Outlet $outlet): View
    {
        return view('outlets.form', ['outlet' => $outlet, 'hubs' => Hub::orderBy('name')->get()]);
    }

    public function update(Request $request, Outlet $outlet): RedirectResponse
    {
        $outlet->update($this->validated($request));

        return redirect()->route('outlets.index')->with('status', 'Outlet updated.');
    }

    public function destroy(Outlet $outlet): RedirectResponse
    {
        $outlet->delete();

        return redirect()->route('outlets.index')->with('status', 'Outlet removed.');
    }

    private function validated(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'hub_id' => 'required|exists:hubs,id',
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:outlets,code,' . $request->route('outlet')?->id,
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
