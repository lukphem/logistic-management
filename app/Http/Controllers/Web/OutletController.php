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
        return view('outlets.form', ['outlet' => new Outlet(), 'hubs' => Hub::orderBy('name')->get(), 'serviceTypes' => \App\Models\ServiceType::orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        Outlet::create($this->validated($request));

        return redirect()->route('outlets.index')->with('status', 'Outlet added.');
    }

    public function edit(Outlet $outlet): View
    {
        return view('outlets.form', ['outlet' => $outlet, 'hubs' => Hub::orderBy('name')->get(), 'serviceTypes' => \App\Models\ServiceType::orderBy('name')->get()]);
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
            'short_code' => 'nullable|string|size:3|unique:outlets,short_code,' . $request->route('outlet')?->id,
            'address' => 'required|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'is_active' => 'sometimes|boolean',
            'can_collect_cash' => 'sometimes|boolean',
            'enabled_billing_models' => 'nullable|array',
            'enabled_billing_models.*' => 'in:' . implode(',', array_keys(\App\Models\Setting::BILLING_MODELS)),
            'enabled_service_type_ids' => 'nullable|array',
            'enabled_service_type_ids.*' => 'exists:service_types,id',
            'discount_percentage' => 'required|numeric|min:0|max:100',
        ]);

        $validator->validate();

        $data = $validator->validated();
        $data['is_active'] = $request->boolean('is_active', true);
        $data['can_collect_cash'] = $request->boolean('can_collect_cash', true);

        // Same inversion as ClientController::updateDisabledBillingModels()
        // — checkboxes that are UNCHECKED submit nothing, so the array of
        // DISABLED values has to be built from which ones weren't
        // checked, not read directly off the request. Keeps the UX
        // consistent with how this already works for client accounts.
        $allBillingModels = array_keys(\App\Models\Setting::current()->supportedBillingModels());
        $enabledBillingModels = $data['enabled_billing_models'] ?? $allBillingModels;
        $data['disabled_billing_models'] = array_values(array_diff($allBillingModels, $enabledBillingModels));
        unset($data['enabled_billing_models']);

        $allServiceTypeIds = \App\Models\ServiceType::pluck('id')->all();
        $enabledServiceTypeIds = $data['enabled_service_type_ids'] ?? $allServiceTypeIds;
        $data['disabled_service_type_ids'] = array_values(array_diff($allServiceTypeIds, $enabledServiceTypeIds));
        unset($data['enabled_service_type_ids']);

        // Blank means "auto-generate" (on create, the model's own
        // creating() hook fills it in) or "leave whatever's already
        // there" (on update) — never explicitly overwrite an existing
        // code with nothing.
        if (empty($data['short_code'])) {
            unset($data['short_code']);
        } else {
            $data['short_code'] = strtoupper($data['short_code']);
        }

        return $data;
    }
}
