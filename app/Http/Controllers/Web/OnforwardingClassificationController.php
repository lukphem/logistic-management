<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\OnforwardingClassification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class OnforwardingClassificationController extends Controller
{
    public function index(): View
    {
        $classifications = OnforwardingClassification::withCount(['cities', 'districts'])->orderBy('name')->paginate(15);

        return view('onforwarding-classifications.index', compact('classifications'));
    }

    public function create(): View
    {
        return view('onforwarding-classifications.form', ['classification' => new OnforwardingClassification()]);
    }

    public function store(Request $request): RedirectResponse
    {
        OnforwardingClassification::create($this->validated($request));

        return redirect()->route('onforwarding-classifications.index')->with('status', 'Classification added.');
    }

    public function edit(OnforwardingClassification $onforwardingClassification): View
    {
        return view('onforwarding-classifications.form', ['classification' => $onforwardingClassification]);
    }

    public function update(Request $request, OnforwardingClassification $onforwardingClassification): RedirectResponse
    {
        $onforwardingClassification->update($this->validated($request));

        return redirect()->route('onforwarding-classifications.index')->with('status', 'Classification updated.');
    }

    public function destroy(OnforwardingClassification $onforwardingClassification): RedirectResponse
    {
        $onforwardingClassification->delete();

        return redirect()->route('onforwarding-classifications.index')->with('status', 'Classification removed.');
    }

    private function validated(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'surcharge_amount' => 'required|numeric|min:0',
            'is_default' => 'sometimes|boolean',
        ]);

        $validator->validate();
        $data = $validator->validated();
        $data['is_default'] = $request->boolean('is_default');

        return $data;
    }
}
