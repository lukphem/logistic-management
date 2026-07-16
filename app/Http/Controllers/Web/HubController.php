<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Hub;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class HubController extends Controller
{
    public function index(): View
    {
        $hubs = Hub::withCount('zones')->orderBy('name')->paginate(15);

        return view('hubs.index', compact('hubs'));
    }

    public function create(): View
    {
        return view('hubs.form', ['hub' => new Hub()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        Hub::create($data);

        return redirect()->route('hubs.index')->with('status', 'Hub added.');
    }

    public function edit(Hub $hub): View
    {
        return view('hubs.form', compact('hub'));
    }

    public function update(Request $request, Hub $hub): RedirectResponse
    {
        $hub->update($this->validated($request));

        return redirect()->route('hubs.index')->with('status', 'Hub updated.');
    }

    public function destroy(Hub $hub): RedirectResponse
    {
        $hub->delete();

        return redirect()->route('hubs.index')->with('status', 'Hub removed.');
    }

    private function validated(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:hubs,code,' . $request->route('hub')?->id,
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
