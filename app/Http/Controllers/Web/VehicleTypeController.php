<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\VehicleType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class VehicleTypeController extends Controller
{
    public function index(): View
    {
        $vehicleTypes = VehicleType::orderBy('name')->paginate(15);

        return view('vehicle-types.index', compact('vehicleTypes'));
    }

    public function create(): View
    {
        return view('vehicle-types.form', ['vehicleType' => new VehicleType()]);
    }

    public function store(Request $request): RedirectResponse
    {
        VehicleType::create($this->validated($request));

        return redirect()->route('vehicle-types.index')->with('status', 'Vehicle type added.');
    }

    public function edit(VehicleType $vehicleType): View
    {
        return view('vehicle-types.form', compact('vehicleType'));
    }

    public function update(Request $request, VehicleType $vehicleType): RedirectResponse
    {
        $vehicleType->update($this->validated($request));

        return redirect()->route('vehicle-types.index')->with('status', 'Vehicle type updated.');
    }

    public function destroy(VehicleType $vehicleType): RedirectResponse
    {
        $vehicleType->delete();

        return redirect()->route('vehicle-types.index')->with('status', 'Vehicle type removed.');
    }

    private function validated(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:vehicle_types,code,' . $request->route('vehicleType')?->id,
            'max_weight_capacity' => 'nullable|numeric|min:0',
            'max_length_cm' => 'nullable|numeric|min:0',
            'max_width_cm' => 'nullable|numeric|min:0',
            'max_height_cm' => 'nullable|numeric|min:0',
            'is_open_body' => 'sometimes|boolean',
            'is_active' => 'sometimes|boolean',
        ]);

        $data = $validator->validate();
        $data['is_open_body'] = $request->boolean('is_open_body');
        $data['is_active'] = $request->boolean('is_active', true);

        foreach (['max_weight_capacity', 'max_length_cm', 'max_width_cm', 'max_height_cm'] as $field) {
            if (($data[$field] ?? null) === '') {
                $data[$field] = null;
            }
        }

        return $data;
    }
}
