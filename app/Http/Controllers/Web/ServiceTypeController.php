<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ServiceType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class ServiceTypeController extends Controller
{
    public function index(): View
    {
        $serviceTypes = ServiceType::orderBy('name')->paginate(15);

        return view('service-types.index', compact('serviceTypes'));
    }

    public function create(): View
    {
        return view('service-types.form', ['serviceType' => new ServiceType()]);
    }

    public function store(Request $request): RedirectResponse
    {
        ServiceType::create($this->validated($request));

        return redirect()->route('service-types.index')->with('status', 'Service type added.');
    }

    public function edit(ServiceType $serviceType): View
    {
        return view('service-types.form', compact('serviceType'));
    }

    public function update(Request $request, ServiceType $serviceType): RedirectResponse
    {
        $serviceType->update($this->validated($request, $serviceType->id));

        return redirect()->route('service-types.index')->with('status', 'Service type updated.');
    }

    public function destroy(ServiceType $serviceType): RedirectResponse
    {
        $serviceType->delete();

        return redirect()->route('service-types.index')->with('status', 'Service type removed.');
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:service_types,code,' . $ignoreId,
            'billing_model' => 'nullable|in:' . implode(',', array_keys(\App\Models\Setting::BILLING_MODELS)),
            'is_active' => 'sometimes|boolean',
        ]);

        $validator->validate();
        $data = $validator->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        if (($data['billing_model'] ?? null) === '') {
            $data['billing_model'] = null;
        }

        return $data;
    }
}
