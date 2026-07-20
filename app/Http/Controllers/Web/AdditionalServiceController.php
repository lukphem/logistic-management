<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AdditionalService;
use App\Models\AdditionalServiceOption;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class AdditionalServiceController extends Controller
{
    public function index(): View
    {
        $additionalServices = AdditionalService::withCount('options')->orderBy('name')->paginate(15);

        return view('additional-services.index', compact('additionalServices'));
    }

    public function create(): View
    {
        return view('additional-services.form', [
            'additionalService' => new AdditionalService(),
            'options' => collect(),
            'serviceTypes' => \App\Models\ServiceType::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    /**
     * Same single-form principle as Standard Billing tariffs: the
     * service and every one of its priced options are submitted
     * together. A service with just one variant still works fine — one
     * option row is all that's required, this doesn't force every
     * service to have multiple types.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $service = AdditionalService::create([
            'name' => $data['name'],
            'is_active' => $request->boolean('is_active', true),
        ]);

        $saved = 0;
        foreach ($data['options'] ?? [] as $option) {
            if (empty($option['name']) || ! isset($option['price']) || $option['price'] === '') {
                continue;
            }

            $chargeType = $option['charge_type'] ?? 'flat';

            AdditionalServiceOption::create([
                'additional_service_id' => $service->id,
                'name' => $option['name'],
                'charge_type' => $chargeType,
                'reverse_service_type_id' => $chargeType === 'percentage_of_reverse_shipment' ? ($option['reverse_service_type_id'] ?? null) : null,
                'reverse_weight_kg' => $chargeType === 'percentage_of_reverse_shipment' ? ($option['reverse_weight_kg'] ?? null) : null,
                'price' => $option['price'],
                'is_vatable' => array_key_exists('is_vatable', $option) ? (bool) $option['is_vatable'] : true,
                'is_active' => true,
            ]);
            $saved++;
        }

        return redirect()->route('additional-services.index')->with('status', "Service created with {$saved} option" . ($saved === 1 ? '' : 's') . '.');
    }

    public function edit(AdditionalService $additionalService): View
    {
        if ($additionalService->kind === 'acknowledgement') {
            return view('additional-services.acknowledgement-form', [
                'additionalService' => $additionalService,
                'option' => $additionalService->options()->first(),
                'serviceTypes' => \App\Models\ServiceType::where('is_active', true)->orderBy('name')->get(),
            ]);
        }

        return view('additional-services.form', [
            'additionalService' => $additionalService,
            'options' => $additionalService->options()->orderBy('name')->get(),
            'serviceTypes' => \App\Models\ServiceType::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    /**
     * Same id-based create/update/delete pattern as Standard Billing's
     * zone prices: a row with its id present and Price filled updates
     * that option; id present and Price blank deletes it; no id and
     * filled creates a new one; an option removed client-side (via the
     * Remove button) never reaches the request at all and is deleted
     * here too, since it's no longer represented in the submission.
     *
     * Packaging and Acknowledgement (isProtected()) can't have their
     * name changed here — Acknowledgement doesn't even reach this
     * method (see updateAcknowledgement() below), and Packaging keeps
     * this generic options builder, just with its name locked.
     */
    public function update(Request $request, AdditionalService $additionalService): RedirectResponse
    {
        if ($additionalService->kind === 'acknowledgement') {
            return $this->updateAcknowledgement($request, $additionalService);
        }

        $data = $this->validated($request, $additionalService);

        $additionalService->update([
            'name' => $additionalService->isProtected() ? $additionalService->name : $data['name'],
            'is_active' => $request->boolean('is_active', true),
        ]);

        $submittedIds = [];
        $saved = 0;

        foreach ($data['options'] ?? [] as $option) {
            $hasPrice = isset($option['price']) && $option['price'] !== '';

            if (! empty($option['id'])) {
                $submittedIds[] = $option['id'];

                if (! $hasPrice || empty($option['name'])) {
                    AdditionalServiceOption::where('id', $option['id'])->where('additional_service_id', $additionalService->id)->delete();
                    continue;
                }

                AdditionalServiceOption::where('id', $option['id'])->where('additional_service_id', $additionalService->id)->update([
                    'name' => $option['name'],
                    'charge_type' => $option['charge_type'] ?? 'flat',
                    'reverse_service_type_id' => ($option['charge_type'] ?? 'flat') === 'percentage_of_reverse_shipment' ? ($option['reverse_service_type_id'] ?? null) : null,
                    'reverse_weight_kg' => ($option['charge_type'] ?? 'flat') === 'percentage_of_reverse_shipment' ? ($option['reverse_weight_kg'] ?? null) : null,
                    'price' => $option['price'],
                    'is_vatable' => array_key_exists('is_vatable', $option) ? (bool) $option['is_vatable'] : true,
                ]);
                $saved++;
                continue;
            }

            if ($hasPrice && ! empty($option['name'])) {
                $chargeType = $option['charge_type'] ?? 'flat';

                $new = AdditionalServiceOption::create([
                    'additional_service_id' => $additionalService->id,
                    'name' => $option['name'],
                    'charge_type' => $chargeType,
                    'reverse_service_type_id' => $chargeType === 'percentage_of_reverse_shipment' ? ($option['reverse_service_type_id'] ?? null) : null,
                    'reverse_weight_kg' => $chargeType === 'percentage_of_reverse_shipment' ? ($option['reverse_weight_kg'] ?? null) : null,
                    'price' => $option['price'],
                    'is_vatable' => array_key_exists('is_vatable', $option) ? (bool) $option['is_vatable'] : true,
                    'is_active' => true,
                ]);
                $submittedIds[] = $new->id;
                $saved++;
            }
        }

        AdditionalServiceOption::where('additional_service_id', $additionalService->id)->whereNotIn('id', $submittedIds)->delete();

        return redirect()->route('additional-services.edit', $additionalService)->with('status', "Service updated, {$saved} option" . ($saved === 1 ? '' : 's') . ' saved.');
    }

    public function destroy(AdditionalService $additionalService): RedirectResponse
    {
        if ($additionalService->isProtected()) {
            return back()->withErrors(['additionalService' => "{$additionalService->name} is a built-in service and can't be removed — deactivate it instead if it shouldn't be offered right now."]);
        }

        $additionalService->delete();

        return redirect()->route('additional-services.index')->with('status', 'Service removed.');
    }

    /**
     * Acknowledgement is always exactly one configuration — Active,
     * which Service Type prices the return document, what percentage
     * of that reverse rate to charge, and whether the charge is
     * taxable — not a multi-option builder the way Packaging or a
     * custom service is. Internally still just one
     * AdditionalServiceOption with charge_type =
     * percentage_of_reverse_shipment, created on first save and updated
     * from then on.
     */
    private function updateAcknowledgement(Request $request, AdditionalService $additionalService): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'is_active' => 'sometimes|boolean',
            'reverse_service_type_id' => 'required|exists:service_types,id',
            'reverse_weight_kg' => 'required|numeric|min:0.01',
            'price' => 'required|numeric|min:0',
            'is_vatable' => 'sometimes|boolean',
        ]);

        $data = $validator->validate();

        $additionalService->update(['is_active' => $request->boolean('is_active', false)]);

        AdditionalServiceOption::updateOrCreate(
            ['additional_service_id' => $additionalService->id],
            [
                'name' => 'Standard',
                'charge_type' => 'percentage_of_reverse_shipment',
                'reverse_service_type_id' => $data['reverse_service_type_id'],
                'reverse_weight_kg' => $data['reverse_weight_kg'],
                'price' => $data['price'],
                'is_vatable' => $request->boolean('is_vatable', true),
                'is_active' => true,
            ]
        );

        return redirect()->route('additional-services.edit', $additionalService)->with('status', 'Acknowledgement settings saved.');
    }

    private function validated(Request $request, ?AdditionalService $ignoring = null): array
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:additional_services,name,' . ($ignoring?->id),
            'options' => 'nullable|array',
            'options.*.id' => 'nullable|integer|exists:additional_service_options,id',
            'options.*.name' => 'nullable|string|max:255',
            'options.*.charge_type' => 'nullable|in:flat,percentage,percentage_of_reverse_shipment',
            'options.*.reverse_service_type_id' => 'nullable|exists:service_types,id',
            'options.*.reverse_weight_kg' => 'nullable|numeric|min:0.01',
            'options.*.price' => 'nullable|numeric|min:0',
            'options.*.is_vatable' => 'nullable|boolean',
        ]);

        return $validator->validate();
    }
}
