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
        return view('additional-services.form', ['additionalService' => new AdditionalService(), 'options' => collect()]);
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

            AdditionalServiceOption::create([
                'additional_service_id' => $service->id,
                'name' => $option['name'],
                'price' => $option['price'],
                'is_active' => true,
            ]);
            $saved++;
        }

        return redirect()->route('additional-services.index')->with('status', "Service created with {$saved} option" . ($saved === 1 ? '' : 's') . '.');
    }

    public function edit(AdditionalService $additionalService): View
    {
        return view('additional-services.form', [
            'additionalService' => $additionalService,
            'options' => $additionalService->options()->orderBy('name')->get(),
        ]);
    }

    /**
     * Same id-based create/update/delete pattern as Standard Billing's
     * zone prices: a row with its id present and Price filled updates
     * that option; id present and Price blank deletes it; no id and
     * filled creates a new one; an option removed client-side (via the
     * Remove button) never reaches the request at all and is deleted
     * here too, since it's no longer represented in the submission.
     */
    public function update(Request $request, AdditionalService $additionalService): RedirectResponse
    {
        $data = $this->validated($request, $additionalService);

        $additionalService->update([
            'name' => $data['name'],
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
                    'price' => $option['price'],
                ]);
                $saved++;
                continue;
            }

            if ($hasPrice && ! empty($option['name'])) {
                $new = AdditionalServiceOption::create([
                    'additional_service_id' => $additionalService->id,
                    'name' => $option['name'],
                    'price' => $option['price'],
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
        $additionalService->delete();

        return redirect()->route('additional-services.index')->with('status', 'Service removed.');
    }

    private function validated(Request $request, ?AdditionalService $ignoring = null): array
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:additional_services,name,' . ($ignoring?->id),
            'options' => 'nullable|array',
            'options.*.id' => 'nullable|integer|exists:additional_service_options,id',
            'options.*.name' => 'nullable|string|max:255',
            'options.*.price' => 'nullable|numeric|min:0',
        ]);

        return $validator->validate();
    }
}
