<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AdditionalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class AdditionalServiceController extends Controller
{
    public function index(): View
    {
        $additionalServices = AdditionalService::orderBy('name')->paginate(15);

        return view('additional-services.index', compact('additionalServices'));
    }

    public function create(): View
    {
        return view('additional-services.form', ['additionalService' => new AdditionalService()]);
    }

    public function store(Request $request): RedirectResponse
    {
        AdditionalService::create($this->validated($request));

        return redirect()->route('additional-services.index')->with('status', 'Service added.');
    }

    public function edit(AdditionalService $additionalService): View
    {
        return view('additional-services.form', compact('additionalService'));
    }

    public function update(Request $request, AdditionalService $additionalService): RedirectResponse
    {
        $additionalService->update($this->validated($request));

        return redirect()->route('additional-services.index')->with('status', 'Service updated.');
    }

    public function destroy(AdditionalService $additionalService): RedirectResponse
    {
        $additionalService->delete();

        return redirect()->route('additional-services.index')->with('status', 'Service removed.');
    }

    private function validated(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'is_active' => 'sometimes|boolean',
        ]);

        $validator->validate();
        $data = $validator->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        return $data;
    }
}
